<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Validator;
use App\Services\QuickBooks\Exceptions\JobValidationException;
use App\Services\Grid\CommanderTrait;
use Carbon\Carbon;
use App\Models\JobAwardedStage;
use DB;
use Illuminate\Support\Facades\Auth;
use QuickBooks_XML_Parser;
use App\Models\State;
use Exception;
use App\Services\Jobs\JobProjectService;
use App\Models\Job as JobModal;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Models\QBOCustomer;
use App\Models\QBDInvoice;
use App\Models\QBDPayment;
use App\Models\QBDCreditMemo;
use App\Models\QBDBill;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Models\QuickBookDesktopTask;
use App\Models\JobInvoice;
use App\Models\DeletedInvoicePayment;
use App\Models\JobPayment;
use App\Models\VendorBill;
use App\Models\JobCredit;
use App\Models\JobRefund;
use App\Models\ChangeOrder;
use Solr;

class Job extends BaseEntity
{
	use CommanderTrait;

	public function __construct(JobRepository $jobRepository, Settings $settings, CustomerEntity $customer, JobProjectService $jobProjectService)
	{
		$this->jobRepository = $jobRepository;
		$this->settings = $settings;
		$this->customerEntity = $customer;
		$this->jobProjectService = $jobProjectService;
	}

	public function getJobByQbdId($id, array $with = array())
	{
		$job = JobModal::withTrashed()->where('qb_desktop_id', $id)->where('company_id', '=', getScopeId())->first();

		return $job;
	}

	public function getGhostJobByQbdId($qbId)
	{
		$job = JobModal::withTrashed()->where('qb_desktop_id', $qbId)
			->where('ghost_job', 1)
			->where('company_id', getScopeId())
			->first();

		return $job;
	}

	public function mapJobInQuickBooks($jobId, $qbJob)
	{
		$job = JobModal::find($jobId);
		$ghostJob = false;

		if ($qbJob['SubLevel'] == 0) {
			$ghostJob = true;
		}

		$job->qb_desktop_id = $qbJob['ListID'];
		$job->ghost_job =  $ghostJob;
		$job->qb_desktop_sequence_number = $qbJob['EditSequence'];
		$job->save();

		return $job;
	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$job = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CustomerQueryRs');

			foreach ($List->children() as $Customer) {

				$job = [
					'ListID' => $Customer->getChildDataAt('CustomerRet ListID'),
					'EditSequence' => $Customer->getChildDataAt('CustomerRet EditSequence'),
					'Notes' => $Customer->getChildDataAt('CustomerRet Notes'),
					'SubLevel' => $Customer->getChildDataAt('CustomerRet Sublevel'),
					'ParentRef' => $Customer->getChildDataAt('CustomerRet ParentRef ListID'),
					'IsActive' => $Customer->getChildDataAt('CustomerRet IsActive')
				];

				$billAddress = [
					'Addr1' => $Customer->getChildDataAt('CustomerRet BillAddress Addr1'),
					'Addr2' => $Customer->getChildDataAt('CustomerRet BillAddress Addr2'),
					'City' => $Customer->getChildDataAt('CustomerRet BillAddress City'),
					'State' => $Customer->getChildDataAt('CustomerRet BillAddress State'),
					'PostalCode' => $Customer->getChildDataAt('CustomerRet BillAddress PostalCode'),
					'Country' => $Customer->getChildDataAt('CustomerRet BillAddress Country'),
				];

				$shipAddress = [
					'Addr1' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr1'),
					'Addr2' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr2'),
					'City' => $Customer->getChildDataAt('CustomerRet ShipAddress City'),
					'State' => $Customer->getChildDataAt('CustomerRet ShipAddress State'),
					'PostalCode' => $Customer->getChildDataAt('CustomerRet ShipAddress PostalCode'),
					'Country' => $Customer->getChildDataAt('CustomerRet ShipAddress Country'),
				];

				$job['BillAddress'] = $billAddress;

				$job['ShipAddress'] = $shipAddress;
			}
		}

		return $job;
	}

	function create($qbdEntity)
	{
		$mappedInput = $this->reverseMap($qbdEntity);

		$job = $this->createJob($mappedInput);

		$this->linkEntity($job, $qbdEntity, $attachOrigin = true);

		return $job;
	}

	function update($qbdEntity, JobModal $job)
	{
		$mappedInput = $this->reverseMap($qbdEntity, $job);

		if ($qbdEntity['IsActive'] == 'false') {

			$job->qb_desktop_sequence_number = $qbdEntity['EditSequence'];

			$job->save();

			$job->delete();

			return $job;

		} else if ($job->trashed() && $qbdEntity['IsActive'] == 'true') {

			$job->restore();

		} else {

			$job = $this->updateJob($mappedInput);
		}

		$this->linkEntity($job, $qbdEntity);

		return $job;
	}

	public function reverseMap($input, $job = null)
	{
		$meta = $this->mapJobInput($input, $job);

		$meta['address'] = $this->mapAddressInput($meta);

		$meta['billing'] = $this->mapBillingAddressInput($meta);

		return $meta;
	}

	public function mapJobInput($input = array(),  $job = null)
	{
		$map = [
			'qb_desktop_id' => 'ListID',
			'qb_desktop_sequence_number' => 'EditSequence',
			'description' => 'Notes',
			'sub_level' => 'SubLevel',
			'parent_ref' => 'ParentRef'
		];

		$mappedInput = $this->mapInputs($map, $input);

		if($job) {

			$mappedInput['id'] = $job->id;

			$mappedInput['customer_id'] = $job->customer_id;
		}

		return $mappedInput;
	}

	public function mapAddressInput($input = array())
	{

		if (!isset($input['ShipAddress']) && !isset($input['BillAddress'])) {
			return false;
		}

		if (!isset($input['ShipAddress'])) {
			$shipingAddress = $input['BillAddress'];
		} else {
			$shipingAddress = $input['ShipAddress'];
		}

		$addressFields =  [
			'address' 	=> 'Addr1',
			'city' 		=> 'City',
			'state' 	=> 'State',
			'country' 	=> 'Country',
			'zip' 		=> 'PostalCode'
		];

		$billing = $this->mapInputs($addressFields, $shipingAddress);
		$billing = $this->mapStateAndCountry($billing);
		return $billing;
	}

	public function mapBillingAddressInput($input = array())
	{
		if (!isset($input['BillAddress'])) {
			return false;
		}

		$billingAddress = $input['BillAddress'];

		$addressFields =  [
			'address' 	=> 'Addr1',
			'city' 		=> 'City',
			'state' 	=> 'State',
			'country' 	=> 'Country',
			'zip' 		=> 'PostalCode'
		];

		$billing = $this->mapInputs($addressFields, $billingAddress);

		$billing = $this->mapStateAndCountry($billing);

		$billing['same_as_customer_address'] = 0;

		return $billing;
	}

	public function mapStateAndCountry($data = array())
	{
		if (!ine($data, 'state')) {
			$data;
		}

		try {
			$state = State::nameOrCode($data['state'])->first();
			$data['state_id'] = $state->id;
			$data['country_id']	= $state->country_id;
			$data['country'] = $state->country->name;
			return $data;
		} catch (Exception $e) {
			return $data;
		}
	}

	private function mapInputs($map, $input = array())
    {
    	$ret = array();

    	// empty the set default.
    	if(empty($input)) {
    		$input = $this->input;
    	}

    	foreach ($map as $key => $value) {
			if(is_numeric($key)){
				$ret[$value] = isset($input[$value]) ? trim($input[$value]) : "";
			}else{
				$ret[$key] = isset($input[$value]) ? trim($input[$value]) : "";
			}
		}

        return $ret;
	}

	/**
	 *	When job/sub customer is created in the QuickBooks
	 * Create Job or Project in JobProgress
	 */
	public function createJob($job)
	{
		$isParentJop = false;
		$isProject = false;
		$parentJob = null;

		// Parent Job or Just Job
		if ($job['sub_level'] == 1) {
			$isParentJop = true;
		}

		// Parent Job or Just Job
		if($job['sub_level'] == 2) {
			$isProject = true;
		}

		$parentCustomerId = null;

		if ($isParentJop) {
			// If Paarent Job or Just Job
			$parentCustomerId = $job['parent_ref'];
		}

		if($isProject) {

			$parentJob = $this->getJobByQbdId($job['parent_ref']);
			if(!$parentJob){
				throw new Exception("Parent Job not found in JP");
			}
			$customer = $parentJob->customer;
			$parentCustomerId = $customer->qb_desktop_id;
		}

		if ($job['sub_level'] == 0) {
			$parentCustomerId = $job['qb_desktop_id'];
		}

		$jpCustomer = $this->customerEntity->getCustomerByQbdId($parentCustomerId);
		$jobData = [];

		if($parentJob) {

			/**
			 * Because parent and children are added sepratly from Quickbooks
			 * if it's parent is multi job only then project can be added below it.
			 */
			if(!$parentJob->isMultiJob()) {

				throw new Exception("Parent Job is not mark as multi job in JP", 1);
			}

			$jobData['description'] = $this->getQuickbookJobDefaultDescription();
			$jobData['company_id'] = getScopeId();
			$jobData['customer_id'] = $jpCustomer->id;
			$jobData['parent_id'] = $parentJob->id;
			$tradesData = $this->getQuickbookJobDefaultTrade();

			if(!empty($tradesData)){
				$jobData['trades'][] = $tradesData['trade'];

				if($tradesData['trade'] == 24){
					$jobData['other_trade_type_description'] = $tradesData['note'];
				}
			}
		}

		if(empty($jobData)) {

			$tradesData = $this->getQuickbookJobDefaultTrade();

			if (!empty($tradesData)) {

				$jobData['trades'][] = $tradesData['trade'];

				if ($tradesData['trade'] == 24) {

					$jobData['other_trade_type_description'] = $tradesData['note'];
				}
			}

			if(empty($jobData['description'])) {

				$jobData['description'] = $this->getQuickbookJobDefaultDescription();
			}

			$jobData['job_move_to_stage'] = $this->getQuickbookJobDefaultStage();

			$jobData['company_id'] = getScopeId();

			$jobData['customer_id'] = $jpCustomer->id;

			$jobData['same_as_customer_address'] = 1;

			$jobData['contact_same_as_customer'] = 1;

			$validator = Validator::make($jobData, $this->jobValidationRules());

			if ($validator->fails()) {

				$errors = $validator->messages()->toArray();

				throw new JobValidationException();
			}
		}

		$jobData['share_token'] = generateUniqueToken();

		$resJob = $this->execute("App\Commands\JobCreateCommand", ['input' => $jobData]);

		if (ine($jobData, 'job_move_to_stage')) {

			$this->jobProjectService->manageWorkFlow($resJob, $jobData['job_move_to_stage'], false);
		}

		$this->jobRepository->qbGenerateJobNumber($resJob);

		if ($job['sub_level'] == 0) {

			$resJob->ghost_job = 1;
			$resJob->save();
		}

		Solr::jobIndex($resJob->id);

		return $resJob;
	}

	public function updateJob($jobMeta)
	{
		if (!empty($jobMeta['billing'])) {

			$jobMeta['address'] = $jobMeta['billing'];

			$jobMeta['same_as_customer_address'] = 0;
		} else if (!empty($jobMeta['address'])) {

			$jobMeta['same_as_customer_address'] = 0;
		} else {

			$jobMeta['same_as_customer_address'] = 1;
		}

		$job = $this->jobProjectService->saveOrUpdateJobs($jobMeta);

		Solr::jobIndex($job->id);
		return $job;
	}

	public static function jobValidationRules($scopes = [])
	{
		$rules = [
			'trades' => 'required|array',
			'description' => 'required',
		];

		return $rules;
	}

	/**
	 * Get Quickbook Job Default Trade
	 * @return CompanyTrade
	 */

	public function getQuickbookJobDefaultTrade()
	{
		$trades = $this->getDefaulJobTrade(getScopeId());
		return $trades;
	}

	/**
	 * Get Quickbook Job Default Description
	 * @return description
	 */

	public function getQuickbookJobDefaultDescription()
	{
		$description = $this->getDefaulJobDescription(getScopeId());
		return $description;
	}

	/**
	 * Get Quickbook Job Default Stage
	 * @return stage
	 */

	public function getQuickbookJobDefaultStage()
	{
		$stage = $this->getDefaultJobStage(getScopeId());
		return $stage;
	}

	public function getDefaulJobTrade($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->settings->getSettings($companyId);

		$data['trade'] = 24; //By default Trade type id Other
		$data['note'] = '';

		if (
			ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		) {
			$jobSync = $settings['jobs_sync'];

			if (
				ine($jobSync, 'qb_to_jp')
				&& ine($jobSync['qb_to_jp'], 'job_trade')
				&& ine($jobSync['qb_to_jp']['job_trade'], 'trade')
			) {
				$data['trade'] = $jobSync['qb_to_jp']['job_trade']['trade'];

				if (($data['trade'] == 24) && ine($jobSync['qb_to_jp']['job_trade'], 'note')) {
					$data['note'] = $jobSync['qb_to_jp']['job_trade']['note'];
				}
			}
		}

		return $data;
	}

	public function getDefaulJobDescription($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->settings->getSettings($companyId);

		$description = '';

		if (
			ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		) {
			$jobSync = $settings['jobs_sync'];

			if (
				ine($jobSync, 'qb_to_jp')
				&& ine($jobSync['qb_to_jp'], 'job_description')
			) {
				$description = $jobSync['qb_to_jp']['job_description'];
			}
		}

		return $description;
	}

	public function getDefaultJobStage($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->settings->getSettings($companyId);

		$stage = '';

		if (
			ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		) {
			$jobSync = $settings['jobs_sync'];

			if (
				ine($jobSync, 'qb_to_jp')
				&& ine($jobSync['qb_to_jp'], 'job_stage')
			) {
				if (
					ine($jobSync['qb_to_jp']['job_stage'], 'awarded_stage')
					&& ($jobSync['qb_to_jp']['job_stage']['awarded_stage'] == 'true')
				) {
					$stage = JobAwardedStage::getJobAwardedStage($companyId);
				} elseif (ine($jobSync['qb_to_jp']['job_stage'], 'code')) {
					$stage = $jobSync['qb_to_jp']['job_stage']['code'];
				}
			}
		}

		return $stage;
	}

	public function createDeleteFinancialTask($jobId, $meta)
	{
		if(!isset($meta['origin'])) return false;

		$origin = $meta['origin'];
		if($origin == QuickBookDesktopTask::ORIGIN_QBD){
			$this->deleteQBDFinancial($jobId, $meta);
		} elseif($origin == QuickBookDesktopTask::ORIGIN_JP){
			$this->deleteJPFinancial($jobId, $meta);
		}
	}

	private function deleteQBDFinancial($jobId, $meta)
	{
		$meta['origin'] = QuickBookDesktopTask::ORIGIN_QBD;
		$this->createDeleteInvoiceTask($jobId, $meta);
		$this->createDeletePaymentTask($jobId, $meta);
		$this->createDeleteCreditMemoTask($jobId, $meta);
		$this->createDeleteBillTask($jobId, $meta);
	}

	private function createDeleteInvoiceTask($jobId, $meta)
	{
		$invoiceIds = QBDInvoice::where('company_id', getScopeId())
			->where('customer_ref', $jobId)
			->pluck('qb_desktop_txn_id')
			->toArray();

		if(!empty($invoiceIds))
		{
			$meta['action'] = QuickBookDesktopTask::DELETE_FINANCIAL;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_DELETE_FINANCIAL;
			$meta['object'] =  QuickBookDesktopTask::INVOICE;
			foreach ($invoiceIds as $invoiceId) {
				$meta['object_id'] = $invoiceId;
				TaskScheduler::addTask(QUICKBOOKS_IMPORT_INVOICE, $meta['user'], $meta);
			}
		}
	}

	private function createDeletePaymentTask($jobId, $meta)
	{
		$paymentIds = QBDPayment::where('company_id', getScopeId())
			->where('customer_ref', $jobId)
			->pluck('qb_desktop_txn_id')
			->toArray();

		if(!empty($paymentIds))
		{
			$meta['action'] = QuickBookDesktopTask::DELETE_FINANCIAL;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_DELETE_FINANCIAL;
			$meta['object'] =  QuickBookDesktopTask::RECEIVEPAYMENT;
			foreach ($paymentIds as $paymentId) {
				$meta['object_id'] = $paymentId;
				TaskScheduler::addTask(QUICKBOOKS_IMPORT_RECEIVEPAYMENT, $meta['user'], $meta);
			}
		}
	}

	private function createDeleteCreditMemoTask($jobId, $meta)
	{
		$creditIds = QBDCreditMemo::where('company_id', getScopeId())
			->where('customer_ref', $jobId)
			->pluck('qb_desktop_txn_id')
			->toArray();

		if(!empty($creditIds))
		{
			$meta['action'] = QuickBookDesktopTask::DELETE_FINANCIAL;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_DELETE_FINANCIAL;
			$meta['object'] =  QuickBookDesktopTask::CREDIT_MEMO;
			foreach ($creditIds as $creditId) {
				$meta['object_id'] = $creditId;
				TaskScheduler::addTask(QUICKBOOKS_IMPORT_CREDITMEMO, $meta['user'], $meta);
			}
		}
	}

	private function createDeleteBillTask($jobId, $meta)
	{
		$billIds = QBDBill::where('company_id', getScopeId())
			->where('customer_ref', $jobId)
			->pluck('qb_desktop_txn_id')
			->toArray();

		if(!empty($billIds)){
			$meta['action'] = QuickBookDesktopTask::DELETE_FINANCIAL;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_DELETE_FINANCIAL;
			$meta['object'] =  QuickBookDesktopTask::BILL;
			foreach ($billIds as $billId) {
				$meta['object_id'] = $billId;
				TaskScheduler::addTask(QUICKBOOKS_IMPORT_BILL, $meta['user'], $meta);
			}
		}
	}

	private function deleteJPFinancial($jobId, $meta)
	{
		$userId = Auth::id();
		$date = Carbon::now()->toDateTimeString();
		$reason = 'Job:Map- Delete Job Financial';
		$job = JobModal::find($jobId);
		$insertData = [
			'deleted_at' => $date,
			'deleted_by' => $userId,
			'reason' => $reason
		];

		$jobInvoices = JobInvoice::where('job_id', $job->id)
				->where('customer_id', $job->customer_id);

		$invoiceIds = $jobInvoices->pluck('id')->toArray();
		if(!empty(arry_fu($invoiceIds))){

			$jobInvoices->update($insertData);

			$invoicePayments = DB::table('invoice_payments')
				->whereIn('invoice_payments.invoice_id', $invoiceIds)
				->get();
			if(!empty($invoicePayments)){
				$deletedPayment = new DeletedInvoicePayment;
				$deletedPayment->job_id = $job->id;
				$deletedPayment->customer_id = $job->customer_id;
				$deletedPayment->company_id = $job->company_id;
				$deletedPayment->created_by = $userId;
				$deletedPayment->data = json_encode($invoicePayments);
				$deletedPayment->save();
			}

			DB::table('invoice_payments')
				->whereIn('invoice_payments.invoice_id', $invoiceIds)
				->delete();
		}

		JobPayment::where('job_id', $job->id)
			->where('customer_id', $job->customer_id)
			->update($insertData);

		VendorBill::where('job_id', $job->id)
			->where('company_id', $job->company_id)
			->where('customer_id', $job->customer_id)
			->update([
				'deleted_at' => $date,
				'deleted_by' => $userId,
			]);

		JobCredit::where('job_id', $job->id)
			->where('customer_id', $job->customer_id)
			->where('company_id', $job->company_id)
			->update($insertData);


		JobRefund::where('job_id', $job->id)
			->where('customer_id', $job->customer_id)
			->where('company_id', $job->company_id)
			->update($insertData);

		ChangeOrder::where('job_id', $job->id)
			->where('company_id', $job->company_id)
			->whereIn('invoice_id', $invoiceIds)
			->delete();
	}

	public function updateDump($task, $meta)
	{
		$data = $this->dumpMap($meta['xml']);

		if(empty($data)){
            return true;
        }

		if(ine($data, 'is_active') && $data['is_active'] == 'false') {
			$this->deleteInactiveCustomer($task->object_id);
			return true;
		}

		unset($data['is_active']);
		$qbCustomer = QBOCustomer::where([
            'company_id' => getScopeId(),
            'qb_id' => $task->object_id,
        ])->first();

        if($qbCustomer){
            DB::table('qbo_customers')->where('id', $qbCustomer->id)->update($data);
            return true;
        }

		$data['company_id'] = getScopeId();
		$data['created_at'] = Carbon::now()->toDateTimeString();
		$data['qb_id'] = $task->object_id;

        DB::table('qbo_customers')->insert($data);
        return true;
	}

	public function dumpMap($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$entity = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {
			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CustomerQueryRs');
			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($List->children() as $item) {
				$level = $item->getChildDataAt('CustomerRet Sublevel');

				if ($level > 2) {
					continue;
				}

				$addressMeta = [];
				$customnerFinancials = $this->customerEntity->getCustomerAllFinancials($item->getChildDataAt('CustomerRet ListID'));


				$entity = [
					'first_name' =>$item->getChildDataAt('CustomerRet FirstName') ? $item->getChildDataAt('CustomerRet FirstName') : null,
					'last_name' => $item->getChildDataAt('CustomerRet LastName') ? $item->getChildDataAt('CustomerRet LastName') : null,
					'display_name' => $item->getChildDataAt('CustomerRet FullName') ? $item->getChildDataAt('CustomerRet FullName') : null,
					'company_name' => $item->getChildDataAt('CustomerRet CompanyName') ? $item->getChildDataAt('CustomerRet CompanyName') : null,
					'email'        => $item->getChildDataAt('CustomerRet Email') ? $item->getChildDataAt('CustomerRet Email') : null,
					'is_sub_customer' => ($item->getChildDataAt('CustomerRet Sublevel') < 1) ? false : true,
					'qb_parent_id'  =>$item->getChildDataAt('CustomerRet ParentRef ListID') ? $item->getChildDataAt('CustomerRet ParentRef ListID') : null,
					'primary_phone_number' => $item->getChildDataAt('CustomerRet Phone') ? $item->getChildDataAt('CustomerRet Phone') : null,
					'mobile_number' => null,
					'alter_phone_number' => $item->getChildDataAt('CustomerRet Fax') ? $item->getChildDataAt('CustomerRet Fax') : null,
					'meta' => $item->asJSON(),
					'updated_at' => $currentDateTime,
					'qb_creation_date' => Carbon::parse($item->getChildDataAt('CustomerRet TimeCreated'))->toDateTimeString(),
					'qb_modified_date' => Carbon::parse($item->getChildDataAt('CustomerRet TimeModified'))->toDateTimeString(),
					'level' => $item->getChildDataAt('CustomerRet Sublevel') ? $item->getChildDataAt('CustomerRet Sublevel') : null,
					'total_invoice_count' => $customnerFinancials['total_invoice_count'],
					'total_payment_count' => $customnerFinancials['total_payment_count'],
					'total_credit_count' => $customnerFinancials['total_credit_count'],
				];

				if($item->getChildDataAt('CustomerRet BillAddress Addr1')){
					$addressMeta['add1'] = $item->getChildDataAt('CustomerRet BillAddress Addr1');
				}

				if($item->getChildDataAt('CustomerRet BillAddress City')){
					$addressMeta['city'] = $item->getChildDataAt('CustomerRet BillAddress City');
				}

				if($item->getChildDataAt('CustomerRet BillAddress State')){
					$addressMeta['state'] = $item->getChildDataAt('CustomerRet BillAddress State');
				}

				if($item->getChildDataAt('CustomerRet BillAddress PostalCode')){
					$addressMeta['postal_code'] = $item->getChildDataAt('CustomerRet BillAddress PostalCode');
				}

				if($item->getChildDataAt('CustomerRet BillAddress Country')){
					$addressMeta['country'] = $item->getChildDataAt('CustomerRet BillAddress Country');
				}

				if($item->getChildDataAt('CustomerRet IsActive')){
					$entity['is_active'] = $item->getChildDataAt('CustomerRet IsActive');
				}

				$entity['address_meta'] = json_encode($addressMeta);
			}
		}
		return $entity;
	}

	public function validateQBSubCustomer($subCustomer)
	{
		$level = $subCustomer['SubLevel'];

		//if it is a sub customer than return true
		if($level == 1){
			return true;
		}

		$parentRef = $subCustomer['ParentRef'];
		$jpJob = $this->getJobByQbdId($parentRef);

		if(!$jpJob || !$jpJob->isMultiJob()) {
			return false;
		}

		return true;
	}

	private function deleteInactiveCustomer($jobId)
	{
		QBOCustomer::where('company_id', getScopeId())
			->where('qb_id', $jobId)
			->delete();
		return true;
	}
}