<?php

namespace App\Http\Controllers;

use App\Exception\DuplicateRecordException;
use App\Exceptions\QuickBookException;
use App\Exceptions\AuthorizationException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\Customer;
use App\Models\CustomerMeta;
use App\Models\User;
use App\Repositories\CustomerListingRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Services\QuickBooks\QuickBookService;
use Solr;
use QBDesktopQueue;
use App\Transformers\CustomersTransformer;
use App\Transformers\Optimized\CustomersJobListTransformer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Event;
use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Exceptions\InvalidDivisionException;
use App\Services\Jobs\JobService;
use App\Exceptions\SystemReferralException;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\TwoWaySync\WebHook;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Events\CustomerSynched;
use App\Models\QuickBookTask;
use App\Models\QuickbookUnlinkCustomer;
use App\Exceptions\UnauthorizedException;
use Exception;
use App\Services\CustomerService;
use App\Exceptions\CustomerFoldersAlreadyLinkedException;

class CustomersController extends ApiController
{

    /**
     * Customer Repo
     * @var \App\Repositories\CustomerRepositories
     */
    protected $repo;

    /**
     * Display a listing of the resource.
     * GET /customers
     *
     * @return Response
     */
    protected $response;
    protected $scope;
    protected $jobRepo;
    protected $customerListingRepo;

    public function __construct(
        Larasponse $response,
        CustomerRepository $repo,
        Context $scope,
        JobRepository $jobRepo,
        CustomerListingRepository $customerListingRepo,
        QuickBookService $quickService,
        JobService $jobService,
        CustomerService $service
    ) {

        $this->response = $response;
        $this->repo = $repo;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->customerListingRepo = $customerListingRepo;
        $this->quickService = $quickService;
        $this->jobService = $jobService;
        $this->service = $service;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function index()
    {
        $input = Request::all();
        try{
            $customers = $this->repo->getFilteredCustomers($input);
            // response without pagination in case of distance filter..
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $customers = $customers->get();
                $customers = $this->addJobCounts($customers, $input);
                $response = $this->response->collection($customers, new CustomersTransformer);
            } else {
                $customers = $customers->paginate($limit);
                $customers = $this->addJobCounts($customers, $input);
                $response = $this->response->paginatedCollection($customers, new CustomersTransformer);
            }
            $response['params'] = $input; // includes applied filters in response..
            return ApiResponse::success($response);
        } catch(InvalidDivisionException $e){
			return ApiResponse::errorGeneral($e->getMessage());
 		} catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get Customer with jobs List (Minimum data)
     * GET /customers_jobs_list
     *
     * @return Response
     */
    public function customersJobsList()
    {
        $input = Request::all();
        try{
            $customers = $this->repo->getFilteredCustomers($input);
            // response without pagination in case of distance filter..
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $customers = $customers->get();
                $response = $this->response->collection($customers, new CustomersJobListTransformer);
            } else {
                $customers = $customers->paginate($limit);
                $response = $this->response->paginatedCollection($customers, new CustomersJobListTransformer);
            }

            return ApiResponse::success($response);
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
       } catch(\Exception $e){

        return ApiResponse::errorInternal(trans('response.error.internal'), $e);
       }
    }

    /**
     * Store a newly created resource in storage.
     * POST /customers
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        if ((ine($input, 'id')) && (!SecurityCheck::maxCustomerJobEditLimit())) {
            return SecurityCheck::$error;
        }

        $input['billing']['same_as_customer_address'] = array_get($input, 'billing.same_as_customer_address', 1);

        $scopes = $this->setValidationScope($input);
        $validate = Validator::make($input, Customer::validationRules($scopes));

        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        try {
            $customer = $this->executeCommand('\App\Commands\CustomerCommand', $input);

            if(ine($input,'id')) {
				Event::fire('JobProgress.Customers.Events.CustomerUpdated', new CustomerUpdated($customer->id));
			} else {
				Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($customer->id));
			}

            Solr::customerIndex($customer->id);
            QBDesktopQueue::addCustomer($customer->id);

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Prospect / Customer']),
                'customer' => $this->response->item($customer, new CustomersTransformer)
            ]);
        } catch (SystemReferralException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DuplicateRecordException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function saveCustomerByThirdPartyTool()
    {
        $input = Request::all();
        $input['billing']['same_as_customer_address'] = array_get($input, 'billing.same_as_customer_address', 1);

        $scopes = $this->setValidationScope($input);
        $validate = Validator::make($input, Customer::validationRules($scopes));

        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        $input['stop_db_transaction'] = true;

        DB::beginTransaction();
        try {
            if (!$customer = $customerExist = $this->checkCustomerDuplicate($input)) {
                $customer = $this->executeCommand('App\Commands\CustomerCommand', $input);
                $customerId = $customer->id;
            }

            if (ine($input, 'job')) {
                $job = $input['job'];
                $job['customer_id'] = $customer->id;
                $job['wp_job'] = true;
                $job = $this->executeCommand('App\Commands\JobCreateCommand', $job);
            }

            DB::commit();

            if($customerExist) {
                Event::fire('JobProgress.Customers.Events.CustomerUpdated', new CustomerUpdated($customer->id));
            } else {
                Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($customer->id));
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Customer']),
                'customer' => [
                    'id' => $customer->id,
                ]
            ]);
        } catch (SystemReferralException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DuplicateRecordException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /customers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $customer = $this->repo->getById($id, ['contacts']);
        $customer->jobsCount = $this->getJobsCounts($customer->id);
        return ApiResponse::success([
            'data' => $this->response->item($customer, new CustomersTransformer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /customers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        if (!SecurityCheck::maxCustomerJobEditLimit()) {
            return SecurityCheck::$error;
        }
        $customer = $this->repo->getById($id);

        $input = Request::all();
        $input['id'] = $id;
        $input['billing']['same_as_customer_address'] = array_get($input, 'billing.same_as_customer_address', 1);

        $scopes = $this->setValidationScope($input);
        $validate = Validator::make($input, Customer::validationRules($scopes));

        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        try {
            $customer = $this->executeCommand('App\Commands\CustomerCommand', $input);

            Event::fire('JobProgress.Customers.Events.CustomerUpdated', new CustomerUpdated($customer->id));

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Prospect / Customer']),
                'customer' => $this->response->item($customer, new CustomersTransformer)
            ]);
        } catch (SystemReferralException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DuplicateRecordException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function change_representative()
    {
        $input = Request::onlyLegacy('customer_id', 'rep_id', 'update_jobs');

        $validator = Validator::make($input, Customer::getChangeRepresentativeRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $customer = Customer::findOrFail($input['customer_id']);
        if (ine($input, 'rep_id')) {
            $user = User::where('id', $input['rep_id'])->company($this->scope->id())->first();
            if (!$user) {
                return ApiResponse::errorNotFound(Lang::get('response.error.not_found', ['attribute' => 'Representative']));
            }
        } else {
            $input['rep_id'] = 0; // unassign rep..
        }


        try {
            $customer = $this->repo->changeRep($customer, $input['rep_id']);

            return ApiResponse::success([
                'message' => Lang::get('response.success.changed', ['attribute' => 'Representative']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /customers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        if (!\Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        $input = Request::onlyLegacy('password', 'note');

        $validator = Validator::make($input, Customer::getDeleteRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $customer = $this->repo->getById($id);
        DB::beginTransaction();
        try {
            $customer->delete();
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => Lang::get('response.success.deleted', ['attribute' => 'Customer']),
        ]);
    }

    /**
     * Restore soft deleted customer
     * Put /customers/{id}/restore
     *
     * @param  int $id
     * @return Response
     */
    public function restore($id)
    {
        $input = Request::onlyLegacy('job_id', 'all_job');

        if (!Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $customer = $this->repo->getDeletedById($id);
        DB::beginTransaction();
        try {
            $this->jobService->restoreCustomer($customer, $input['all_job'], $input['job_id']);

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.restored', ['attribute' => 'Customer']),
        ]);
    }

    /**
     * Count Customers with jobs and without job
     * Get /customers/with_jobs
     *
     * @return Response
     */
    public function count_with_and_without_job()
    {
        $data = [];
        $data['with_job'] = $this->customerListingRepo->getCustomerQeuryBuilder(['has_jobs' => true])->count();
        $data['without_job'] = $this->customerListingRepo->getCustomerQeuryBuilder(['has_jobs' => false])->count();
        return ApiResponse::success(['data' => $data]);
    }

    public function customer_communication($id)
    {
        $input = Request::onlyLegacy('type', 'status');
        $validator = Validator::make($input, Customer::getCustomerCommunicationRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $customer = Customer::findOrFail($id);
            if ($input['type'] == 'call') {
                $customer->call_required = $input['status'];
            } else {
                $customer->appointment_required = $input['status'];
            }
            $customer->update();
            return ApiResponse::success(['message' => Lang::get('response.success.updated', ['attribute' => 'Customer'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function give_access($id)
    {
        $customer = $this->repo->getById($id);
        $input = Request::onlyLegacy('users');
        $validator = Validator::make($input, ['users' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $customer->users()->detach();
            $customer->users()->attach($input['users']);
            return ApiResponse::success([
                'message' => trans('response.success.access_granted'),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * POST customers/{id}/selected_jobs
     * Selected job for proposal
     * @param type $id
     * @return type
     */
    public function setSelectedJobs($id)
    {
        $input = Request::onlyLegacy('job_ids');
        $validator = Validator::make($input, CustomerMeta::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $customer = $this->repo->getById($id);
        try {
            $customerMeta = CustomerMeta::firstOrNew([
                'customer_id' => $id,
                'meta_key' => CustomerMeta::SELECTED_JOB,
                'created_by' => \Auth::id(),
            ]);
            $customerMeta->meta_value = $input['job_ids'];
            $customerMeta->save();

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Selected Jobs'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * GET customers/{id}/selected_jobs
     * get Selected job for proposal
     * @param type $id
     * @return type
     */

    public function getSelectedJobs($customerId)
    {
        $meta = CustomerMeta::whereCustomerId($customerId)
            ->whereCreatedBy(\Auth::id())
            ->firstOrFail();

        return ApiResponse::success([
            'data' => [
                'job_ids' => $meta->meta_value
            ]
        ]);
    }

    /**
     * Get /customers/count_commercial_residential
     * Count of commercial and residential customers
     * @return count
     */
    public function countCommercialAndResidential()
    {
        $count = $this->customerListingRepo
            ->getCustomerQeuryBuilder()
            ->selectRaw('count(id) as count, is_commercial')
            ->groupBy('is_commercial')
            ->pluck('count', 'is_commercial')->toArray();

        $data['commercial'] = (int)issetRetrun($count, 1);
        $data['residential'] = (int)issetRetrun($count, 0);

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * Save customer on quickbook
     * Put /customers/{id}/save_on_quickbook
     * @return Response
     */
    public function saveOnQuickbook($id)
    {
        $customer = $this->customerListingRepo->getById($id);

        if($customer->disable_qbo_sync || $customer->unlinkCustomer){
			return ApiResponse::errorGeneral('Customer not synced. ');
		}

		$token = QuickBooks::getToken();

        if (!$token) {
            return ApiResponse::errorGeneral(
                trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
            );
        }

        try {
            // Create Customer in QuickBooks
			QBOQueue::addTask(QuickBookTask::QUICKBOOKS_CUSTOMER_CREATE, ['id' => $id], [
				'object_id' => $id,
				'object' => 'Customer',
				'action' => QuickBookTask::CREATE,
				'origin' => QuickBookTask::ORIGIN_JP,
				'created_source' => QuickBookTask::SYSTEM_EVENT
			]);

            return ApiResponse::success([
                'message' => 'Customer synced'
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
	 * Synch customer account on quickbooks
	 * Put /customers/{id}/synch_on_quickbooks
	 * @return Response
	 */
	public function syncCustomerAccountOnQuickbooks($id)
	{
		$customer = $this->customerListingRepo->getById($id);

		Event::fire('JobProgress.Customers.Events.CustomerSynched', new CustomerSynched($customer));
		return ApiResponse::success([
			'message' => 'Customer has been successfully queued for Synching.'
		]);
	}

	/**
	 * Unlink customer on quickbook
	 * Put /customers/{id}/unlink_from_quickbook
	 * @return Response
	 */
	public function unlinkFromQuickbook($id)
	{
		$meta = [];
		$customer = $this->customerListingRepo->getById($id);
		if($token = QBDesktopQueue::isAccountConnected()){
			$meta['type'] = QuickbookUnlinkCustomer::QBD;
		}else{
			$token = QuickBooks::isConnected();
			$meta['type'] = QuickbookUnlinkCustomer::QBO;
		}

		if(!$token) {
			return ApiResponse::errorGeneral(
				trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
			);
		}

		try {

			$this->repo->unlinkFromQuickbooks($customer, $meta);

			return ApiResponse::success([
				'message' => 'Customer unlinked.'
			]);

		} catch(UnauthorizedException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Disable Quickbook Sync for customer
	 * Put /customers/{id}/disable_qbo_sync
	 * @return Response
	 */
	public function disableQboSync($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, ['disable_qbo_sync' => 'required']);

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$customer = $this->customerListingRepo->getById($id);

		if(QBDesktopQueue::isAccountConnected()){
			$token = true;
		}else{
			$token = QuickBooks::isConnected();
		}

		if(!$token) {
			return ApiResponse::errorGeneral(
				trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
			);
		}

		try {

			$customer->disable_qbo_sync = $input['disable_qbo_sync'];
			$customer->save();
			return ApiResponse::success([
				'message' => 'Customer updated.'
			]);

		} catch(UnauthorizedException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    public function linkCustomerFoldersWithSettingFolders($id)
	{
		$customer = $this->repo->getById($id);

		try {
			$this->service->linkCustomerFoldersWithSettingFolders($customer);
			$customer = $this->repo->getById($id);

			return ApiResponse::success([
				'message' => "Customer folders linked with new setting folders.",
				'data' => $this->response->item($customer, new CustomersTransformer)
			]);
		} catch (CustomerFoldersAlreadyLinkedException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}



    /**************************** Private Function ******************************/

    private function setValidationScope($input)
    {
        $scope = [];

        if (isset($input['referred_by_type'])) {
            $scope[] = 'referred_by';
        }

        if (isset($input['customer_contacts'])) {
            $scope[] = 'customer_contacts';
            $scope['customer_contacts_count'] = count($input['customer_contacts']);
        }

        if (isset($input['phones'])) {
            $scope[] = 'phones';
            $scope['phones_count'] = count($input['phones']);
        }

        if(isset($input['canvasser_id'])) {
			$scope[] = 'canvasser';
		}

		if(isset($input['call_center_rep_id'])) {
			$scope[] = 'call_center_rep';
		}

		if(isset($input['rep_id'])) {
			$scope[] = 'rep';
		}

        return $scope;
    }

    private function addJobCounts($customers, $filters = [])
    {
        foreach ($customers as $customer) {
            $customer->jobsCount = $this->getJobsCounts($customer->id, $filters);
        }
        return $customers;
    }

    private function getJobsCounts($customerId, $filters = [])
    {
        $filters['customer_id'] = $customerId;
        $jobs = $this->jobRepo->getFilteredJobs($filters, false);
        return $jobs->get()->count();
    }

    private function checkCustomerDuplicate($input)
    {
        $phones = []; 
        $numbers = array_column($input['phones'], 'number');
        foreach ($numbers as $number) {
            $phones[] =  str_replace(' ', '', preg_replace("/[^a-zA-Z0-9\s]/", "", $number));
        }

        if (ine($input, 'email')) {
            $customer = $this->repo->make()->whereEmail($input['email'])
                ->whereHas('phones', function ($q) use ($phones) {
                    $q->whereIn('number', $phones);
                })->first();
        } else {
            $customer = $this->repo->make()->whereFirstName($input['first_name'])
                ->whereLastName($input['last_name'])
                ->whereHas('phones', function ($q) use ($phones) {
                    $q->whereIn('number', $phones);
                })->first();
        }

        return $customer;
    }
}
