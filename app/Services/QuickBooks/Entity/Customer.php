<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\QuickBookRepository;
use App\Services\QuickBooks\Client;
use App\Repositories\CustomerRepository;
use Exception;
use App\Models\Job;
use App\Models\State;
use App\Models\CompanyTrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\QuickBook;
use App\Models\Customer as CustomerModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\QuickBooks\QuickBookService;
use App\Repositories\JobRepository;
use App\Services\QuickBooks\Exceptions\CustomerNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentJobNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobValidationException;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use QuickBooksOnline\API\Facades\Customer as QBOCustomer;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\Grid\CommanderTrait;
use App\Models\QuickBookTask;
use App\Services\Jobs\JobProjectService;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use App\Services\QuickBooks\Exceptions\CustomerDuplicateException;
use App\Services\QuickBooks\Exceptions\CustomerValidationException;
use App\Services\QuickBooks\Exceptions\GhostJobNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\CustomerAccountHandler;
use App\Models\QuickbookUnlinkCustomer;
use Settings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;

class Customer
{
	use CommanderTrait;

    public function __construct(
		QuickBookRepository $repo,
		CustomerRepository $customerRepo,
		Client $client,
        QuickBookService $quickbooksService,
        JobRepository $jobRepository,
        JobProjectService $jobProjectService,
		Context $scope
	) {
		$this->repo         = $repo;
		$this->customerRepo = $customerRepo;
		$this->client       = $client;
        $this->quickbooksService = $quickbooksService;
        $this->jobRepository = $jobRepository;
        $this->jobProjectService = $jobProjectService;
        $this->scope = $scope;
	}

	/**
	 * Create Customer in JP or QB
	 */

    public function create($id, QuickBookTask $task)
    {
		try {

			DB::beginTransaction();

			// If customer is not already synced with JP
			$response = $this->get($id);

			if (!ine($response, 'entity')) {

				throw new Exception("Unable to fetch customer details from QuickBooks");
			}

			$qbCustomer = $response['entity'];

			$qbCustomer = QuickBooks::toArray($qbCustomer);

			// Users can't create sub customers beyond 2nd level
			// 2nd  level is project level
			if ($qbCustomer['Job'] == 'true' && $qbCustomer['Level'] > 2) {

				throw new Exception("Job created at more then 2 level.");
			}

			if ($qbCustomer['Job'] == 'false') {

				$jpCustomer = $this->customerRepo->getByQBId($id);

				// If customer already exists in JobProgress
				if ($jpCustomer) {

					DB::commit();
					return $jpCustomer;

					// Disabled for now..

					// Log::info('Customer Already Exists', [$jpCustomer->id]);

					// throw new CustomerAlreadyExistException(['customer_id' => $qbCustomer['Id']]);
				}

				$customerMeta = $this->extractCustomerData($qbCustomer);

				if (!$customerMeta['is_valid']) {

					Log::info('Invalid Customer Details', [$customerMeta]);

					throw new CustomerValidationException(['customer_id' => $qbCustomer['Id']]);

				} else if ($customerMeta['duplicate']) {

					QuickBooks::saveToStagingArea([
						'object_id' => $qbCustomer['Id'],
						'object_type' => 'Customer',
						'type' => 'duplicate'
					]);

					throw new CustomerDuplicateException(['customer_id' => $qbCustomer['Id']]);
				}

				$customer = $this->jpCreateCustomer($customerMeta);

				DB::commit();

				return $customer;
			}

			/** Job/Project was created */
			if($qbCustomer['Job'] == 'true') {

				$job = QuickBooks::getJobByQBId($qbCustomer['Id']);

				if($job) {

					DB::commit();

					return $job;

					// Log::info('Job already exists', [$job->id, $job->quickbook_id]);

					// throw new JobAlreadyExistException("Job already exists.");
				}

				$job = $this->createJob($qbCustomer);

				DB::commit();

				// $this->jobRepository->qbGenerateJobNumber($job);

				return $job;
			}

			throw new Exception("Unhandled object.", [$task->id]);

		} catch (Exception $e) {

			DB::rollback();

			// Log::error('Unable to create customer', [(string) $e]);

			throw $e;
		}
    }

    public function update($id, $task)
    {
		try {

			DB::beginTransaction();

			$response = $this->get($id);

			if(!ine($response, 'entity')) {
				throw new Exception("Unable to fetch the QuickBooks Customer.");
			}

			$qbCustomer = QuickBooks::toArray($response['entity']);
			$jpCustomer = $this->customerRepo->getByQBId($id);

			if(!$jpCustomer && $qbCustomer['Job'] == 'false') {
				throw new CustomerNotSyncedException(['customer_id' => $qbCustomer['Id']]);
			}

			$job = QuickBooks::getJobByQBId($id);

			if($qbCustomer['Job'] == 'true' && !$job) {

				$customerId = $this->getParentCustomerId($qbCustomer);

				$parentCustomer = $this->customerRepo->getByQBId($customerId);

				if (!$parentCustomer) {
					throw new ParentCustomerNotSyncedException(['parent_customer_id' => $customerId]);
				}

				$jpCustomer = $this->customerRepo->getByQBId($qbCustomer['Id']);
				if($jpCustomer) {
					throw new Exception("Customer already with this Id.");
				}

				throw new JobNotSyncedException(['job_id' => $qbCustomer['Id']]);
			}

			if(!empty($jpCustomer) && $qbCustomer['Job'] == 'false') {

				$customerMapped = $this->extractCustomerData($qbCustomer);

				$customerMapped['customer']['id'] = $jpCustomer->id;

				// Stop duplicate updates and webhook loop
				if($customerMapped['customer']['quickbook_sync_token'] > $jpCustomer->quickbook_sync_token) {

					// Customer made inactive on Quickbooks
					if($qbCustomer['Active'] == 'false') {

						$this->deleteCustomer($jpCustomer->id);

						DB::commit();

						return;

					} else if($jpCustomer->trashed() && $qbCustomer['Active'] == 'true') {

						$this->restoreCustomer($jpCustomer->id);

						DB::commit();

						return;

					} else {

						$customer = $this->updateCustomer($customerMapped['customer'], $customerMapped['customer']['address'], $customerMapped['customer']['phones'], false, $customerMapped['customer']['billing'], false);

						DB::commit();

						return $customer;
					}
				}

			}

			if(!empty($job) && $qbCustomer['Job'] == 'true') {

				// Sub Customer made inactive on Quickbooks
				if($qbCustomer['Active'] == 'false') {

					$job->delete();

					DB::commit();

					return $job;

				} else if($job->trashed() && $qbCustomer['Active'] == 'true') {

					$job->restore();

					DB::commit();

					return;

				} else {

					$jobMeta = $this->reverseMapJobMeta($qbCustomer, $job);

					if(!empty($jobMeta['billing'])) {

						$jobMeta['address'] = $jobMeta['billing'];

						$jobMeta['same_as_customer_address'] = 0;

					} else if(!empty($jobMeta['address'])) {

						$jobMeta['same_as_customer_address'] = 0;
					} else {

						$jobMeta['same_as_customer_address'] = 1;
					}

					$jobMeta['customer_id'] = $job->customer_id;

					$jobMeta['id'] = $job->id;

					$job = $this->jobProjectService->saveOrUpdateJobs($jobMeta);

					DB::commit();

					return $job;
				}
			}

		} catch (Exception $e) {

			DB::rollback();

			// Log::error('Unable to create customer', [(string) $e]);

			throw $e;
		}
    }

    public function get($id)
    {
        return QuickBooks::findById('customer', $id);
	}

	public function getCustomerWithFinancialCounts($id)
	{
		$quickbooks = QuickBooks::findById('customer', $id);
		$count = QuickBooks::getAllFinancialEntitiesCount($id);
        $quickbooks['entity']->TotalInvoiceCount = $count['TotalInvoiceCount'];
        $quickbooks['entity']->TotalPaymentCount = $count['TotalPaymentCount'];
        $quickbooks['entity']->TotalCreditCount  =  $count['TotalCreditCount'];

        return $quickbooks;
	}

	public function import($companyId, $isCommand = false)
	{
		$data = [];

		// $quickbook = Quickbook::where('company_id', $companyId)->first();
		/**
		 * Author Anoop
		 * this is creating an issue in financial update. so decided to update dump at every sync request.
		 */
		// if($quickbook && $quickbook->last_customer_snapshot){
		// 	$dateTime = Carbon::now()->toDateTimeString();
		// 	$nextSnapshotDate = Carbon::parse($quickbook->last_customer_snapshot)->addDay(Quickbook::CUSTOMER_SNAPSHOT_DURATION)->toDateTimeString();

		// 	if($nextSnapshotDate > $dateTime){
		// 		return false;
		// 	}
		// }

		$customers = App::make('App\Services\QuickBooks\Sync\Customer')->getAllCustomers($companyId);
		DB::table('qbo_customers')->where('company_id', getScopeId())->delete();
		$currentDateTime = Carbon::now();
		foreach ($customers as $customer) {
			$phonePrimaryPhone = $customer['PrimaryPhone'];
			$mobilePhone = $customer['Mobile'];
			$fax = $customer['Fax'];

			$faxNumber = $mobileNumber = $alterPhoneNumber = $primaryPhoneNumber = null;
			if (!empty($phonePrimaryPhone)) {
				$primaryPhoneNumber = getNumber($phonePrimaryPhone['FreeFormNumber']);
			}
			if (!empty($mobilePhone)) {
				$mobileNumber = getNumber($mobilePhone['FreeFormNumber']);
			}
			if (!empty($fax)) {
				$faxNumber =  getNumber($fax['FreeFormNumber']);
			}
			$email = $customer['PrimaryEmailAddr']['Address'];

			$data[] = [
				'company_id' => getScopeId(),
				'first_name' => $customer['GivenName'],
				'last_name' => $customer['FamilyName'],
				'display_name' => $customer['DisplayName'],
				'company_name' => $customer['CompanyName'],
				'email'        => $email,
				'is_sub_customer' => $customer['Job'],
				'qb_id' => $customer['Id'],
				'qb_parent_id'  => ine($customer, 'ParentRef') ? $customer['ParentRef'] : null,
				'primary_phone_number' => $primaryPhoneNumber,
				'mobile_number' => $mobileNumber,
				'alter_phone_number' => $faxNumber,
				'meta' => json_encode($customer, true),
				'created_at' => $currentDateTime,
				'updated_at' => $currentDateTime,
				'qb_creation_date' => Carbon::parse($customer['MetaData']['CreateTime'])->toDateTimeString(),
				'qb_modified_date' => Carbon::parse($customer['MetaData']['LastUpdatedTime'])->toDateTimeString(),
				'level' => $customer['Level'],
				'total_invoice_count' => $customer['TotalInvoiceCount'],
				'total_payment_count' => $customer['TotalPaymentCount'],
				'total_credit_count' => $customer['TotalCreditCount'],
				'address_meta' => '',
			];

			if (count($data) == 500) {
				DB::Table('qbo_customers')->insert($data);

				if ($isCommand) {
					echo "500 Records saved in DB" . PHP_EOL;
				}

				$data = [];
			}
		}

		if (!empty($data)) {
			DB::Table('qbo_customers')->insert($data);
			if ($isCommand) {
				echo "All Records saved in DB" . PHP_EOL;
			}
		}
		// if($quickbook){
		// 	$quickbook->last_customer_snapshot = $currentDateTime;
		// 	$quickbook->save();
		// }

	}

	public function createGhostJob($customerQBId)
	{
		try {

			DB::beginTransaction();

			$ghostJob = QuickBooks::getGhostJobByQBId($customerQBId);

			if($ghostJob) {

				Log::info('Ghost Job already exists', [$ghostJob->id]);
				DB::commit();

				/**
				 * Changed by Anoop
				 * since we are trying to create ghost job which seems to be already created and linked.
				 * let's not break the task.. let it pass so that it's child can continue.
				 */

				// throw new Exception("Ghost Job already exists");
				return $ghostJob;
			}

			$customer = CustomerModel::where('quickbook_id', $customerQBId)
				->where('company_id', getScopeId())
				->first();

			if (!$customer) {

				Log::info('Customer not synced with JobProgress', [$customer->id]);

				throw new Exception("Customer not synced with JobProgress.");
			}

			$job = $this->createNewGhostJob($customer);

			if(!$job) {

				Log::info('Unable to create Ghost Job.', [$customer->id]);

				throw new Exception("Unable to create Ghost Job.");
			}

			DB::commit();

			return $job;

		} catch (Exception $e) {

			DB::rollback();

			// Log::error('Unable to create ghost job', [(string) $e]);

			throw $e;
		}
	}

	public function getOrCreateQuickBookJob($job, $meta)
	{
		try {

			//create job & customer & if job is project then create parent job
			$jobQuickbookId = $this->getJobQuickbookId($job);

			$job->quickbook_id = $jobQuickbookId;

			switch ($meta['type']) {
				case 'financials':
					$this->updateJobFinancial($job);
					break;
				case 'invoices_with_financials':
					$this->updateJobFinancialWithInvoice($job);
					break;
			}

			return $job;
		}  catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * Update job financial with invoice
	 * @param  string   $token token
	 * @param  instance $job   job
	 * @return boolean
	 */

	private function updateJobFinancialWithInvoice($job)
	{
		$this->updateJobFinancial($job);

		$paymentIds = [];

		$invoices = $job->invoices()->whereNull('quickbook_invoice_id')->get();

		if($invoices->count()) {

			foreach ($invoices as $invoice) {

				QBInvoice::createOrUpdateInvoice($invoice);

				$pIds = $invoice->jobPayments()->whereNull('quickbook_id')
						->select('job_payments.id')
						->pluck('id')
						->toArray();

				$paymentIds = array_merge($paymentIds, $pIds);
			}
		}

		if(!empty($paymentIds)) {
			$referenceId = $job->customer->quickbook_id;

			QBPayment::paymentsSync($paymentIds, $referenceId);
		}

		return true;
	}

	/**
	 * Update job financials
	 * @param  string $token token
	 * @param  Job    $job   job
	 * @return boolean
	 */
	private function updateJobFinancial($job) {

		$credits = $job->credits()->where(function($query) {
			$query->where('quickbook_id', '=', '')->orWhereNull('quickbook_id');
		})->get();

		$jobQuickbookId = $job->quickbook_id;

		$paymentIds = $job->payments()->whereNull('quickbook_id')
			->whereNull('canceled')
			->pluck('job_payments.id')
			->toArray();

		if($credits->count()) {
			$description = $this->getDefaultJobDesc($job);
			foreach ($credits as $credit) {
				QBCreditMemo::createCreditNote($credit, $description, $jobQuickbookId);
			}
		}

		if(!empty($paymentIds)) {

			$referenceId = $job->customer->quickbook_id;

			QBPayment::paymentsSync($paymentIds, $referenceId);
		}
	}

	private function getDefaultJobDesc($job)
	{
		$trades = $job->trades->pluck('name')->toArray();
		$description = $job->number;
		// Append Other trade type decription if 'Other' trade is associated..
		if(in_array( 'OTHER', $trades) && ($job->other_trade_type_description)) {
			$otherKey = array_search('OTHER', $trades);
			unset($trades[$otherKey]);
			$other  = 'OTHER - ' . $job->other_trade_type_description;
			array_push($trades, $other);
		}

		if($trade = implode(', ', $trades)) {
			$description .= ' / '.$trade;
		}

		return $description;
	}


	/**
	 * Get Job Quickbook Id
	 * @param  Object   $token Token
	 * @param  Instance $job   Job
	 * @return Quickbook Job Id
	 */
	public function getJobQuickbookId($job, $syncCustomer = true)
	{
	 	$jobEntity = [];
		$customer = $job->customer;

		// Update cycle issue in earlier implementation
		if(!empty($job->quickbook_id)) {
			return $job->quickbook_id;
		}

		// Check if customer is synced if not then sync with Quickbooks
		if($syncCustomer && empty($customer->quickbook_id)) {

			try {

				$customer = $this->qbSyncCustomer($customer->id, 'create');
				if(empty($customer->quickbook_id)) {
					throw new Exception('Unable to Create Customer In QuickBooks');
				}

			} catch (Exception $e) {
				throw new Exception($e);
			}
		}

		// Default job will not be synced.
		// Return customer QuickBooks Id.
		if($job->ghost_job) {

			return $customer->quickbook_id;
		}

		if($job->isProject()) {
			$parentJob = $job->parentJob;
			if(!$referenceId = $parentJob->quickbook_id) {
				$referenceId = $this->getParentJobQuickbookId($parentJob);
			}
		} else if($job->isMultiJob()) {
			if(!$referenceId = $job->quickbook_id) {
				$referenceId = $this->getParentJobQuickbookId($job);
			}
			return $referenceId;
		} else {
			$referenceId = $customer->quickbook_id;
		}

		$displayName = $job->getQuickbookDisplayName();
		$quickbookId = $job->quickbook_id;
		$data = $this->getQuickbookCustomer($quickbookId, $displayName);

		$dateTime = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));

		$createdDate = $dateTime->format('Y-m-d');

		$jobEntity = [
			'MetaData' => [
		    	'CreateTime'  => $createdDate,
		    ]
		];

		$jobEntity['Job'] = true;
		$jobEntity['DisplayName']    = removeQBSpecialChars($displayName);
		$jobEntity['BillWithParent'] = true;
		$jobEntity['ParentRef']['value'] = $referenceId;

		$billingAddress = $customer->billing;
		$jobEntity['GivenName']   = removeQBSpecialChars(substr($customer->getFirstName(), 0, 25)); // maximum of 25 char
		$jobEntity['FamilyName']  = removeQBSpecialChars(substr($customer->last_name, 0, 25));
		$jobEntity['CompanyName'] = substr($customer->getCompanyName(), 0, 25);
		$jobEntity['BillAddr'] = [
	        'Line1' => $billingAddress->address,
	        'Line2' => $billingAddress->address_line_1,
	        'City' =>  $billingAddress->city ? $billingAddress->city : '',
	        'Country' => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
	        'CountrySubDivisionCode' => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
	        'PostalCode' => $billingAddress->zip
		];

		$jobEntity['PrimaryEmailAddr'] = [
			"Address" => $customer->email
		];

		$jobEntity["Notes"] = removeQBSpecialChars(substr($job->description, 0, 4000));

	    $jobEntity = array_filter($jobEntity);

		if(ine($data, 'Id')) {

			$jobEntity = array_merge($data, $jobEntity);

			$customer = $this->get($data['Id']);

			$qboCustomer = QBOCustomer::update($customer['entity'], $jobEntity);

			$resultingCustomer = QuickBooks::getDataService()->Update($qboCustomer);

			// we are updating this with model for stoping model events
			Job::where('id', $job->id)->update([
				'quickbook_id'   => $resultingCustomer->Id,
				'quickbook_sync_token' => $resultingCustomer->SyncToken,
				'quickbook_sync' => true
			]);

			return $resultingCustomer->Id;
		}

		$qboCustomer = QBOCustomer::create($jobEntity);

		$resultingCustomer = QuickBooks::getDataService()->Add($qboCustomer);

		// we are updating this with model for stoping model events
		Job::where('id', $job->id)->update([
			'quickbook_id'   => $resultingCustomer->Id,
			'quickbook_sync_token' => $resultingCustomer->SyncToken,
			'quickbook_sync' => true
		]);

		return $resultingCustomer->Id;
	}


	/**
	 * Get Parent Job Quickbook Id
	 * @param  Object   $token Token
	 * @param  Instance $job   Job
	 * @return Int Quickbook Id
	 */
	public function getParentJobQuickbookId($job)
	{
		if(!$job->isMultiJob()) return false;

		$jobEntity   = [];

		$customer    = $job->customer;
		$displayName = $job->getQuickbookDisplayName();
		$quickbookId = $job->quickbook_id;

		$data = $this->getQuickbookCustomer($quickbookId, $displayName);

		$dateTime    = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));
		$createdDate = $dateTime->format('Y-m-d');
		$jobEntity = [
			'MetaData' => [
		    	'CreateTime' => $createdDate,
		    ]
		];

		$jobEntity['Job'] = true;
		$jobEntity['DisplayName'] = removeQBSpecialChars($displayName);
		$jobEntity['BillWithParent'] = true;
		$jobEntity['ParentRef']['value'] = $customer->quickbook_id;

		$billingAddress = $customer->billing;

		$jobEntity['GivenName']   = removeQBSpecialChars(substr($customer->getFirstName(), 0, 25)); // maximum of 25 char
		$jobEntity['FamilyName']  = removeQBSpecialChars(substr($customer->last_name, 0, 25));
		$jobEntity['CompanyName'] = substr($customer->getCompanyName(), 0, 25);

		$jobEntity['BillAddr'] = [
	        'Line1' => $billingAddress->address,
	        'Line2' => $billingAddress->address_line_1,
	        'City'  =>  $billingAddress->city ? $billingAddress->city : '',
	        'Country' => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
	        'CountrySubDivisionCode' => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
	        'PostalCode' => $billingAddress->zip
	    ];

		$jobEntity['PrimaryEmailAddr'] = [
			"Address" => $customer->email
		];

		$jobEntity["Notes"] = removeQBSpecialChars(substr($job->description, 0, 4000));

	    $jobEntity = array_filter($jobEntity);

	    if(ine($data, 'Id')) {
			$jobEntity = array_merge($data, $jobEntity);
		}

		$qboCustomer = QBOCustomer::create($jobEntity);

		$resultingCustomer = QuickBooks::getDataService()->Add($qboCustomer);

		// we are updating this with model for stoping model events
		Job::where('id', $job->id)->update([
			'quickbook_id'   => $resultingCustomer->Id,
			'quickbook_sync' => true
		]);

		$job = Job::find($job->id);

		return $job->quickbook_id;
	}

	/**
	 * Create/Update Customer in QuickBooks
	 * @param $id
	 * @param $operation
	 * @return Customer
	 */

	public function qbSyncCustomer($id, $operation)
	{
		$customer = CustomerModel::findOrfail($id);

		try {

			$reverseDisplayName = null;

			if($customer->is_commercial) {
				$displayName = $customer->first_name.' '.'(' . $customer->id.')';
			} else {
				$displayName = $customer->first_name .' '.$customer->last_name .' '.'(' . $customer->id.')';
				$reverseDisplayName = $customer->last_name .' '.$customer->first_name .' '.'(' . $customer->id.')';
			}

			$quickbookId = $customer->quickbook_id;

			$qbCustomer = $this->qbCustomerExists($quickbookId, $displayName, $reverseDisplayName, $isJob = false);

			$jpCustomer = null;

			if($qbCustomer) {
				$qbId = $qbCustomer->Id;

				$jpCustomer = $this->customerRepo->getByQBId($qbId);

				//If Customer exists but not synced with JobProgress then link it.
				if(!$jpCustomer) {
					$data = [
						'quickbook_id'         => $qbCustomer->Id,
						'quickbook_sync_token' => $qbCustomer->SyncToken,
						'quickbook_sync'       => true,
					];
					CustomerModel::where('id', $customer->id)->update($data);

					$customer->quickbook_id = $qbCustomer->Id;
					$customer->quickbook_sync_token = $qbCustomer->SyncToken;
					$customer->quickbook_sync = true;
					return $customer;
				}
			}

			if($qbCustomer && $operation == 'create') {
				/**
				 * Changed by Ankit
				 * since we are trying to create customer which seems to be already created and linked.
				 * let's not break the task.. let it pass so that it's child can continue.
				 */
				// throw new CustomerAlreadyExistsException('Customer already exists.');
				return $customer;
			}

			$data = [];

			if($operation == 'update') {

				$response = $this->get($quickbookId);

				$existingCustomer = $response['entity'];
			}

			$customerEntity = $this->mapCustomerData($customer, $data);

			if($operation == 'create') {

				$qboCustomer = QBOCustomer::create($customerEntity);

				$resultingCustomer = QuickBooks::getDataService()->Add($qboCustomer);
			}

			if($operation == 'update') {

				$qboCustomer = QBOCustomer::update($existingCustomer, $customerEntity);

				$resultingCustomer = QuickBooks::getDataService()->Update($qboCustomer);
			}

			$data = [
				'quickbook_id'         => $resultingCustomer->Id,
				'quickbook_sync_token' => $resultingCustomer->SyncToken,
				'quickbook_sync'       => true,
			];

			CustomerModel::where('id', $customer->id)->update($data);

			$customer->quickbook_id = $resultingCustomer->Id;
			$customer->quickbook_sync_token = $resultingCustomer->SyncToken;
			$customer->quickbook_sync = true;

			return $customer;
		}  catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}


	/**
	 * Map Customer Data
	 * @param  Instance $customer Customer
	 * @param  Array    $data     [Quickbook Id, Quickbook Sync Token]
	 * @return Array Of Customer Data
	 */
    private function mapCustomerData($customer, $data = array())
	{
		$billingAddress = $customer->billing;
		$firstName = $customer->first_name;
		$lastName  = $customer->last_name;
		$companyName = $customer->company_name;

		if($customer->is_commercial) {
			$firstName = '';
			$lastName = '';
			$companyName = $customer->first_name;
			$displayName = $companyName.' '.'(' . $customer->id.')';
		} else {
			$settings = Settings::get('QUICKBOOK_ONLINE');
			$displayNameFormat = $settings['customer_display_name_format'];

			switch ($displayNameFormat) {
				case QuickBook::LAST_NAME_FIRST_NAME:
					$displayName = $customer->last_name .' '.$customer->first_name .' '.'(' . $customer->id.')';
					break;
				default:
					$displayName = $customer->first_name .' '.$customer->last_name .' '.'(' . $customer->id.')';
					break;
			}
		}
		$dateTime = convertTimezone($customer->created_at, Settings::get('TIME_ZONE'));
		$createdDate = $dateTime->format('Y-m-d');

		$note = '';
		// QB not accpet the note having value greater than 4000 chars. then remove extra content
		if($customer->note) {
			$note = substr($customer->note , 0, 4000);
		}

		$customerEntity = [
			"BillAddr" => [
		        "Line1" => $billingAddress->address,
		        "Line2" => $billingAddress->address_line_1,
		        "City" =>  $billingAddress->city ? $billingAddress->city : '',
		        "Country" => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
		        "CountrySubDivisionCode" => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
		        "PostalCode" => $billingAddress->zip
		    ],
		    "Notes" => $note,
		    "GivenName" => substr($firstName, 0, 25), // maximum of 25 char
		    "FamilyName" => substr($lastName, 0, 25),
		    "CompanyName" => substr($companyName, 0, 50),
		    "DisplayName" => removeQBSpecialChars(substr($displayName, 0, 100)),
		    "PrimaryEmailAddr" => [
		        "Address" => $customer->email
		    ],
		    "MetaData" => [
		    	'CreateTime' => $createdDate
		    ]
		];

		$company = $customer->company;
		$countryCode = $company->country->code;

		foreach ($customer->phones as $phone) {
			$number = phoneNumberFormat($phone->number, $countryCode);
			switch ($phone->label) {
				case 'phone':
					$customerEntity["PrimaryPhone"]["FreeFormNumber"] = $number;
					break;

				case 'cell':
					$customerEntity["Mobile"]["FreeFormNumber"] = $number;
					break;

				case 'fax':
					$customerEntity["Fax"]["FreeFormNumber"] = $number;
					break;

				case 'other':
					$customerEntity["AlternatePhone"]["FreeFormNumber"] = $number;
					break;

				default:
					if(!isset($customerEntity["AlternatePhone"]["FreeFormNumber"])) {
						$customerEntity["AlternatePhone"]["FreeFormNumber"] = $number;
					}
					break;
			}
		}

		$customerEntity = array_filter($customerEntity);
		$customerEntity['Job'] = false;
		$customerEntity['BillWithParent'] = false;

		if(!empty($data)) {
			$customerEntity = array_merge($customerEntity, $data);
		}

		return $customerEntity;
	}

	/**
	 * check if the customer exists in Quickbooks already
	 */

	public function qbCustomerExists($id = null, $displayName = null, $reverseDisplayName = null, $isJob = true)
	{

		$entity = false;

		$displayName = "'" .addslashes(removeQBSpecialChars($displayName)). "'";

		if($reverseDisplayName) {
			$displayName .= ", '".addslashes(removeQBSpecialChars($reverseDisplayName)). "'";
		}

		$query = "SELECT * FROM Customer WHERE DisplayName IN ($displayName)";

		$queryResponse = QuickBooks::getDataByQuery($query);

		if($queryResponse && gettype($queryResponse) == 'array') {

			if($queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPCustomer) {
				$entity = $queryResponse[0];
			}
		}

		return $entity;
	}

    public function jpCreateCustomer($customer)
	{

		$customer = $this->saveCustomer($customer['customer'], $customer['customer']['address'], $customer['customer']['phones'], false, $customer['customer']['billing'], false);
		return $customer;

	}

	public function mapCustomerInQuickBooks($customer, $qbCustomerId)
	{
		$customer->quickbook_id = $qbCustomerId;
		$customer->save();

		$jpUnlinkCustomer = QuickbookUnlinkCustomer::where('company_id', $customer->company_id)
			->where('type', QuickbookUnlinkCustomer::QBO)
			->where('customer_id', $customer->id)
			->first();

		if($jpUnlinkCustomer){
			$jpUnlinkCustomer->delete();
		}

		$qbUnlinkCustomer = QuickbookUnlinkCustomer::where('company_id', $customer->company_id)
			->where('type', QuickbookUnlinkCustomer::QBO)
			->where('quickbook_id', $qbCustomerId)
			->first();

		if($qbUnlinkCustomer){
			$qbUnlinkCustomer->delete();
		}
		//update customer on quickbooks
		$customer = $this->qbSyncCustomer($customer->id, 'update');
		return $customer;
	}

	public function mapJobInQuickBooks($job, $qbJob)
	{
		$qbJob = QuickBooks::toArray($qbJob);

		$ghostJob = false;

		if($qbJob['Job'] == 'false') {
			$ghostJob = true;
		}

		$job->quickbook_id = $qbJob['Id'];
		$job->ghost_job =  $ghostJob;
		$job->quickbook_sync_token = $qbJob['SyncToken'];
		$job->quickbook_sync = True;
		$job->save();

		return $job;
	}

	private function handleDuplicateCustomers()
	{
        /** @todo  hanlde duplicate customer*/
        return 'Duplicate customer found';
	}

	private function deleteCustomer($id)
	{
		$customer = $this->customerRepo->getById($id);
		if($customer) {
			DB::beginTransaction();
			try {
				$customer->delete();

			} catch(Exception $e) {

				DB::rollback();

				return false;
			}

			DB::commit();
			return true;
		}

		return false;
	}

	private function restoreCustomer($id)
	{
		$customer = $this->customerRepo->getDeletedById($id);
		if($customer) {

			DB::beginTransaction();

			try {

				$customer->restore();

			} catch (Exception $e) {

				DB::rollBack();

				return false;
			}

			DB::commit();

			return true;
		}

		return false;
	}


	/** Job Handler */

	/**
	 *	When job/sub customer is created in the QuickBooks
	 * Create Job or Project in JobProgress
	 */
	public function createJob($qbSubCustomer)
	{

		$isParentJop = false;

		$isProject = false;

		$parentJob = null;

		// Parent Job or Just Job
		if($qbSubCustomer['Level'] == 1) {
			$isParentJop = true;
		}

		// Parent Job or Just Job
		if($qbSubCustomer['Level'] == 2) {
			$isProject = true;
		}

		$parentCustomerId = null;

		if($isParentJop) {

			// If Paarent Job or Just Job
			$parentCustomerId = $qbSubCustomer['ParentRef'];
		}

		if($isProject) {

			$parentJob = $this->get($qbSubCustomer['ParentRef']);

			$parentJob = QuickBooks::toArray($parentJob['entity']);

			$parentCustomerId = $parentJob['ParentRef'];
		}

		$jpCustomer = $this->customerRepo->getByQBId($parentCustomerId);

		/**
		 * If parent customer is not synced with JobProgress
		 */

		if(!$jpCustomer) {
			throw new ParentCustomerNotSyncedException(['parent_customer_id' => $parentCustomerId]);
		}

		$job = [];

		// Project added we need parent job from JobProgress
		if($parentJob) {

			$jpParentJob = QuickBooks::getJobByQBId($parentJob['Id']);

			// If parent Job is not synced then sync in JobProgress.
			if(!$jpParentJob) {

				throw new ParentJobNotSyncedException(['parent_job_id' => $parentJob['Id']]);
			}

			/**
			 * Because parent and children are added sepratly from Quickbooks
			 * Check and mark the parent jobs mulijob if it not already
			 * only then project can be added below it.
			 */
			if(!$jpParentJob->isMultiJob()) {

				$jpParentJob->multi_job = 1;
				$jpParentJob->save();
			}

			$job['description'] = $this->getQuickbookJobDefaultDescription();
			$job['company_id'] = getScopeId();
			$job['customer_id'] = $jpCustomer->id;
			$job['parent_id'] = $jpParentJob->id;
			$tradesData = $this->getQuickbookJobDefaultTrade();

			if(!empty($tradesData)){
				$job['trades'][] = $tradesData['trade'];

				if($tradesData['trade'] == 24){
					$job['other_trade_type_description'] = $tradesData['note'];
				}
			}
			$job['quickbook_id'] = $qbSubCustomer['Id'];
			$job['origin'] = QuickBookTask::ORIGIN_QB;
			$job['quickbook_sync'] = true;
			$job['quickbook_sync_token'] = $qbSubCustomer['SyncToken'];
		}

		if(empty($job)) {

			$tradesData = $this->getQuickbookJobDefaultTrade();

			if(!empty($tradesData)){

				$job['trades'][] = $tradesData['trade'];

				if($tradesData['trade'] == 24) {

					$job['other_trade_type_description'] = $tradesData['note'];
				}
			}

			$job['name'] = $this->getQuickbookJobDisplayName($qbSubCustomer['Id']);

			$job['description'] = $this->getQuickbookJobDefaultDescription();

			$job['job_move_to_stage'] = $this->getQuickbookJobDefaultStage();

			$job['company_id'] = getScopeId();

			$job['customer_id'] = $jpCustomer->id;

			$job['same_as_customer_address'] = 1;

			$job['contact_same_as_customer'] = 1;

			$job['quickbook_id'] = $qbSubCustomer['Id'];

			$job['quickbook_sync'] = true;

			$job['origin'] = QuickBookTask::ORIGIN_QB;

			$job['quickbook_sync_token'] = $qbSubCustomer['SyncToken'];

			$validator = Validator::make($job, $this->jobValidationRules());

			if($validator->fails()) {

				$errors = $validator->messages()->toArray();

				Log::info("Job details are not valid", $errors);

				throw new JobValidationException();
			}
		}

		$job['share_token'] = generateUniqueToken();

		$resJob = $this->execute("App\Commands\JobCreateCommand", ['input' => $job]);

		if(ine($job, 'job_move_to_stage')) {

			$this->jobProjectService->manageWorkFlow($resJob, $job['job_move_to_stage'], false);
		}

		// $this->jobRepository->qbGenerateJobNumber($resJob);

		return $resJob;
	}

	public static function jobValidationRules($scopes = [])
    {
        $rules = [
            'trades' => 'required|array',
            'description' => 'required',
		];

        return $rules;
    }

	/** @todo
	 * Get Quickbook Display Name
	 * @return Display Name
	 */

	public function getQuickbookJobDisplayName($subCustomerId)
	{
		$displayName = 'Job # '. $subCustomerId;

		return $displayName;
	}

	/**
	 * Get Quickbook Default Job Description
	 * This job is created to handle financials at customer level and this will not be synced to QuickBooks
	 */

	public function getDefaultJobDescription($customerId)
	{
		$displayName = 'JobProgress Default Job # '. $customerId;

		return $displayName;
	}

	/**
	 * Get Quickbook Default Job Trade
	 */

	public function getDefaultJobTrade()
	{
		$otherTrade = CompanyTrade::where('company_id', getScopeId())
			->where('trade_id', 24)
			->first();

		if(!$otherTrade) {

			$companyTrade = new CompanyTrade;

			$companyTrade->company_id = getScopeId();

			$companyTrade->trade_id = 24; // other trade id

			$companyTrade->color = '#fff'; // other trade id

			$companyTrade->save();

			return $companyTrade;
		}

		return $otherTrade;
	}

	/**
	 * Get Quickbook Job Default Trade
	 * @return CompanyTrade
	 */
	public function getQuickbookJobDefaultTrade()
	{
		$trades = Quickbooks::getDefaulJobTrade(getScopeId());
		return $trades;
	}

	/**
	 * Get Quickbook Job Default Description
	 * @return description
	 */
	public function getQuickbookJobDefaultDescription()
	{
		$description = Quickbooks::getDefaulJobDescription(getScopeId());

		return $description;

	}

	/**
	 * Get Quickbook Job Default Stage
	 * @return stage
	 */
	public function getQuickbookJobDefaultStage()
	{
		$stage = Quickbooks::getDefaultJobStage(getScopeId());

		return $stage;

	}

	/** Job Handler end */

	public function saveCustomer($customerData,
		$addressData,
		$phones,
		$isSameAsCustomerAddress,
		$billingAddressData = null,
		$geocodingRequired = false
	) {

		$existingCustomer = ine($customerData, 'id'); // edit case ..

		if($addressData) {
			ksort($addressData);
		}

		if($billingAddressData) {
			ksort($billingAddressData);
		}

		$addressId = $this->customerRepo->qbSaveAddress($addressData, $geocodingRequired, $existingCustomer);

		$customerData['company_id'] = getScopeId();

		$billingAddressId = $addressId;

		if(!$isSameAsCustomerAddress) {
			$billingAddressId = $this->customerRepo->qbSaveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer);
		} else {
			$this->customerRepo->qbDeleteBillingAddress($billingAddressData, $addressId);
		}

		$customerData['address_id'] = $addressId;

		$customerData['billing_address_id'] = $billingAddressId;

		$customerData['solr_sync'] = false;

		$customerData['quickbook_sync'] = true;

		$customerData['origin'] = QuickBookTask::ORIGIN_QB;

		$customerData['created_by'] = Auth::user()->id;

		$customer = CustomerModel::create($customerData);

		$this->customerRepo->qbAddPhones($phones,$customer->id);

		return $customer;
	}

	public function updateCustomer($customerData,
		$addressData,
		$phones,
		$isSameAsCustomerAddress,
		$billingAddressData = null,
		$geocodingRequired = false
	) {

		$existingCustomer = ine($customerData, 'id');

		if($addressData) {
			ksort($addressData);
		}

		if($billingAddressData) {
			ksort($billingAddressData);
		}

		$addressId = $this->customerRepo->qbSaveAddress($addressData, $geocodingRequired, $existingCustomer);

		$billingAddressId = $addressId;

		if(!$isSameAsCustomerAddress) {
			$billingAddressId = $this->customerRepo->qbSaveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer);
		}else {
			$this->customerRepo->qbDeleteBillingAddress($billingAddressData, $addressId);
		}

		$customerData['address_id'] = $addressId;

		$customerData['billing_address_id'] = $billingAddressId;

		$customer = CustomerModel::find($customerData['id']);

		$customer->update($customerData);

		$customer->phones()->delete();

		$this->customerRepo->qbAddPhones($phones, $customer->id);

		return $customer;
	}

	public function extractCustomerData($data)
	{
		$meta = [];

		$meta['duplicate'] = false;
		$meta['is_valid']  =  true;
		$meta['errors']    =  null;

		$customer = $this->mapCustomerInput($data);
		$customer['address'] = $this->mapAddressInput($data);
		$customer['billing'] = $this->mapBillingAddressInput($data);
		$customer['phones']  = $this->mapPhonesInput($data);

		$customerName = $customer['first_name'] . ' ' . $customer['last_name'];

		$validate = Validator::make($customer, CustomerModel::validationRules([]));

		if($validate->fails()) {

			$meta['errors'] = $validate->messages()->toArray();
			$meta['is_valid'] = false;
		}

		if ($meta['is_valid'] && $this->isDuplicate(
				$customer['phones'],
				$customer['email'],
				$customerName,
				$customer['company_name']
			)
		) {
			$meta['duplicate'] = true;
		}

		$meta['customer']  = $customer;
		$meta['company_id'] = getScopeId();

		return $meta;
	}

	public function mapCustomerInput($input = array())
	{
    	$map = [
    		'quickbook_id' => 'Id',
    		'first_name' => 'GivenName',
    		'last_name'  => 'FamilyName',
    		'company_name' => 'CompanyName',
    		'quickbook_sync_token' => 'SyncToken',
    		'note'         => 'Notes',
    		'display_name' => 'DisplayName'
		];

		$customer = $this->mapInputs($map,$input);
		//for save dirty record i.e no first_name, last_name
		$displayNameArray = explode(" ", $customer['display_name'], 2);

		$firstName = $displayNameArray[0];
		$lastName = isset($displayNameArray[1]) ? $displayNameArray[1] : $displayNameArray[0];

		if(!ine($customer, 'first_name')){
			$customer['first_name'] = $firstName;
		}

		if(!ine($customer, 'last_name')){
			$customer['last_name'] = $lastName;
		}

		$customer['email'] = null;

		//check if customer has company name than make it as commercial.
		$customer['is_commercial'] = ine($customer, 'company_name');
		if($customer['is_commercial']) {
			$customer['first_name'] =  issetRetrun($customer, 'company_name') ?: $customer['first_name'] .' '.$customer['last_name'];
			$customer['company_name'] = "";
			$customer['last_name'] = "";
		}

		if(isset($input['PrimaryEmailAddr']['Address'])) {
			$emails = explode(",",  str_replace(' ', '', $input['PrimaryEmailAddr']['Address']));
			$customer['email']	= $emails[0];

			if(count($emails) > 1){
				unset($emails[0]);
				$customer['additional_emails'] = array_values($emails);
			}
		}

		return $customer;
    }

	/**
     *	map customer locations input data.
     */

    public function mapAddressInput( $input = array() ) {

    	if(!isset($input['ShipAddr']) && !isset($input['BillAddr'])) {
    		return false;
    	}

    	if(!isset($input['ShipAddr'])) {
    		$shipingAddress = $input['BillAddr'];
    	} else {
    		$shipingAddress = $input['ShipAddr'];
    	}
    	$addressFields =  [
    		'address' 	=> 'Line1',
    		'city' 		=> 'City',
    		'state' 	=> 'CountrySubDivisionCode',
    		'country' 	=> 'Country',
    		'zip' 		=> 'PostalCode'
    	];

    	$billing = $this->mapInputs($addressFields, $shipingAddress);
		$billing = $this->mapStateAndCountry($billing);

    	return $billing;
    }

    public function mapBillingAddressInput( $input = array() ) {
    	if(!isset($input['BillAddr'])) {
    		return false;
    	}
    	$billingAddress = $input['BillAddr'];
    	$addressFields =  [
    		'address' 	=> 'Line1',
    		'city' 		=> 'City',
    		'state' 	=> 'CountrySubDivisionCode',
    		'country' 	=> 'Country',
    		'zip' 		=> 'PostalCode'
    	];

    	$billing = $this->mapInputs($addressFields, $billingAddress);
    	$billing = $this->mapStateAndCountry($billing);
		$billing['same_as_customer_address'] = 0;
    	return $billing;
    }

    private function mapPhonesInput( $input = array() ) {

    	$phones = [];
    	//deafault phone number if number not found
    	$phones[0]['label'] = 'phone';
    	$phones[0]['number'] = 1111111111;
		$key = 0;

		if(isset($input['PrimaryPhone']['FreeFormNumber'])) {
    		$number = preg_replace('/\D+/', '', $input['PrimaryPhone']['FreeFormNumber']);
			if(strlen($number) == 10) {
	    		$phones[$key]['label'] = 'phone';
				$phones[$key]['number'] = $number;
				$key++;
			}
    	}

    	if(isset($input['Mobile']['FreeFormNumber'])) {
    		$number = preg_replace('/\D+/', '', $input['Mobile']['FreeFormNumber']);
			if(strlen($number) == 10) {
	    		$phones[$key]['label'] = 'cell';
				$phones[$key]['number'] = $number;
				$key++;
			}
    	}

    	if(isset($input['Fax']['FreeFormNumber'])) {
    		$number = preg_replace('/\D+/', '', $input['Fax']['FreeFormNumber']);
			if(strlen($number) == 10) {
	    		$phones[$key]['label'] = 'fax';
				$phones[$key]['number'] = $number;
				$key++;
			}
		}

		if(isset($input['AlternatePhone']['FreeFormNumber'])) {

			$number = preg_replace('/\D+/', '', $input['AlternatePhone']['FreeFormNumber']);

			if(strlen($number) == 10) {

	    		$phones[$key]['label'] = 'other';
				$phones[$key]['number'] = $number;
				$key++;
			}
    	}

		return $phones;
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

    public function mapStateAndCountry($data = array())
    {
    	if(! ine($data, 'state')) {
    		$data;
    	}

    	try {
    		$state = State::nameOrCode($data['state'])->first();
    		$data['state_id'] = $state->id;
    		$data['country_id']	= $state->country_id;
    		$data['country'] = $state->country->name;
    		return $data;
    	} catch(Exception $e) {
    		return $data;
    	}
    }

	/**
	 * Check if Quickbook Customer is duplicate
	 * @return Boolean
	 */

    public function isDuplicate($phones = [], $fullName, $email, $companyName)
    {
		$phoneList = [];

		foreach ($phones as $phone) {

			if(ine($phone, 'number')) {

				$phoneList[] = $phone['number'];
			}
		}

		$customer = $this->customerRepo->findMatchingQBCustomer($phoneList, $fullName, $email, $companyName);

    	return (Boolean) $customer;
	}

	/**
	 * Get Quickbook Customer
	 * @param  Object $token       Token
	 * @param  Int    $id          Customer Id
	 * @param  String $displayName Customer Display Name
	 * @return Array of ['Customer quickbook id', 'Quickbook Sync Token']
	 */
	public function getQuickbookCustomer($id = null, $displayName = null, $reverseDisplayName = null, $isJob = true)
	{
		if(!$id && !$displayName) return false;

		$entity = false;

		$displayName = "'" .addslashes(removeQBSpecialChars($displayName)). "'";
		if($reverseDisplayName) {
			$displayName .= ", '".addslashes(removeQBSpecialChars($reverseDisplayName)). "'";
		}

		$query = "SELECT * FROM Customer WHERE DisplayName IN ($displayName)";

		$param = [
			'query' => $query,
		];

		$queryResponse = $this->dataExist($param['query']);

		if(empty($queryResponse) && ($id)) {

			$query = "SELECT *  FROM  Customer WHERE Id = '".$id."'";

			if($isJob) {
				$query .= ' AND job = true';
			} else {
				$query .= ' AND job = false';
			}

			$param = [
				'query' => $query
			];

			$queryResponse = $this->dataExist($param['query']);
		}

		if(!empty($queryResponse)
			&& gettype($queryResponse) == 'array'
			&& $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPCustomer) {
			$entity['Id']        = (int)$queryResponse[0]->Id;
			$entity['SyncToken'] = (int)$queryResponse[0]->SyncToken;
		}

		return $entity;
	}

	/**
	 * Check Data Exist On Quickbook
	 * @param  Arary $param [query]
	 * @return Array Of Response
	 */
	private function dataExist($param)
	{
		$item = Quickbooks::getDataByQuery($param);

		/** NUll means data dose not exists */

		if($item == null) {

			return false;
		}
		return $item;
	}

	/**
	 * Get Customer Quickbook Id or Create customer then return it
	 * @param  Object   $token    token
	 * @param  Instance $customer Customer
	 * @return Customer Quickbook Id
	 */
	public function getCustomerQuickbookId($customer)
	{
		try {

			if(!empty($customer->quickbook_id)) {
				return $customer->quickbook_id;
			}

			// create Customer in QB
			$customer = $this->qbSyncCustomer($customer->id, 'create');

			return $customer->quickbook_id;

		}  catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	public function reverseMapJobMeta($qbCustomer)
	{
		$inputMap = [];

		$inputMap['quickbook_sync_token'] = $qbCustomer['SyncToken'];

		$inputMap['description'] = $qbCustomer['Notes'];

		$inputMap['billing'] = [
			'address' => $qbCustomer['BillAddr']['Line1'],
			'address_line_1' => $qbCustomer['BillAddr']['Line2'],
			'city' => $qbCustomer['BillAddr']['City'],
			'country' => $qbCustomer['BillAddr']['Country'],
			'state' => $qbCustomer['BillAddr']['CountrySubDivisionCode'],
			'zip' => $qbCustomer['BillAddr']['PostalCode'],
		];

		$inputMap['address'] = $this->mapAddressInput($qbCustomer);

		$inputMap['billing'] = $this->mapBillingAddressInput($qbCustomer);

		return $inputMap;
	}

	/**
	 * 	Get Default Job or create it
	 * 	This job is created to just to manage all the finacials that are created on QuickBooks without any
	 * 	Job but at the customer level
	 */
	public function createNewGhostJob($customer)
	{

		$job = QuickBooks::getGhostJobByQBId($customer->quickbook_id);

		if($job) {

			Log::info('Ghost Job already exists', [$job->id]);

			/**
			 * Changed by Ankit
			 * since we are trying to create ghost job which seems to be already created and linked.
			 * let's not break the task.. let it pass so that it's child can continue.
			 */

			// throw new Exception("Ghost Job already exists");
			return $job;
		}

		if(!$job) {

			$job = [];

			$tradesData = $this->getQuickbookJobDefaultTrade();

			if (!empty($tradesData)) {

				$job['trades'][] = $tradesData['trade'];

				if ($tradesData['trade'] == 24) {

					$job['other_trade_type_description'] = $tradesData['note'];
				}
			}

			$job['description'] = $this->getQuickbookJobDefaultDescription();

			$job['job_move_to_stage'] = $this->getQuickbookJobDefaultStage();

			$job['company_id'] = getScopeId();

			$job['customer_id'] = $customer->id;

			$job['same_as_customer_address'] = 1;

			$job['contact_same_as_customer'] = 1;
			$job['quickbook_id'] = $customer->quickbook_id;
			$job['quickbook_sync'] = true;
			$job['origin'] = QuickBookTask::ORIGIN_QB;

			$resJob = $this->execute("App\Commands\JobCreateCommand", ['input' => $job]);

			if (ine($job, 'job_move_to_stage')) {

				$this->jobProjectService->manageWorkFlow($resJob, $job['job_move_to_stage'], false);
			}

			$resJob->ghost_job = 1;

			$resJob->save();

			if(!empty($job['trades'])) {

				$resJob->trades()->sync(arry_fu($job['trades']));
			}

			// $this->jobRepository->qbGenerateJobNumber($resJob);

			return $resJob;
		}
	}

	/**
	 * Get parent customer.
	 */

	public function getParentCustomerId($customer)
	{
		$level = $customer['Level'];

		$isJob = $customer['Job'];

		$isProject = false;

		if($level > 2) {
			throw new Exception("Jobs created beyond 2 level are not handled");
		}

		if($level == 2) {
			$isProject = true;
		}

		$parentCustomerId = null;

		// If not created on customer level
		if(!empty($level) && $isJob == 'true' && $isProject == 'true' ) {

			$parentJobSubCustomerId = $customer['ParentRef'];

			$rootCustomer = $this->get($parentJobSubCustomerId);

			if(!ine($rootCustomer, 'entity')) {

				throw new Exception("Parent Customer not found on QuickBooks");
			}

			$parentCustomerId = $rootCustomer['entity']->ParentRef;

		} else if(!empty($level) && $isJob == 'true') {

			$parentCustomerId = $customer['ParentRef'];
		} else {

			$parentCustomerId = $customer['Id'];
		}

		return $parentCustomerId;
	}


	/**
	 *	Get or create customer in JobProgress.
	 */
	public function getOrCreateCustomer($qbCustomerId)
	{
		try {

			$jpCustomer = $this->customerRepo->getByQBId($qbCustomerId);

			return $jpCustomer;

		} catch (Exception $e) {

			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Get or create job in JobProgress
	 */

	public function getJob($customer, $jpCustomer = null, $isMultiJob = false)
	{
		$job = null;

		if (!empty($customer['Level']) && $customer['Job'] == 'true') {

			$job = QuickBooks::getJobByQBId($customer['Id']);
		}

		/**
		 * If financial was created on Parent customer not on job.
		 */
		if (empty($customer['Level']) && $jpCustomer && $customer['Job'] == 'false') {

			$job = QuickBooks::getGhostJobByCustomerId($jpCustomer->id);

			if(!$job) {

				throw new GhostJobNotSyncedException(['customer_id' => $jpCustomer->quickbook_id]);
			}
		}

		return $job;
	}

	/**
	 * Check if job has projects
	 */

	public function getProjects($qbId)
	{
		/**
		 * Used Query and foreach becase ParentRef is not queryable in QuickBoooks
		 */
		$query = "SELECT ParentRef FROM Customer Where Job = true";

		$response = QuickBooks::getDataByQuery($query);

		if(QuickBooks::isValidResponse($response, '\QuickBooksOnline\API\Data\IPPCustomer')) {

			if(!empty($response) && is_array($response)) {

				foreach($response as $customer) {

					// Job has Projects
					if($customer->ParentRef == $qbId) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public function getMatchingRecords($data, $phones)
	{
    	$phones = array_column($phones, 'number');

		$edit = ine($data,'id') ? $data['id'] : 0;

    	if(ine($data, 'email')) {

	    	$customer = \Customer::whereEmail($data['email'])
	    		->where('id', '!=', $edit)
	    		->whereHas('phones', function($q) use ($phones) {
	    			$q->whereIn('number', $phones);
				})->first();
    	} else {

	    	$customer = \Customer::where('id', '!=', $edit)
	    		->whereFirstName($data['first_name'])
	    		->whereLastName($data['last_name'])
	    		->whereHas('phones', function($q) use ($phones) {
	    			$q->whereIn('number', $phones);
	    		})->first();
    	}

		if($customer) {
			return $customer->id;
		}

		return [];
	}

	public function syncJobToQuickBooks($jobId, $meta)
	{
		Log::info('Job Create start -(syncJobToQuickBooks) QuickBooks', [$jobId, $meta]);

		Log::info('Job Create -(syncJobToQuickBooks) QuickBooks', [$jobId, $meta]);

		if(!$jobId || empty($meta) || ine($meta, 'quickbook_id')) {

			return false;
		}

		$companyId = getScopeId();

		$job = Job::where('id', $jobId)
			->where('company_id', $companyId)
			->first();

		if(!$job || $job->quickbook_id) return;

		$customer = $job->customer;

		//return if this is unlink customer or disable from QB sync
		if(!$customer->quickbook_id){
			if($customer->disable_qbo_sync || $customer->unlinkCustomer){
				return ;
			}
		}

		$token = QuickBooks::getToken();

		if(!$token) {
			return false;
		}

		$action = '';
		$parentId = null;

		if(ine($meta, 'action')) {

			$action = $meta['action'];
		}

		$whenToSycn = QuickBooks::whenToSyncJPJob($companyId);

		if($action == 'create' && $whenToSycn == 'created') {

			$this->addTaskOfJobCreate($customer, $job, $meta, $parentId);

			return $job;
		}

		if($action == 'financial added' && $whenToSycn == 'first_financial') {

			$this->addTaskOfJobCreate($customer, $job, $meta, $parentId);

			return $job;
		}

		if($action == 'job awarded' && $whenToSycn == 'awarded') {

			$this->addTaskOfJobCreate($customer, $job, $meta, $parentId);

			return $job;
		}

		if($action == 'stage changed' && $whenToSycn == 'stage') {

			$this->addTaskOfJobCreate($customer, $job, $meta, $parentId);

			return $job;
		}
	}

	public function isJobCustomerAccountSynced($id, $origin){
		if($origin){
			$job = Job::where('company_id', getScopeId())->where('quickbook_id', $id)->first();
		}else{
			$job = Job::where('company_id', getScopeId())->whereId($id)->first();
		}

		if(!$job) return false;

		$customer = $job->customer;

		return (bool)$customer->quickbook_id;
	}

	public function isCustomerAccountSynced($id, $origin){
		if($origin){
			$customer = CustomerModel::where('company_id', getScopeId())->where('quickbook_id', $id)->first();
		}else{
			$customer = CustomerModel::where('company_id', getScopeId())->whereId($id)->first();
		}

		if(!$customer && $origin == QuickBookTask::ORIGIN_JP) {
			return false;
		} else if (!$customer && $origin == QuickBookTask::ORIGIN_QB) {
			//Sync customer is not found now we can also Jobs table since customers are jobs in JP
			return $this->isJobCustomerAccountSynced($id, $origin);
		}

		return (bool)$customer->quickbook_id;
    }

	public function importAllCustomers()
	{
		QBOQueue::addTask(QuickBookTask::CUSTOMER.' '.QuickBookTask::IMPORT, [
				// 'id' => $jobId,
				// 'input' => $meta
		], [
			'object_id' => null,
			'object' => QuickBookTask::CUSTOMER,
			'action' => QuickBookTask::IMPORT,
			'origin' => 0,
		]);
	}

	/**
	 * add customer to staging if he is already on QB
	 *
	 * @param Customer 		| $customer 	| Object of Customr model
	 * @param QBOCustomer 	| $qbCustomer 	| Object of QBOCustomr model
	 */
	public function addToStaging($customer, $qbCustomer)
	{
		Log::info('JP Customer Id:'. $customer->id);
		Log::info('QB Customer Id:'. $qbCustomer->qb_id);
	}

	public function updateJobInQuickbooks($job)
	{
		$customer = $job->customer;

		if(!$job->quickbook_id){
			return $job;
		}

		if($job->isProject()) {
			$parentJob = $job->parentJob;
			if(!$referenceId = $parentJob->quickbook_id) {
				$referenceId = $this->getParentJobQuickbookId($parentJob);
			}
		} else {
			$referenceId = $customer->quickbook_id;
		}

		$displayName = $job->getQuickbookDisplayName();

		$quickbookId = $job->quickbook_id;
		$data = $this->getQuickbookCustomer($quickbookId, $displayName);

		$dateTime = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));

		$createdDate = $dateTime->format('Y-m-d');

		$jobEntity = [
			'MetaData' => [
		    	'CreateTime'  => $createdDate,
		    ]
		];

		$jobEntity['Job'] = true;
		$jobEntity['DisplayName']    = removeQBSpecialChars($displayName);
		$jobEntity['BillWithParent'] = true;
		$jobEntity['ParentRef']['value'] = $referenceId;

		$billingAddress = $customer->billing;
		$jobEntity['GivenName']   = removeQBSpecialChars(substr($customer->getFirstName(), 0, 25)); // maximum of 25 char
		$jobEntity['FamilyName']  = removeQBSpecialChars(substr($customer->last_name, 0, 25));
		$jobEntity['CompanyName'] = substr($customer->getCompanyName(), 0, 25);
		$jobEntity['BillAddr'] = [
	        'Line1' => $billingAddress->address,
	        'Line2' => $billingAddress->address_line_1,
	        'City' =>  $billingAddress->city ? $billingAddress->city : '',
	        'Country' => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
	        'CountrySubDivisionCode' => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
	        'PostalCode' => $billingAddress->zip
		];

		$jobEntity['PrimaryEmailAddr'] = [
			"Address" => $customer->email
		];

		$jobEntity["Notes"] = removeQBSpecialChars($job->description);

	    $jobEntity = array_filter($jobEntity);

		if(ine($data, 'Id')) {

			$jobEntity = array_merge($data, $jobEntity);

			$customer = $this->get($data['Id']);

			$qboCustomer = QBOCustomer::update($customer['entity'], $jobEntity);

			$resultingCustomer = QuickBooks::getDataService()->Update($qboCustomer);

			Job::where('id', $job->id)->update([
				'quickbook_id'   => $resultingCustomer->Id,
				'quickbook_sync_token' => $resultingCustomer->SyncToken,
				'quickbook_sync' => true
			]);

			$job->quickbook_id   = $resultingCustomer->Id;
			$job->quickbook_sync_token = $resultingCustomer->SyncToken;
			$job->quickbook_sync = true;
		}

		return $job;

	}

	public function validateQBSubCustomer($subCustomer)
	{
		$level = $subCustomer->Level;

		//if it is a sub customer than return true
		if($level == 1){
			return true;
		}

		$parentRef = $subCustomer->ParentRef;
		$jpJob = QuickBooks::getJobByQBId($parentRef);

		if(!$jpJob || !$jpJob->isMultiJob()) {
			return false;
		}

		return true;
	}

	/***** Private Methods *****/

	/**
	 * add quickbook sync task when a job is created
	 */
	private function addTaskOfJobCreate($customer, $job, $meta, $parentId)
	{
		// If customer not synced then create all its entities task
		if(!$customer->quickbook_id) {
			$data = [
				'customer_id' => $customer->id,
				'auth_user_id' => Auth::user()->id,
				'company_id' => getScopeId(),
				'origin' => QuickBookTask::ORIGIN_JP,
				'created_source' => QuickBookTask::SYSTEM_EVENT
			];

			$delayTime = Carbon::now()->addSeconds(5);

			Queue::connection('qbo')->later($delayTime, CustomerAccountHandler::class, $data);

			return;
		}

		$task = QBOQueue::addTask(QuickBookTask::QUICKBOOKS_JOB_CREATE, [
			'id' => $job->id,
			'input' => $meta
		], [
			'object_id' => $job->id,
			'object' => 'Job',
			'action' => QuickBookTask::CREATE,
			'origin' => QuickBookTask::ORIGIN_JP,
			'parent_id' => $parentId,
			'created_source' => QuickBookTask::SYSTEM_EVENT
		]);

		Log::info('Job Create - QuickBooks - sync now', [$job->id, $meta]);

		return $task;
	}
}