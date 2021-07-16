<?php

namespace App\Http\OpenAPI\Controllers;

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
use App\Http\Controllers\ApiController;
use App\Http\OpenAPI\Transformers\CustomersTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\DirExistsException;

class ProspectsController extends ApiController
{
    protected $jobProjectService;

    public function __construct(JobProjectService $jobProjectService, Larasponse $response)
    {
        parent::__construct();

        $this->jobProjectService = $jobProjectService;

        $this->response = $response;
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

        $input['jobs'] = [];

        if(ine($input, 'job')) {

            $input['job']['contact_same_as_customer'] = array_get($input, 'contact_same_as_customer', 1);

            $input['job']['same_as_customer_address'] = array_get($input, 'same_as_customer_address', 1);
            
            $input['jobs'][0] = $input['job'];
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
                return ApiResponse::validation($validate);
            }
        }
        
        $input['stop_db_transaction'] = true;
        DB::beginTransaction();
        try {
            $meta = [
                'job_ids' => [],
                'first_stage_code' => null,
            ];

            $user_id = \Auth::user()->id;
            $customerRepAssign = !ine($input, 'jobs');

            
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

            if (ine($input, 'jobs')) {
                $newCustomerRep = isset($input['rep_id']) ? $input['rep_id'] : 0; // unassign rep..
                $oldCustomerRep = $customer->rep_id;

                $meta = $this->saveJobs($customer, $input['jobs']);
            }
        } catch (DuplicateRecordException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidDivisionException $e) {
            DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DirExistsException $e) {
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

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Prospect / Customer']),
            'customer' => $this->response->item($customer, new CustomersTransformer),
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

            if(!$job['contact_same_as_customer']) {
                $scope[] = 'contact';
            }
            
            if (ine($job, 'contact')) {
                $scope[] = 'contact';
            }
            
            if (!$job['same_as_customer_address']) {
                $scope[] = 'address';
            }

            $scope[] = 'open-api';

            if (ine($job, 'trades')) {
                foreach($job['trades'] as $trade) {
                    if(!$trade) {
                        $job['trades'] = [];
                    }
                }
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

        if (isset($input['phones'])) {
            $scope[] = 'phones';
            $scope['phones_count'] = count($input['phones']);
        }

        if (isset($input['referred_by'])) {
            $scope[] = 'referred_by';
        }

        if (isset($input['customer_contacts'])) {
            $scope[] = 'customer_contacts';
            $scope['customer_contacts_count'] = count($input['customer_contacts']);
        }

        return $scope;
    }
}
