<?php

namespace App\Http\Controllers;

use App\Events\TempImportCustomerDeleted;
use App\Models\Address;
use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Flag;
use App\Models\State;
use App\Models\TempImportCustomer;
use App\Models\User;
use App\Repositories\CustomerRepository;
use App\Repositories\TempImportCustomersRepository;
use App\Services\Contexts\Context;
use App\Transformers\CustomersExportTransformer;
use App\Services\Jobs\JobProjectService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Excel;
use Sorskod\Larasponse\Larasponse;
use Request;
use Event;
use PDF;
use App\Services\Solr\Solr;
use App\Repositories\JobFollowUpRepository;
use App\Models\Referral;
use App\Models\Trade;
use App\Models\JobType;
use App\Models\Workflow;
use App\Models\WorkflowStage;
use App\Repositories\WorkflowRepository;
use Carbon\Carbon;
use App\Repositories\JobNotesRepository;
use App\Models\JobPayment;
use App\Models\JobFinancialCalculation;
use App\Services\JobSchedules\JobSchedulesService;
use App\Services\FinancialDetails\FinancialPayment;

class CustomersImportExportController extends ApiController
{

    /**
     * Customer Import Repo
     * @var \App\Repositories\TempImportCustomersRepository
     */
    protected $repo;

    /**
     * Customer Repo
     * @var \App\Repositories\CustomerRepositories
     */
    protected $customerRepo;
    protected $response;
    protected $scope;

    protected $jobProjectService;

    public function __construct(TempImportCustomersRepository $repo,
        CustomerRepository $customerRepo,
        Larasponse $response,
        Context $scope,
        JobProjectService $jobProjectService,
        JobFollowUpRepository $followUpRepo,
        WorkflowRepository $workFlowRepo,
        JobNotesRepository $jobNotesRepo,
        JobSchedulesService $jobScheduleService,
        FinancialPayment $finacialPayment
    ){
        $this->repo = $repo;
        $this->customerRepo = $customerRepo;
        $this->response = $response;
        $this->scope = $scope;
        $this->jobProjectService = $jobProjectService;
        $this->followUpRepo = $followUpRepo;
        $this->workFlowRepo = $workFlowRepo;
		$this->jobNotesRepo = $jobNotesRepo;
        $this->jobScheduleService = $jobScheduleService;
		$this->finacialPayment = $finacialPayment;

        parent::__construct();
    }

    public function import()
    {
        set_time_limit(0);
        $input = Request::onlyLegacy('file');
        $validator = Validator::make($input, TempImportCustomer::getImportRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $insuranceCategory  = JobType::whereNull('company_id')
				->where('name', JobType::INSURANCE_CLAIM)
				->where('type', JobType::JOB_TYPES)
				->first();
			$this->insuranceCategoryId = $insuranceCategory->id;

			$customers = $this->getCSVData(Request::file('file'));
            $count = count($customers);
            foreach ($customers as $customer) {
                TempImportCustomer::create($customer);
            }
            return ApiResponse::success(
                [
                    'message' => trans('response.success.records_received', ['count' => $count])
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function import_preview()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['type' => 'required|in:valid,invalid,duplicate,quickbook']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        switch ($input['type']) {
            case 'valid':
                $customers = $this->repo->getValidRecords();
                break;
            case 'invalid':
                $customers = $this->repo->getInvalidRecords();
                break;
            case 'duplicate':
                $customers = $this->repo->getDuplicateRecords();
                break;
            case 'quickbook':
                $customers = $this->repo->getQuickBookRecords();
                break;
            default:
                $customers = $this->repo->getValidRecords();
                break;
        }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $customers = $customers->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($customers, function ($customer) {
            $data['id'] = $customer->id;
            $data['customer'] = $customer->data;
            $data['errors'] = $customer->errors;
            $data['duplicate'] = $customer->duplicate;
            return $data;
        }));
    }

    public function import_preview_single($id)
    {
        $customer = $this->repo->getById($id);
        return ApiResponse::success($this->response->item($customer, function ($customer) {
            $data['data']['id'] = $customer->id;
            $data['data']['customer'] = $customer->data;
            $data['data']['errors'] = $customer->errors;
            $data['data']['duplicate'] = $customer->duplicate;
            return $data;
        }));
    }

    public function save_customers()
    {
        set_time_limit(0);
        try {
            $filters = [];
			if(getScopeId() == config('jp.temp_import_customer_company')) {
				$filters['include_duplicates'] = true;
            }

            $input = Request::all();

 			$customers = $this->repo->getValidRecords($filters);
			if(getScopeId() == config('jp.temp_import_customer_company')) {
				$customers->where('created_at', '>', '2020-05-21 08:00:00');
            }

            $customers = $this->repo->getValidRecords();
            $customers = $customers->get();
            if (!$customers->count()) {
                return ApiResponse::success(['message' => trans('response.error.no_record_to_import')]);
            }
            foreach ($customers as $customer) {
                $customerArray = $customer->toArray();
                if ((getScopeId() != config('jp.temp_import_customer_company')) && $this->isDuplicate($customerArray)) {
                    $tempCustomer = TempImportCustomer::find($customerArray['id']);
                    if($tempCustomer) {
                        $tempCustomer->update(['duplicate' => true]);
                    }
                    continue;
                }
                $this->saveCustomer($customerArray);
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Customers'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function cancel_import()
    {
        set_time_limit(0);
        try {
            $input = Request::all();

            $validator = Validator::make($input, ['type' => 'required|in:valid,invalid,all,duplicate,quickbook']);
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            switch ($input['type']) {
                case 'valid':
                    $customers = $this->repo->getValidRecords();
                    break;
                case 'invalid':
                    $customers = $this->repo->getInvalidRecords();
                    break;
                case 'duplicate':
                    $customers = $this->repo->getDuplicateRecords();
                    break;
                case 'quickbook':
                    $customers = $this->repo->getQuickBookRecords();
                    break;
                default:
                    $customers = $this->repo->get();
                    break;
            }
            $customers->delete();
            Event::fire('JobProgress.Customers.Events.TempImportCustomerDeleted', new TempImportCustomerDeleted($input['type']));
            return ApiResponse::success([
                'message' => trans('response.success.customer_import', ['attribute' => 'canceled'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /customers/import/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $tempCustomer = $this->repo->getById($id);
        try {
            $tempCustomer->delete();
            return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Record'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function export()
    {
        set_time_limit(0);
        $input = Request::all();
        $customers = $this->customerRepo->getFilteredCustomers($input);
        $customers = $customers->with('address', 'billing', 'phones')->get();
        $customers = $this->response->collection($customers, new CustomersExportTransformer);

        Excel::create('Customers', function ($excel) use ($customers) {
            $excel->sheet('sheet1', function ($sheet) use ($customers) {
                $sheet->fromArray($customers['data']);
            });
        })->export('csv');
    }

    /**
     * Print Pdf of customer
     * GET /customers/{id}/pdf_print
     *
     * @param  int $id
     * @return Response
     */
    public function customer_pdf_print($id)
    {
        $customer = Customer::with([
            'address',
            'phones',
            'appointments',
            'flags.color',
            'jobs' => function ($query) {
                if(Auth::user()->isSubContractorPrime()) {
                    $query->own(Auth::id());
                }
                $query->division();
                $query->withoutArchived();
                $query->addScheduleStatus();
            },
            'jobs.workflow',
            'jobs.flags.color',
            'jobs.projects' => function ($query) {
                if(Auth::user()->isSubContractorPrime()) {
                    $query->own(Auth::id());
                }
                $query->division();
                $query->withoutArchived();
            }
        ])->findOrFail($id);
        $company = Company::find($this->scope->id());

        $contents = view('customers.customer_export', [
            'customer' => $customer,
            'company' => $company,
            'company_country_code' => $company->country->code
        ])->render();

        $pdf = PDF::loadHTML($contents)
            ->setPaper('a4')
            ->setOption('no-background', false)
            ->setOption('dpi', 200);
        return $pdf->stream('customer.pdf');
    }

    /**
     * @method  [customers_export] method use for only customer export not a job.
     * @return [pdf] [customer pdf file]
     */
    public function customer_detail_page()
    {
        $input = Request::all();
        $customers = $this->customerRepo->getFilteredCustomers($input);
        $company = Company::find($this->scope->id());
        $flags = Flag::whereFor('customer')->pluck('title', 'id')->toArray();
        $users = User::where('company_id', $this->scope->id())->division()->select('id', DB::raw("CONCAT(first_name,' ',last_name) as fname"))->pluck('fname', 'id')->toArray();
        $customers = $customers->with([
            'address',
            'address.state',
            'phones',
            'rep',
            'billing',
            'appointments',
            'billing.state',
            'todayAppointments',
            'upcomingAppointments',
            'flags.color',
            'referredByCustomer',
            'referredByReferral',
            'secondaryNameContact',
            'jobs' => function ($query) {
                if(Auth::user()->isSubContractorPrime()) {
                    $query->own(Auth::id());
                }
                $query->division();
                $query->withoutArchived();
            }
        ])->get();

        $mode = 'portrait';
        $view = 'customers.customers_export_portrait';
        if (ine($input, 'mode') && $input['mode'] == 'landscape') {
            $mode = 'landscape';
            $view = 'customers.customers_export_landscape';
        }

        $contents = view($view, [
            'customers' => $customers,
            'company' => $company,
            'filters' => $input,
            'flags' => $flags,
            'users' => $users,
            'company_country_code' => $company->country->code
        ]);
        $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation($mode);
        $pdf->setOption('dpi', 200);
        return $pdf->stream('customers.pdf');
    }

    public function import_update()
    {
        set_time_limit(0);
        $input = Request::onlyLegacy('file');
        $validator = Validator::make($input, TempImportCustomer::getImportRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $customers = $this->getCSVData(Request::file('file'));
            foreach ($customers as $customer) {
                $customer = $customer['data'];
                $addresses = Address::where('address', 'Like', '%' . trim($customer['address']['address']) . '%')->whereHas('customer', function ($query) use ($customer) {
                    $query->where('first_name', 'Like', '%' . $customer['first_name'] . '%')->where('last_name', 'Like', '%' . $customer['last_name'] . '%');
                })->update(['zip' => sprintf("%05s", $customer['address']['zip'])]);

                if (!$customer['billing']['same_as_customer_address']) {
                    $billings = Address::where('address', 'Like', '%' . trim($customer['billing']['address']) . '%')->whereHas('customerBilling', function ($query) use ($customer) {
                        $query->where('first_name', 'Like', '%' . $customer['first_name'] . '%')->where('last_name', 'Like', '%' . $customer['last_name'] . '%');
                    })->update(['zip' => sprintf("%05d", $customer['billing']['zip'])]);
                }
            }
            return ApiResponse::success(['message' => "Updation Complete."]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**************************** Private Function ******************************/


    private function getCSVData($file)
    {
        $excel = App::make('excel');
        $filename = $file->getRealPath();
        $import = $excel->load($filename);
        $records = $import->get()->toArray();
        $data = [];
        foreach ($records as $key => $value) {
            $customerData = $this->extractCustomerData($value);
            $data[$key] = $customerData;
        }
        return $data;
    }

    private function extractCustomerData($data)
    {
        $customerData['data'] = $this->mapCustomerInput($data);
        $customerData['data']['address'] = $this->mapAddressInput($data);
        $customerData['data']['job'] = $this->mapJobInput($data);
        $customerData['data']['billing'] = $this->mapBillingAddressInput($data);
        $customerData['data']['phones'] = $this->mapPhonesInput($data);

        $validate = Validator::make($customerData['data'], Customer::validationRules());
        if ($validate->fails()) {
            $customerData['errors'] = $validate->messages()->toArray();
            $customerData['is_valid'] = false;
        } else {
            $customerData['is_valid'] = true;
        }
        if (getScopeId() != config('jp.temp_import_customer_company')&& $customerData['is_valid'] && $this->isDuplicate($customerData) ) {
            $customerData['duplicate'] = true;
        } else {
            $customerData['duplicate'] = false;
        }

        $customerData['company_id'] = $this->scope->id();
        return $customerData;
    }

    private function mapCustomerInput($input = [])
    {
        $map = [
            'first_name',
            'last_name',
            'company_name',
            'property_name',
            'email' => 'email',
            'is_commercial',
            'note',
            'rep_id',
            'canvasser',
    		'call_center_rep'
        ];

        $data = $this->mapInputs($map, $input);

        $data['is_commercial'] = ine($data, 'is_commercial');
        if ($data['is_commercial']) {
            $data['first_name'] = issetRetrun($data, 'company_name') ?: $data['first_name'] .' '.$data['last_name'];;
            $data['company_name'] = "";
            $data['last_name'] = "";
        }

        if(empty($data['last_name'])) {
            $data['full_name'] = $data['first_name'];
        } else {
            $data['full_name'] = $data['first_name'].' '.$data['last_name'];
        }

        if(ine($input, 'salesman')) {
            $rep = User::where('company_id', getScopeId())
        		->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?",[$input['salesman']])
        		->first();
        	if($rep){
        		$data['rep_id'] = $rep->id;
        	}else{
        		$note = ine($data, 'note') ? $data['note']: '';
        		$data['note'] = 'Salesman / Customer Rep: '. $input['salesman'].'.'.$note;
        	}
        }

        if(ine($input, 'lead_source')) {
        	$referral = Referral::where('name', $input['lead_source'])
        		->where('company_id', getScopeId())
        		->first();
        	if(!$referral) {
        		$referral = Referral::create([
        			'name' => $input['lead_source'],
        			'company_id' => getScopeId()
        		]);
        	}
        	$data['referred_by_id'] = $referral->id;
        	$data['referred_by_type'] = 'referral';
        }

        if(ine($input, 'canvasser')) {
			$canvasser = User::where('company_id', getScopeId())
			->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?",[$input['canvasser']])
			->first();
			if($canvasser){
				unset($data['canvasser']);
				$data['canvasser_id'] = $canvasser->id;
			}else{
				$canvasser = ine($data, 'canvasser') ? $data['canvasser']: '';
				$data['canvasser'] = $input['canvasser'];
			}
		}
		  if(ine($input, 'call_center_rep')) {
			$call_center_rep = User::where('company_id', getScopeId())
			->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?",[$input['call_center_rep']])
			->first();
			if($call_center_rep){
				unset($data['call_center_rep']);
				$data['call_center_rep_id'] = $call_center_rep->id;
			}else{
				$call_center_rep = ine($data, 'call_center_rep') ? $data['call_center_rep']: '';
				$data['call_center_rep'] = $input['call_center_rep'];
			}
		}

        return $data;
    }


    /**
     *  map customer locations input data.
     */
    private function mapAddressInput($input = [])
    {
        $addressFields = [
            'address' => 'mailing_address_street',
            'address_line_1' => 'mailing_address_street_2',
            'city' => 'mailing_address_city',
            'state' => 'mailing_address_state',
            'country' => 'mailing_address_country',
            'zip' => 'mailing_address_zip'
        ];

        $address = $this->mapInputs($addressFields, $input);
        return $this->mapStateAndCountry($address);
    }

    private function mapBillingAddressInput($input = [])
    {
        $billing = [];
        $addressFields = [
            'address' => 'billing_address_street',
            'city' => 'billing_address_city',
            'state' => 'billing_address_state',
            'country' => 'billing_address_country',
            'zip' => 'billing_address_zip'
        ];
        $billing = $this->mapInputs($addressFields, $input);
        $billing = $this->mapStateAndCountry($billing);
        if (empty(array_filter(array_values($billing)))) {
            $billing['same_as_customer_address'] = 1;
        } else {
            $billing['same_as_customer_address'] = 0;
        }
        return $billing;
    }

    private function mapPhonesInput($input = [])
    {
        $phones = [];

        if (!ine($input, 'phone') || empty(trim($input['phone']))) {
            $phones[0]['label'] = 'phone';
            $phones[0]['number'] = '0000000000';

            return $phones;
        }

        $numbers = explode(',', str_replace(['(', ')', '-', ' '], '',trim($input['phone'])));
        foreach (arry_fu($numbers) as $key => $number) {
            $number = preg_replace("/[^0-9]+/", "", $number);

            if (empty($number)) {
                continue;
            }

            $number = substr($number, 0, 10);

            if(ine($input, 'extension')){
				$phones[$key]['ext'] = $input['extension'];
			}
            if(ine($input, 'label')) {
				$phones[$key]['label'] = $input['label'];
			} else {
				$phones[$key]['label'] = 'phone';
			}
            $phones[$key]['number'] = $number;
        }
        if (empty($phones)) {
            return null;
        }
        return $phones;
    }

    private function mapInputs($map, $input = [])
    {
        $ret = [];

        // empty the set default.
        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? trim($input[$value]) : "";
            } else {
                $ret[$key] = isset($input[$value]) ? trim($input[$value]) : "";
            }
        }

        return $ret;
    }

    private function mapStateAndCountry($data = [])
    {
        if (!ine($data, 'state')) {
            return $data;
        }
        try {
            $state = State::nameOrCode($data['state'])->first();
            $data['state_id'] = $state->id;
            $data['country_id'] = $state->country_id;
            $data['country'] = $state->country->name;
            return $data;
        } catch (\Exception $e) {
            return $data;
        }
    }

    private function isDuplicate($customerArray)
    {
        // if((getScopeId() == 1160) ) {
        //     return false;
        // }

        $duplicate = $this->customerRepo->isDuplicateRecord($customerArray['data'], $customerArray['data']['phones']);

        if ($duplicate) {
            return true;
        }
        return false;
    }

    private function mapJobInput($data) {
        // if($this->scope->id() != 1160) return [];
        if(!(ine($data, 'job_description')) && empty($data['trades'])) return [];
        $map = [
            'alt_id' => 'job_number',
            'name' => 'job_name',
            'lead_number' => 'lead_number',
            'description' => 'job_description',
            'division'    => 'division',
            'division_id' => 'division_id',
            'job_types'   => 'category_id',
            'estimator_ids' => 'estimator_id',
            'estimator'     => 'estimator',
            'job_move_to_stage' => 'job_move_to_stage',
            'trades'  => 'trades',
            'category'    => 'category',
            'job_flag'	=> 'job_flag',
    		'current_stage'	=> 'current_stage',
    		'other_trade_type_description' => 'other_trade_type_description',
    		'job_price' => 'job_price',
    		'job_note_1' => 'job_note_1',
    		'job_note_2' => 'job_note_2',
    		'job_note_3' => 'job_note_3',
    		'archive'	=> 'archive',
    		'cs_date'	=> 'contract_signed_date',
            'total_received_payemnt' => 'total_payments',
    		'job_schedule_start_date' => 'job_schedule_start_date',
    		'job_schedule_end_date' => 'job_schedule_end_date',
    		'completion_date'   => 'job_completion_date'
        ];
        $jobData = $this->mapInputs($map, $data);
        $this->addressFields = [
            'address'   => 'job_address_street',
            'address_line_1' => 'job_address_street1',
            'city'      => 'job_address_city',
            'state'     => 'job_address_state',
            'country'   => 'job_address_country',
            'zip'       => 'job_address_zip'
        ];
        $tradeDescription = [];
        $trades = [];
        $tradeNames = explode(', ', $jobData['trades']);
		foreach ($tradeNames as $tradeName) {
			$trade = Trade::where('name', $tradeName)->first();
			if(!$trade) {
				$tradeDescription[] = $tradeName;
				$trades[] = 24;
				continue;
			}
			$trades[] = $trade->id;

			if(!ine($data, 'work_type')) {
                continue;
            }

			$workType = JobType::where('company_id', getScopeId())
				->where('trade_id', $trade->id)
				->where('type', JobType::WORK_TYPES)
				->where('name', $data['work_type'])
				->first();

			if(!$workType) {
				$workType = JobType::create([
					'company_id' => getScopeId(),
					'trade_id' => $trade->id,
					'name' => $data['work_type'],
					'type' => JobType::WORK_TYPES
				]);
			}
			$jobData['work_types'][] = $workType->id;
		}

        $jobData['trades'] = $trades;

		$jobData['insurance_details'] = $this->mapJobInsuranceInput($data);

		if($jobData['cs_date']) {
			$date =	str_replace('/', '-', $jobData['cs_date']);
			$date = Carbon::parse($date)->toDateString();
			$jobData['cs_date'] = $date;
		}

		if((bool) $jobData['insurance_details']){
			$jobData['job_types'] = $this->insuranceCategoryId;
			$jobData['insurance'] = true;
		}

		if(empty($tradeDescription) && in_array(24, $trades) && (!ine($jobData, 'other_trade_type_description'))) {
            $tradeDescription[] = 'Other';
        } elseif (ine($jobData, 'other_trade_type_description')) {
			$tradeDescription[] = $jobData['other_trade_type_description'];
		}

        $jobData['same_as_customer_address'] = 1;
		$jobData = array_merge((array)$this->mapAddressInput($data), $jobData);
		$jobData['other_trade_type_description'] = implode(', ', $tradeDescription);

        if(ine($data, 'secondary_name')) {
    		$contactName = preg_split('#\s+#', $data['secondary_name'], 2);
			$jobData['contact']['first_name'] = $contactName[0];
			$jobData['contact']['last_name'] = array_key_exists(1, $contactName) ? $contactName[1] : null;
    	}

        if(!ine($data, 'current_stage')) {
            return $jobData;
        }

		if(strtolower($data['current_stage']) == 'dead'){
			$jobData['follow_up']['mark'] = 'lost_job';
			$jobData['follow_up']['note'] = ine($data, 'dead_lead_reason') ? $data['dead_lead_reason'] : 'lost';

			return $jobData;
		}


		$workflow = Workflow::where('company_id', getScopeId())->orderBy('id', 'desc')->first();

		$newStage = WorkflowStage::where('workflow_id', $workflow->id)
			->where('name', $data['current_stage'])
			->first();

        if(!$newStage) {
            return $jobData;
        }

		$jobData['job_move_to_stage'] = $newStage->code;

		return $jobData;
    }

    public function saveCustomer($customerArray)
    {
    	DB::beginTransaction();
    	try {
            $jobData = [];
            $customerData = null;
    		$existjobData = null;
            $customerArray['data']['stop_db_transaction'] = true;
            $phoneNumber = $customerArray['data']['phones']['0']['number'];

            if(getScopeId() == config('jp.temp_import_customer_company')) {
                $customerData = $this->customerRepo->getDuplicateCustomer($customerArray['data'], $customerArray['data']['phones']);
            }

            if(!$customerData){
				$customerData = $this->execute("App\Commands\CustomerCommand", [
					'input' => $customerArray['data'],
					'geocoding_required' => false,
                ]);
			}

            if(ine($customerArray['data']['job'], 'job_flag')) {
				$jobFlag = $customerArray['data']['job']['job_flag'];
				$flag = Flag::where('title', $jobFlag)
					->where('company_id', getScopeId())
		       		->first();

		      	if(!$flag) {
		       		$flag = Flag::create([
		       			'title' => $jobFlag,
		       			'for'	=> 'job',
		       			'company_id' => getScopeId()
		       		]);
		       	}

		       	$customerArray['data']['job']['flag_ids'] = (array)$flag->id;
			}
			if(isset($customerArray['data']['job']['description'])) {

				if(ine($customerArray['data']['job'], 'alt_id')
					&& getScopeId() == config('jp.temp_import_customer_company')) {

					$jobData = Job::where('customer_id', $customerData->id)
						->where('company_id', getScopeId())
						->whereNull('jobs.deleted_at')
						->where('jobs.alt_id', $customerArray['data']['job']['alt_id'])
						->first();
				}

				if(ine($customerArray['data']['job'], 'name')
					&& getScopeId() == config('jp.temp_import_customer_company')) {

					$jobData = Job::where('customer_id', $customerData->id)
						->where('company_id', getScopeId())
						->whereNull('jobs.deleted_at')
						->where('jobs.name', $customerArray['data']['job']['name'])
						->first();
				}
				$finacialCalulationFlag = false;
				$jobCreateFlag = false;
				$jobSaveFlag = false;

				if(!$jobData) {
					$customerArray['data']['job']['customer_id'] = $customerData->id;
					$jobData = $this->execute("App\Commands\JobCreateCommand", ['input' => $customerArray['data']['job']]);
					$jobCreateFlag = true;
					if(ine($customerArray['data']['job'], 'job_price')) {
						$jobData->amount = $customerArray['data']['job']['job_price'];
						$jobSaveFlag = true;
						$finacialCalulationFlag = true;
					}

					if(ine($customerArray['data']['job'], 'total_received_payemnt')) {
						$meta['date'] = Carbon::now()->toDateTimeString();
						$payment = $customerArray['data']['job']['total_received_payemnt'];
						$this->finacialPayment->createPayment($jobData, $payment, JobPayment::CASH, $meta);
						$finacialCalulationFlag = true;
					}

					if($finacialCalulationFlag) {
						JobFinancialCalculation::updateFinancials($jobData->id);
					}

					if(ine($customerArray['data']['job'], 'archive')) {
						$jobData->archived = Carbon::now();
						$jobSaveFlag = true;
					}
				}

				if(ine($customerArray['data']['job'], 'cs_date')) {
					$jobData->cs_date = $customerArray['data']['job']['cs_date'];
					$jobSaveFlag = true;
				}

				if(ine($customerArray['data']['job'], 'completion_date') && $jobCreateFlag) {
					$jobData->completion_date = $customerArray['data']['job']['completion_date'];
					$jobSaveFlag = true;
				}

				if($jobSaveFlag) {
					$jobData->save();
				}

				if(ine($customerArray['data']['job'], 'job_move_to_stage')) {
					$this->jobProjectService->manageWorkFlow($jobData, $customerArray['data']['job']['job_move_to_stage'], false);
				}

				$jobStage = $jobData->getCurrentStage();
				if(ine($customerArray['data']['job'], 'follow_up') && ine($customerArray['data']['job']['follow_up'], 'mark')) {
					$followUp = $customerArray['data']['job']['follow_up'];
					$this->followUpRepo->saveFollowUp(
						$customerData->id,
						$jobData->id,
						$jobStage['code'],
						$followUp['note'],
						$followUp['mark']
					);
				}

				if(ine($customerArray['data']['job'], 'job_schedule_start_date') && ine($customerArray['data']['job'], 'job_schedule_end_date') && $jobCreateFlag) {
					$title = $customerData->full_name;
					$meta['job_id'] = $jobData->id;

					$trade = Trade::whereIn('id', $customerArray['data']['job']['trades'])
						->pluck('name')->toArray();
					if($trade) {
						$tradeName = implode(' / ', $trade);
						$title .= ' / ' . $tradeName;

						$meta['trade_ids'] = $customerArray['data']['job']['trades'];
					}
					if($jobData->alt_id) {
						$title .= ' / Job # ' . $jobData->alt_id;
					}

					$startDateTime = Carbon::parse($customerArray['data']['job']['job_schedule_start_date'])->toDateTimeString();
					$endDateTime = Carbon::parse($customerArray['data']['job']['job_schedule_end_date'])->endOfDay()->toDateTimeString();
					$schedule = $this->jobScheduleService->importJobSchedule($title, $startDateTime, $endDateTime, Auth::id(), $meta);
				}

				if(ine($customerArray['data']['job'], 'job_note_1')) {
					$this->jobNotesRepo->saveNote($jobData->id,
						$customerArray['data']['job']['job_note_1'],
						$jobStage['code'],
						Auth::id()
					);
				}

				if(ine($customerArray['data']['job'], 'job_note_2')) {
					$this->jobNotesRepo->saveNote($jobData->id,
						$customerArray['data']['job']['job_note_2'],
						$jobStage['code'],
						Auth::id()
					);
				}

				if(ine($customerArray['data']['job'], 'job_note_3')) {
					$this->jobNotesRepo->saveNote($jobData->id,
						$customerArray['data']['job']['job_note_3'],
						$jobStage['code'],
						Auth::id()
					);
				}
			}
			//Customer Index
			Solr::customerIndex($customerData->id);

			DB::table('temp_import_customers')->where('id', $customerArray['id'])
				->delete();
			DB::commit();
		} catch(\Exception $e) {
			DB::rollback();
        }
    }

    private function mapJobInsuranceInput($data)
    {
        $insuranceFields = [
            'insurance_company' => 'insurance_company',
            'insurance_number'  => 'insurance_claim_number',
            'policy_number'     => 'insurance_company_policy_number',
            'phone' 		    => 'insurance_company_phone',
            'fax'   			=> 'insurance_company_fax',
            'email' 			=> 'insurance_company_email',
            'adjuster_name'  	=> 'insurance_adjuster_name',
            'adjuster_phone' 	=> 'insurance_adjuster_phone',
            'adjuster_phone_ext' => 'insurance_adjuster_phone_extension',
            'adjuster_email' 	=> 'insurance_adjuster_email',
            'contingency_contract_signed_date'    => 'insurance_contingency_contract_signed_date',
            'date_of_loss'      => 'insurance_date_of_loss',
            'acv' 				=> 'insurance_acv',
            'deductable_amount' => 'insurance_deductiable_amount',
            'net_claim'    		=> 'insurance_new_claim_amount',
            'depreciation' 		=> 'insurance_depreciation',
            'rcv' 				=> 'insurance_rcv',
            'supplement' 		=> 'insurance_supplement'
        ];
        $insurance = $this->mapInputs($insuranceFields, $data);
        return array_filter($insurance);
    }
}
