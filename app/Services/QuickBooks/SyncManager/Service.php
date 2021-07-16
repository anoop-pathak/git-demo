<?php
namespace App\Services\QuickBooks\SyncManager;

use App\Services\QuickBooks\SyncManager\Customer as QBSyncCustomerManager;
use App\Services\QuickBooks\SyncManager\Batch as QBSyncBatchManager;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\DB;
use App\Models\JobInvoice;
use App\Models\QuickbookSyncCustomer;
use App\Models\SyncRequestAction;
use Auth;
use Queue;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\Exceptions\InvalidSyncCustomerException;
use Sorskod\Larasponse\Larasponse;
use App\Services\Transformers\Optimized\JobsTransformer;
use App\Services\Transformers\QBSyncJobTransformer;
use App\Models\QuickBookMappedJob;
use Carbon\Carbon;
use QuickBooks;
use App\Models\QuickBookTask;
use App\Models\QBOBill;
use App\Models\QuickbookSyncBatch;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomerEntity;

class Service
{
	protected $qbSyncCustomerManager;

	public function __construct(
		QBSyncCustomerManager $qbSyncCustomerManager,
		QBSyncBatchManager $qbSyncBatchManager,
		JobRepository $jobRepo,
		QBDCustomerEntity $qbdCustomerEntity
	)
	{
		$this->qbSyncCustomerManager = $qbSyncCustomerManager;
		$this->qbSyncBatchManager 	 = $qbSyncBatchManager;
		$this->jobRepo 				 = $jobRepo;
		$this->qbdCustomerEntity 	 = $qbdCustomerEntity;
	}

	public function getQBSyncCustomers($batchId, $filters = [])
	{
		return $this->qbSyncCustomerManager->getQBSyncCustomers($batchId, $filters);
	}

	/**
	 * get sync customers with JP origin
	 * @param  Integer | $batchId | Id of Sync Request
	 * @param  Array   | $filters | Array of inputs
	 * @return QueryBuilder $customers
	 */
	public function getJpSyncCustomers($batchId, $filters = [])
	{
		return $this->qbSyncCustomerManager->getJpSyncCustomers($batchId, $filters);
	}

	public function getMatchingCustomers($batchId, $filters = [])
	{
		return $this->qbSyncCustomerManager->getMatchingCustomers($batchId, $filters);
	}

	public function getActionRequiredCustomers($batchId, $filters = [])
	{
		return $this->qbSyncCustomerManager->getActionRequiredCustomers($batchId, $filters);
	}

	public function saveQBSyncBatch($input)
	{
		return $this->qbSyncBatchManager->save($input);
	}

	public function qbSyncBatchListing($input)
	{
		return $this->qbSyncBatchManager->listing($input);
	}

	public function getQBSyncBatchById($id)
	{
		return $this->qbSyncBatchManager->getById($id);
	}

	public function getSyncCustomerStats($batchId, $filters= [])
	{
		return $this->qbSyncCustomerManager->getSyncCustomerStats($batchId, $filters);
	}

	/**
	 * get jobs of a customer of sync request from JP
	 * @param  Customer | $customer | Customer model object
	 * @param  Array 	| $input 	| Array of inputs
	 * @return QueryBuilder $jobs
	 */
	public function getJPSyncJobsOfCustomer($customer, $input)
	{
		$with = $this->getJobIncludes($input);

		$input['customer_id'] = $customer->id;
		$jobs = $this->jobRepo->getJobsQueryBuilder($input);
		$joins = [
			'job_payments',
			'invoices',
			'job_credits',
			'vendor_bills',
			'job_refunds',
		];
		$financialCountFilters = $input;
		$financialCountFilters['include_projects'] = true;
		$financialCountJobs = $this->jobRepo->getJobsQueryBuilder($financialCountFilters, $joins)
			->select(DB::raw("COALESCE(jobs.parent_id, jobs.id) as job_id,
				COUNT(IF(job_invoices.type='job',1,NULL)) as job_invoices_count,
				COUNT(IF(job_invoices.type='change_order',1,NULL)) as change_orders_with_invoice_count,
				COUNT(DISTINCT(job_credits.id)) AS credit_count,
				COUNT(DISTINCT(vendor_bills.id)) AS total_bills,
				COUNT(DISTINCT(job_refunds.id)) AS refund_count,
				COUNT(DISTINCT(job_payments.id)) AS payments_count"))
			->groupBy('job_id');

		$financialCountJobsQuery = generateQueryWithBindings($financialCountJobs);
		$jobs = $jobs->leftJoin(DB::raw("({$financialCountJobsQuery}) as financial_jobs "), 'financial_jobs.job_id', '=', 'jobs.id')
			->groupBy('jobs.id')
			->orderBy('jobs.id','DESC')
			->select(DB::raw("
				jobs.*,
				IFNULL(SUM(financial_jobs.job_invoices_count), 0) as total_job_invoices,
				IFNULL(SUM(financial_jobs.change_orders_with_invoice_count), 0) as total_change_orders_with_invoice,
				IFNULL(SUM(financial_jobs.credit_count), 0) as total_credit_count,
				IFNULL(SUM(financial_jobs.total_bills), 0) as total_bill_count,
				IFNULL(SUM(financial_jobs.refund_count), 0) as total_refund_count,
				IFNULL(SUM(financial_jobs.payments_count), 0) as total_payments
			"));

		if(ine($input, 'includes') && in_array('financial_calculations', (array)$input['includes'])) {
			$this->jobRepo->withFinancials($jobs);
		}

		// $jobs->groupBy('jobs.id')->orderBy('jobs.id','DESC');

		return $jobs;
	}

	/**
	 * get QB jobs of a QB customer
	 * @param  QuickbookSyncCustomer | $customer | Details of QuickbookSyncCustomer
	 * @param  Array 				 | $input 	 | Array of inputs
	 * @return QueryBuilder 		 | $jobs
	 */
	public function getQBSyncJobsOfCustomer($customer, $input)
	{
		$jobs = QBOCustomer::where('company_id', getScopeId())
			->where('level', 1)
			->where('qb_parent_id', $customer->qb_id)
			->orderBy('qbo_customers.qb_id','DESC');

			if(ine($input, 'exclude_qb_ids')){
				$jobs->whereNotIn('qb_id', (array)$input['exclude_qb_ids']);
			}

		return $jobs;
	}

	/**
	 * get jobs of a customer who is linked in JP and QB
	 * @param  Object | $jpCustomer | Customer Model
	 * @param  Object | $qbCustomer | QBOCustomer Model
	 * @param  Array  | $input 		| Array of inputs
	 * @return [type]             [description]
	 */
	public function getQBAndJPCustomerJobs($jpCustomer, $qbCustomer, $jpSortColumn, $qbSortColumn, $sortOrder = SORT_ASC, $input)
	{
		$response = app()->make(Larasponse::class);

		if(ine($input, 'includes')) {
			$response->parseIncludes($input['includes']);
		}

		$customerRes = [];

		//if qb customer's financial exists then add in listing
		$bills = QBOBill::where('company_id', getScopeId())
			->where('qb_customer_id', $qbCustomer->qb_id)
			->pluck('qb_id')
			->toArray();

		if(!empty($bills)){
			$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
		}

		if(empty($bills)){

			$invoices = QuickBooks::getQBEntitiesByParentId($qbCustomer->company_id, $qbCustomer->qb_id, QuickBookTask::INVOICE);

			if(!empty($invoices)){
				$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
			}

			if(empty($invoices)){
				$payments = QuickBooks::getQBEntitiesByParentId($qbCustomer->company_id, $qbCustomer->qb_id, QuickBookTask::PAYMENT);

				if(!empty($payments)){
					$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
				}

				if(empty($payments)){
					$credits = QuickBooks::getQBEntitiesByParentId($qbCustomer->company_id, $qbCustomer->qb_id, QuickBookTask::CREDIT_MEMO);

					if(!empty($credits)){
						$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
					}
				}
			}
		}

		$qbJobs = $this->getQBSyncJobsOfCustomer($qbCustomer, $input);
		$qbJobs = $qbJobs->get();
		$qbJobs = $response->collection($qbJobs, new QBSyncJobTransformer);

		$jpJobs = $this->getJPSyncJobsOfCustomer($jpCustomer, $input);
		$transformer = new JobsTransformer;
		$transformer->setDefaultIncludes([]);

		$jpJobs = $jpJobs->get();
		$jpJobs = $response->collection($jpJobs, $transformer);

		$jpJobs = sortMultiDimArrayByCol($jpJobs['data'], $jpSortColumn, $sortOrder);
		$qbJobs = sortMultiDimArrayByCol($qbJobs['data'], $qbSortColumn, $sortOrder);

		$data = [
			'jp_jobs' => $jpJobs,
			'qb_jobs' => $qbJobs,

		];

		if($customerRes){
			$data['qb_customer'] = $customerRes;
		}

		return $data;
	}

	/**
	 * get jobs of a customer who is linked in JP and QB
	 * @param  Object | $jpCustomer | Customer Model
	 * @param  Object | $qbCustomer | QBOCustomer Model
	 * @param  Array  | $input 		| Array of inputs
	 * @return [type]             [description]
	 */
	public function getActionRequiredCustomerJobs($jpCustomer, $qbCustomer, $jpSortColumn, $qbSortColumn, $sortOrder = SORT_ASC, $input)
	{
		$response = app()->make(Larasponse::class);

		if(ine($input, 'includes')) {
			$response->parseIncludes($input['includes']);
		}
		$customerRes = [];

		//check ghost job exist or not
		$ghostJob = QuickBooks::getGhostJobByQBId($qbCustomer->qb_id);

		if(!$ghostJob){
			//if qb customer's financial exists then add in listing
			$bills = QBOBill::where('company_id', getScopeId())
				->where('qb_customer_id', $qbCustomer->qb_id)
				->pluck('qb_id')
				->toArray();

			if(!empty($bills)){
				$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
			}
			if(empty($bills)){
				$invoices = QuickBooks::getQBEntitiesByParentId($qbCustomer->company_id, $qbCustomer->qb_id, QuickBookTask::INVOICE);

				if(!empty($invoices)){
					$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
				}

				if(empty($invoices)){
					$payments = QuickBooks::getQBEntitiesByParentId($qbCustomer->company_id, $qbCustomer->qb_id, QuickBookTask::PAYMENT);

					if(!empty($payments)){
						$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
					}

					if(empty($payments)){
						$credits = QuickBooks::getQBEntitiesByParentId($qbCustomer->company_id, $qbCustomer->qb_id, QuickBookTask::CREDIT_MEMO);

						if(!empty($credits)){
							$customerRes =  $response->item($qbCustomer, new QBSyncJobTransformer);
						}
					}
				}
			}
		}

		$jpJobs = $this->getJPSyncJobsOfCustomer($jpCustomer, $input);

		$quickBookIds = $jpJobs->pluck('quickbook_id')->toArray();
		$jpJobs = $jpJobs->get();
		$transformer = new JobsTransformer;
		$transformer->setDefaultIncludes([]);
		$jpJobs = $response->collection($jpJobs, $transformer);

		$input['exclude_qb_ids'] =array_filter($quickBookIds);


		$qbJobs = $this->getQBSyncJobsOfCustomer($qbCustomer, $input);
		$qbJobs = $qbJobs->get();
		$qbJobs = $response->collection($qbJobs, new QBSyncJobTransformer);



		$jpJobs = sortMultiDimArrayByCol($jpJobs['data'], $jpSortColumn, $sortOrder);
		$qbJobs = sortMultiDimArrayByCol($qbJobs['data'], $qbSortColumn, $sortOrder);

		$data = [
			'jp_jobs' => $jpJobs,
			'qb_jobs' => $qbJobs,

		];

		if($customerRes){
			$data['qb_customer'] = $customerRes;
		}

		return $data;
	}

	/**
	 * get mapped jobs of a customer who is linked in JP and QB
	 * @param  Object | $jpCustomer | Customer Model
	 * @param  Object | $qbCustomer | QBOCustomer Model
	 * @param  Array  | $input 		| Array of inputs
	 * @return [type]             [description]
	 */
	public function getQBAndJPCustomerMappedJobs($jpCustomer, $qbCustomer, $input)
	{
		$response = app()->make(Larasponse::class);

		if(ine($input, 'includes')) {
			$response->parseIncludes($input['includes']);
		}

		$jpJobs = $this->getJPSyncJobsOfCustomer($jpCustomer, $input);
		$transformer = new JobsTransformer;
		$transformer->setDefaultIncludes([]);
		$jpJobs = $jpJobs->get();
		$jpJobs = $response->collection($jpJobs, $transformer);

		$jpJobs = sortMultiDimArrayByCol($jpJobs['data'], 'updated_at', SORT_DESC);

		return $jpJobs;
	}

	/**
	 * add sync request to queue
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  String 	| $type		| Type of action/tab like QB to JP
	 * @return SyncRequestAction $action
	 */
	public function queueSyncRequest($batchId, $type, $customerIds, $qbCustomerIds = [])
	{
		$data = [
			'company_id'	=> getScopeId(),
			'batch_id'		=> $batchId,
			'auth_user_id'	=> Auth::id(),
			'action_type'	=> $type,
		];

		$batch = QuickbookSyncBatch::find($batchId);

		if($type == SyncRequestAction::JP_TO_QB){
			QuickbookSyncCustomer::where('batch_id', $batchId)
				->whereIn('customer_id', $customerIds)
				->where('origin', QuickbookSyncCustomer::ORIGIN_JP)
				->whereNull('qb_id')
				->where('ignored', false)
				->update(['sync_status' => QuickbookSyncCustomer::SUBMIT_FOR_SYNCHING]);

			$data['customer_ids'] = $customerIds;

			if($batch->connection_type == QuickbookSyncBatch::QBD){
				Queue::connection('qbo')->push('App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb\SyncNowHandler@handle', $data);
			} else{
				Queue::connection('qbo')->push('App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\SyncNowHandler@handle', $data);
			}

		} elseif($type == SyncRequestAction::QB_TO_JP){
			QuickbookSyncCustomer::where('batch_id', $batchId)
				->whereIn('qb_id', $customerIds)
				->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
				->whereNull('customer_id')
				->where('ignored', false)
				->update(['sync_status' => QuickbookSyncCustomer::SUBMIT_FOR_SYNCHING]);

			$data['customer_ids'] = $customerIds;
			if($batch->connection_type == QuickbookSyncBatch::QBD){
				Queue::connection('qbo')->push('App\Services\QuickBookDesktop\QueueHandler\SyncNow\QbToJp\SyncNowHandler@handle', $data);
			} else{
				Queue::connection('qbo')->push('App\Services\QuickBooks\QueueHandler\SyncNow\QbToJp\SyncNowHandler@handle', $data);
			}

		} elseif($type == SyncRequestAction::MATCHING_CUSTOMERS){

			QuickbookSyncCustomer::where('batch_id', $batchId)
				->whereIn('qb_id', $qbCustomerIds)
				->whereIn('customer_id', $customerIds)
				->where('ignored', false)
				->where('action_required', false)
				->update(['sync_status' => QuickbookSyncCustomer::SUBMIT_FOR_SYNCHING]);

			$data['customer_ids'] = $customerIds;
			$data['qb_customer_ids'] = $qbCustomerIds;
			if($batch->connection_type == QuickbookSyncBatch::QBD){
				Queue::connection('qbo')->push('App\Services\QuickBookDesktop\QueueHandler\SyncNow\Mapped\SyncNowHandler@handle', $data);
			} else{
				Queue::connection('qbo')->push('App\Services\QuickBooks\QueueHandler\SyncNow\Mapped\SyncNowHandler@handle', $data);
			}
		} elseif($type == SyncRequestAction::ACTION_REQUIRED){

			QuickbookSyncCustomer::where('batch_id', $batchId)
				->whereIn('qb_id', $qbCustomerIds)
				->whereIn('customer_id', $customerIds)
				->where('ignored', false)
				->where('action_required', true)
				->update(['sync_status' => QuickbookSyncCustomer::SUBMIT_FOR_SYNCHING]);

			$data['customer_ids'] = $customerIds;
			$data['qb_customer_ids'] = $qbCustomerIds;
			if($batch->connection_type == QuickbookSyncBatch::QBD){
				Queue::connection('qbo')->push('App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired\SyncNowHandler@handle', $data);
			} else{
				Queue::connection('qbo')->push('App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired\SyncNowHandler@handle', $data);
			}
		}



		return true;
	}

	/**
	 * get sync request action
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  String 	| $type		| Type of action/tab like QB to JP
	 * @return SyncRequestAction $action
	 */
	public function getSyncRequestAction($batchId, $type)
	{
		$action = SyncRequestAction::where('company_id', getScopeId())
			->where('batch_id', $batchId)
			->where('action_type', $type)
			->first();

		return $action;
	}

	/**
	 * get sync request action
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  String 	| $origin		| Type of action/tab like QB to JP
	 * @return SyncRequestAction $action
	 */
	public function getSyncQueueCustomer($batchId, $type, $customerIds, $qbCustomerIds=[])
	{
		$customer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batchId)
			->where('ignored', false)
			->where('sync_status', QuickbookSyncCustomer::READY_FOR_SYNCHING);

		if($type == QuickbookSyncCustomer::ORIGIN_JP) {
			$customer->whereIn('customer_id', (array)$customerIds)
				->where('origin', $type)
				->whereNull('qb_id');
		}elseif($type == QuickbookSyncCustomer::ORIGIN_QB) {
			$customer->whereIn('qb_id', (array)$customerIds)
				->where('origin', $type)
				->whereNull('customer_id');
		}elseif($type == QuickbookSyncCustomer::MATCHING_CUSTOMERS) {
			$customer->whereIn('qb_id', (array)$qbCustomerIds)
				->whereIn('customer_id', (array)$customerIds)
				->where('action_required', false);
		}elseif($type == QuickbookSyncCustomer::ACTION_REQUIRED) {
			$customer->whereIn('qb_id', (array)$qbCustomerIds)
				->whereIn('customer_id', (array)$customerIds)
				->where('action_required', true);
		}

		if(count($customerIds) != $customer->count()) {
			throw new InvalidSyncCustomerException("Invalid customer id(s) in request.");
		}

		return true;
	}

	public function ignoreOrReinstateSyncCustomer($batchId, $details, $type, $value)
	{
		$details = array_filter(array_unique($details, SORT_REGULAR));

		$qbCustomerIds = array_column($details, 'sync_qb_id');
		$jpCustomerIds = array_column($details, 'sync_customer_id');

		$customer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batchId);

		if($type == QuickbookSyncCustomer::ORIGIN_JP) {
			$customer->whereIn('customer_id', $jpCustomerIds)
				->where('origin', $type)
				->whereNull('qb_id');
		}elseif($type == QuickbookSyncCustomer::ORIGIN_QB) {
			$customer->whereIn('qb_id', $jpCustomerIds)
				->where('origin', $type)
				->whereNull('customer_id');
		}elseif($type == QuickbookSyncCustomer::MATCHING_CUSTOMERS) {
			$customer->whereIn('qb_id', $qbCustomerIds)
				->whereIn('customer_id', $jpCustomerIds);
		}elseif($type == QuickbookSyncCustomer::ACTION_REQUIRED) {
			$customer->whereIn('qb_id', $qbCustomerIds)
				->whereIn('customer_id', $jpCustomerIds);
		}

		if(count($details) != $customer->count()) {
			throw new InvalidSyncCustomerException("Invalid customer id(s) in request.");
		}

		$customer->update(['ignored' => $value]);

		return true;
	}

	/**
	 * mark a matching customer different/same
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  Array 	| $details 	| Array of Customer Ids
	 * @param  String 	| $type 	| Indication of Mark as Same/Different
	 * @return boolean
	 */
	public function markMatchingCustomersDiffOrSame($batchId, $details, $flagDiff = false)
	{
		$details = array_filter(array_unique($details, SORT_REGULAR));

		$qbCustomerIds = array_column($details, 'sync_qb_id');
		$jpCustomerIds = array_column($details, 'sync_customer_id');

		$matchingCustomer = QuickbookSyncCustomer::where('batch_id', $batchId)
			->where('company_id', getScopeId())
			->whereIn('qb_id', $qbCustomerIds)
			->whereIn('customer_id', $jpCustomerIds);

		if(count($details) != $matchingCustomer->count()) {
			throw new InvalidSyncCustomerException("Invalid customer id(s) in request.");
		}

		$matchingCustomer->update(['mapped' => $flagDiff]);

		return true;
	}

	/**
	 * mark a matching customer different
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  Array 	| $details 	| Array of Customer Ids
	 * @param  String 	| $type 	| Indication of Mark as Same/Different
	 * @return boolean
	 */
	public function markMatchingCustomersDiff($batchId, $details)
	{
		$details = array_filter(array_unique($details, SORT_REGULAR));

		$qbCustomerIds = array_column($details, 'sync_qb_id');
		$jpCustomerIds = array_column($details, 'sync_customer_id');

		$matchingCustomer = QuickbookSyncCustomer::where('batch_id', $batchId)
			->where('company_id', getScopeId())
			->whereIn('qb_id', $qbCustomerIds)
			->whereIn('customer_id', $jpCustomerIds);

		if(count($details) != $matchingCustomer->count()) {
			throw new InvalidSyncCustomerException("Invalid customer id(s) in request.");
		}

		$matchingCustomer->delete();

		$jpCustomers = [];
		$qbCustomers = [];
		$date = Carbon::now()->toDateTimeString();
		foreach ($jpCustomerIds as $jpCustomerId) {
			$jpCustomers[] = [
                'customer_id' => $jpCustomerId,
                'origin' => 'jp',
                'batch_id' => $batchId,
                'company_id' => getScopeId(),
                'created_at' => $date,
                'updated_at' => $date,
            ];
		}

		DB::table('quickbook_sync_customers')->insert($jpCustomers);

		foreach ($qbCustomerIds as $qbCustomerId) {
			$qbCustomers[] = [
                'qb_id' => $qbCustomerId,
                'origin' => 'qb',
                'batch_id' => $batchId,
                'company_id' => getScopeId(),
                'created_at' => $date,
                'updated_at' => $date,
            ];
		}

		DB::table('quickbook_sync_customers')->insert($qbCustomers);

		return true;
	}

	/**
	 * Save matching customer
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  Object 	| $jpCustomer 	| JP Customer
	 * @param  Object 	| $qbCustomer 	| QB Customer
	 * @return boolean
	 */
	public function saveMatchingCustomers($batchId, $jpCustomer, $qbCustomer)
	{
		$date = Carbon::now()->toDateTimeString();
		$data = [
		 	'customer_id' => $jpCustomer->customer_id,
		 	'qb_id' => $qbCustomer->qb_id,
            'origin' => 'jp',
            'batch_id' => $batchId,
            'created_by' => Auth::id(),
            'company_id' => getScopeId(),
            'created_at' => $date,
            'updated_at' => $date,
        ];
		DB::table('quickbook_sync_customers')->insert($data);

		$jpCustomer->delete();
		$qbCustomer->delete();

		return true;
	}

	/**
	 * select matching customer financial
	 * @param  Integer 	| $batchId 	| Id of a Sync Request
	 * @param  Array 	| $details 	| Array of Customer Ids
	 * @param Boolean 	| Indication to select JP financial or QBO finacial
	 * @return boolean
	 */
	public function SelectMatchingCustomerFinancial($batchId, $details, $type)
	{
		$details = array_filter(array_unique($details, SORT_REGULAR));

		$qbCustomerIds = array_column($details, 'sync_qb_id');
		$jpCustomerIds = array_column($details, 'sync_customer_id');

		$matchingCustomer = QuickbookSyncCustomer::where('batch_id', $batchId)
			->where('company_id', getScopeId())
			->whereIn('qb_id', $qbCustomerIds)
			->whereIn('customer_id', $jpCustomerIds);

		if(count($details) != $matchingCustomer->count()) {
			throw new InvalidSyncCustomerException("Invalid customer id(s) in request.");
		}

		$matchingCustomer->update(['retain_financial' => $type]);

		return true;
	}

	public function saveMappedJobs($batchId, $details, $customerId, $qbCustomerId, $actionRequiredJob)
	{
		$companyId= getScopeId();
		$createdBy = Auth::user()->id;
		$date = Carbon::now();
		$jobData = [];

		QuickBookMappedJob::where('customer_id', $customerId)
			->where('qb_customer_id', $qbCustomerId)
			->where('company_id', $companyId)
			->where('action_required_job', $actionRequiredJob)
			->delete();

		foreach ($details as $detail) {
			$jobId = ine($detail, 'job_id') ? $detail['job_id'] : null;
			$qbJobId = ine($detail, 'qb_job_id') ? $detail['qb_job_id'] : null;
			if($qbJobId || $jobId){
				$jobData[] =[
					'batch_id' =>$batchId,
					'company_id' =>$companyId,
					'customer_id' =>$customerId,
					'qb_customer_id' =>$qbCustomerId,
					'action_required_job' => $actionRequiredJob,
					'job_id' =>$jobId,
					'qb_job_id' =>$qbJobId,
					'created_by' => $createdBy,
					'created_at' => $date,
					'updated_at' => $date,
				];
			}
		}
		if(!empty($jobData)){
			$mappedJobs = new QuickBookMappedJob;
			$mappedJobs->insert($jobData);
		}

		return true;
	}

	public function getQBCustomerFinancial($customer)
	{
		if(QBDesktopQueue::isAccountConnected()){
			$financials = $this->qbdCustomerEntity->getCustomerAllFinancials($customer->qb_id);
		}else{
			$financials = QuickBooks::getCustomerAllFinancials($customer->qb_id);
		}

		return $financials;
	}

	/***** Private Functions *****/

	private function getJobIncludes($input)
	{
		$with = [];

		$includes = isSetNotEmpty($input, 'includes') ?: [];

		if(!arry_fu($includes)) return $with;

		if(in_array('customer', $includes)) {
			$with[] = 'customer';
		}

		if(in_array('address', $includes)) {
			$with[] = 'address';
		}

		if(in_array('parent', $includes)) {
			$with[] = 'parentJob';
		}

		if(in_array('mapped_qb_job', $includes)) {
			$with[] = 'qbMappedJob.qbJob';
		}

		if(in_array('qb_job', $includes)) {
			$with[] = 'qbJob';
		}

		return $with;
	}
}