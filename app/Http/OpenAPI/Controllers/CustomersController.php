<?php

namespace App\Http\OpenAPI\Controllers;

use App\Exception\DuplicateRecordException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\CustomerListingRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Services\QuickBooks\QuickBookService;
use Solr;
use QBDesktopQueue;
use App\Http\OpenAPI\Transformers\CustomersTransformer;
use App\Http\OpenAPI\Transformers\Optimized\CustomersJobListTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Event;
use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Exceptions\InvalidDivisionException;
use App\Http\Controllers\ApiController;
use phpDocumentor\Reflection\Types\Boolean;
use App\Exceptions\SystemReferralException;

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
        QuickBookService $quickService
    ) {

        $this->response = $response;
        $this->repo = $repo;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->customerListingRepo = $customerListingRepo;
        $this->quickService = $quickService;

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
            
            $customers = $customers->paginate($limit);
            $customers = $this->addJobCounts($customers, $input);
            $response = $this->response->paginatedCollection($customers, new CustomersTransformer);

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

        $scopes = $this->setValidationScope($input);

        $validate = Validator::make($input, Customer::validationRules($scopes));
        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        try {

            $input['billing']['same_as_customer_address'] = array_get($input, 'billing.same_as_customer_address', 1);

            if (isset($input['is_commercial']) && ($input['is_commercial'])) {
                unset($input['customer_contacts']);
                if(ine($input, 'first_name') || ine($input, 'last_name')) {
                    $input['customer_contacts'][] = [
                        'first_name' => ine($input, 'first_name') ? $input['first_name']: '',
                        'last_name' => ine($input, 'last_name') ? $input['last_name']: '',
                    ];
                }

                $input['first_name'] = $input['company_name'];
            }

            $customer = $this->executeCommand('\App\Commands\CustomerCommand', $input);

            \Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($customer->id));

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

        $scopes = $this->setValidationScope($input);
        $validate = Validator::make($input, Customer::validationRules($scopes));

        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        try {

            $input['billing']['same_as_customer_address'] = array_get($input, 'billing.same_as_customer_address', 1);

            if (isset($input['is_commercial']) && ($input['is_commercial'])) {
                
                if(ine($input, 'first_name') || ine($input, 'last_name')) {
                    $input['customer_contacts'][] = [
                        'first_name' => ine($input, 'first_name') ? $input['first_name']: '',
                        'last_name' => ine($input, 'last_name') ? $input['last_name']: '',
                    ];
                }

                $input['first_name'] = $input['company_name'];
            }

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


    /**************************** Private Function ******************************/

    private function setValidationScope($input)
    {
        $scope = [];

        /**
         * Removed validation for the address fields
        */

        if(ine($input, 'is_commercial')) {
            $scope[] = 'commercial';
        }

        if (isset($input['referred_by_type'])) {
            $scope[] = 'referredBy';
        }
 
        if (isset($input['phones'])) {
            $scope[] = 'phones';
            $scope['phones_count'] = count($input['phones']);
        }

        if (isset($input['customer_contacts'])) {
            $scope[] = 'customer_contacts';
            $scope['customer_contacts_count'] = count($input['customer_contacts']);
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
