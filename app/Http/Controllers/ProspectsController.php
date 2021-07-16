<?php

namespace App\Http\Controllers;

use App\Exception\DuplicateRecordException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\Customer;
use App\Models\Job;
use App\Services\Jobs\JobProjectService;
use QBDesktopQueue;
use Solr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Queue;
use Event;
use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Exceptions\InvalidDivisionException;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Events\JobCreated;
use App\Exceptions\InvalidJobContactData;
use App\Exceptions\DirExistsException;

class ProspectsController extends ApiController
{
    protected $jobProjectService;

    public function __construct(JobProjectService $jobProjectService)
    {
        parent::__construct();
        $this->jobProjectService = $jobProjectService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        if (ine($input, 'id') && !SecurityCheck::maxCustomerJobEditLimit()) {
            return SecurityCheck::$error;
        }

        $input['billing']['same_as_customer_address'] = array_get($input, 'billing.same_as_customer_address', 1);

        $scopes = $this->setValidationScope($input);
        $validate = Validator::make($input, Customer::validationRules($scopes));

        //validate customer data.
        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        //validate jobs data.
        if (isset($input['jobs']) && is_array($input['jobs']) && !empty($input['jobs'])) {
            if ($validate = $this->jobsValidate($input['jobs'])) {
                return ApiResponse::validation($validate->instance());
            }
        }
        $input['stop_db_transaction'] = true;
        $token = null;
        DB::beginTransaction();
        try {
            $meta = [
                'job_ids' => [],
                'first_stage_code' => null,
            ];

            $user_id = \Auth::user()->id;
            $customerRepAssign = !ine($input, 'jobs');
            $customer = $this->executeCommand('\App\Commands\CustomerCommand', $input);

            if (ine($input, 'jobs')) {
                $newCustomerRep = isset($input['rep_id']) ? $input['rep_id'] : 0; // unassign rep..
                $oldCustomerRep = $customer->rep_id;

                $meta = $this->saveJobs($customer, $input['jobs']);
            }
        } catch(DuplicateRecordException $e ){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e ){
			//Do nothing
		} catch(QuickBookException $e ){
			//Do nothing
		} catch(InvalidDivisionException $e ){
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch(DirExistsException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidJobContactData $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();
        if(ine($input,'id')) {
            Event::fire('JobProgress.Customers.Events.CustomerUpdated', new CustomerUpdated($customer->id));
        } else {
            Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($customer->id));
        }

        // if(!$customer->disable_qbo_sync && ine($meta, 'job_ids')) {

		// 	$job = \Job::findOrFail($meta['job_ids'][0]);

		// 	\Event::fire('JobProgress.Jobs.Events.JobCreated', new JobCreated($job));
		// }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Prospect / Customer']),
            'customer' => $customer,
            'job_ids' => $meta['job_ids'],
            'first_stage_code' => $meta['first_stage_code']
        ]);
    }

    /***************** Private Section ******************/

    private function saveJobs(Customer $customer, array $jobsData)
    {
        if (empty($jobsData)) {
            return;
        }
        $ret = [];
        foreach ($jobsData as $key => $jobData) {
            $jobData['customer_id'] = $customer->id;
            $job = $this->jobProjectService->saveJobAndProjects($jobData);
            $ret['job_ids'][$key] = $job->id;
        }

        $ret['first_stage_code'] = $job->jobWorkflow->current_stage;

        return $ret;
    }

    private function jobsValidate($jobs)
    {
        foreach ($jobs as $job) {
            $scope = [];
            $job['same_as_customer_address'] = array_get($job, 'same_as_customer_address', 1);
            if (ine($job, 'contacts')) {
                $scope[] = 'contacts';
            }
            if (!$job['same_as_customer_address']) {
                $scope[] = 'address';
            }

            $validate = Validator::make($job, Job::validationRules($scope));

            if ($validate->fails()) {
                return $validate;
            }
        }
        return false;
    }


    private function setValidationScope($input)
    {
        $scope = [];

        if (isset($input['referred_by'])) {
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

        return $scope;
    }
}
