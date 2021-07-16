<?php
namespace App\Services\QuickBooks\TwoWaySync;

use App\Repositories\QuickBookRepository;
use App\Services\QuickBooks\Client;
use App\Services\QuickBookPayments\Objects\AccessToken;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Services\QuickBooks\QuickBookService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use QuickBooksOnline\API\Core\Http\Serialization\JsonObjectSerializer;
use QuickBooksOnline\API\DataService\DataService;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\Item as QBItem;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Services\QuickBooks\Facades\Department as QBDepartment;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\JobAccountHandler;
use Carbon\Carbon;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\CustomerAccountHandler;
use App\Models\QuickBook;
use App\Models\QuickbookWebhookEntry;
use App\Models\User;
use App\Models\QuickBookTask;
use Settings;
use Exception;
use Auth;
use App\Models\Customer as CustomerModel;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Models\JobCredit;
use App\Models\JobPayment;
use App\Models\JobAwardedStage;
use App\Models\QuickbookUnlinkCustomer;
use App\Models\QuickBookStagedEntry;
use App\Models\QBOCustomer;
use App\Models\Division;
use App\Models\VendorBill;
use App\Models\QBOBill;
use QuickBooksOnline\API\Core\HttpClients\FaultHandler;
use App\Models\JobRefund;
use App\Models\CustomTax;

class Service
{
	protected $repo;

	protected $quickbooksService;

	public $dataService = null;

	public static $qbLogger = null;

	public $payload = '';

	public function __construct(
		QuickBookRepository $repo,
		CustomerRepository $customerRepo,
		Client $client,
		QuickBookService $quickbooksService,
		JsonObjectSerializer $jsonSerializer
	) {
		$this->repo         = $repo;
		$this->customerRepo = $customerRepo;
		$this->client       = $client;
		$this->quickbooksService = $quickbooksService;
		$this->jsonSerializer = $jsonSerializer;
	}

	/**
	 * @method  disconnect
	 * @return Boolean
	 */
	public function accountDisconnect()
	{
		return $this->repo->deleteToken();
	}

	/**
	 * Check Account Has Connected
	 * @param $returnToken Pass true for returning the token as well and not a boolean value
	 * @return \QuickBook instance
	 * @return boolean
	 */
	public function isConnected($returnToken = FALSE)
	{
		$token = $this->repo->getToken();

		if(!$token) {
			return false;
		}

		if($token->isRefreshTokenExpired()) {
			return false;
		}


		if($returnToken) {
			return $token;
		}

		return true;
	}

	/**
	 * Check Account Has Connected
	 * @param $returnToken Pass true for returning the token as well and not a boolean value
	 * @return \QuickBook instance
	 * @return boolean
	 */
	public function isPaymentsConnected()
	{
		$token = $this->repo->getToken();

		if(!$token) {
			return false;
		}

		if($token->isRefreshTokenExpired()) {
			return false;
		}

		return $token->isPaymentsConnected();
	}

	/**
	 * Get Token
	 * @return \Quickbook instance
	 * @return boolean
	 */
	public function getToken()
	{
		$token = $this->isConnected(TRUE);

		if($token && $this->repo->isAccessTokenExpired($token->access_token)) {
			$tokenObj = new AccessToken;
			$tokenObj->setRefreshToken($token->refresh_token);
			$newTokenObj = $this->client->refreshAccessToken($tokenObj);
			
			$token = $this->repo->updateByRefreshToken(
				$tokenObj->getRefreshToken(), 
				$newTokenObj->getAccessToken(), 
				$newTokenObj->getRefreshTokenExpiresIn(), 
				$newTokenObj->getRefreshToken()
			);
		}
		return $token;
	}

	public function log() 
	{
		if(!self::$qbLogger) {

			$logger = new Logger('Quickbooks');

			$logger->pushHandler(new StreamHandler(storage_path() .'/logs/laravel.log', Logger::DEBUG));

			self::$qbLogger = $logger;
		}
		
		return self::$qbLogger;	
	}

	private function createDataService()
	{
		try {

			if(!getScopeId()) {

				throw new Exception('Please set company scope!');
			}

			$token = $this->getToken();

			// If returned false then 
			if(!$token) {

				throw new Exception('Account not connected or QuickBook token expired!');
			}

			$dataService = DataService::Configure(array(
				'auth_mode' => 'oauth2',
				'ClientID' => config('jp.quickbook.client_id'),
				'ClientSecret' => config('jp.quickbook.client_secret'),
				'accessTokenKey' =>$token->access_token,
				'refreshTokenKey' => $token->access_token_secret,
				'QBORealmID' => $token->quickbook_id,
				'baseUrl' => App::environment('production') ? "Production" : "Development",
			));

			$dataService->throwExceptionOnError(true);
			$dataService->disableLog();

			// $dataService->setLogLocation(storage_path() .'/logs/');

			return $dataService;

		} catch (Exception $e) {

			throw new Exception($e->getMessage());
		}
	}

	/** Get data service of Quickbooks */

	public function getDataService()
	{

		$dataService = $this->createDataService();

		if(!$dataService) {

			throw new Exception('Unable to create QuickBook service. please reconnect.');
		}

		$this->dataService = $dataService;

		return $dataService;
	}

	public function getLastError()
	{
		if($this->dataService) {

			$this->dataService->getLastError();
		}
	}

	/**
	 * Helper function
	 * Convert QBO object to array deep
	 * @param boject
	 * @return Array
	 */

	function toArray($object) 
	{

		$reflectionClass = new \ReflectionClass(get_class($object));
		
		$array = array();

		foreach ($reflectionClass->getProperties() as $property) {
			$property->setAccessible(true);
			
			$value = $property->getValue($object);
			
			if(gettype($value) == 'object') {

				$array[$property->getName()] = $this->toArray($value);

			} else if(gettype($property->getValue($object)) == 'array') {

				$arrayVal = $property->getValue($object);

				$propertyArray = [];

				foreach($arrayVal as $key => $val) {
					if(gettype($val) == 'object') {
						$propertyArray[$key] = $this->toArray($val);
					} else {
						$propertyArray[$key] = $val;
					}
				}
				
				$array[$property->getName()] = $propertyArray;
				
			} else {
				$array[$property->getName()] = $property->getValue($object);
			}

			$property->setAccessible(false);
		}

		return $array;
	}

	/**
	 * Change data capture 
	 * This function is used to capture data changed from a perticuler time of past 30 days.
	 * @param $enities array example ['customer', 'payment'].
	 * @param $sinceTime since minutes.
	 */
	public function cdc($enities=array(), $sinceTime=0) 
	{
		$response = null;
		
		// timezone conversion is applied due to API was returning errors
		// this method is tested and working well don't change
		$sinceTime = Carbon::now()->subMinutes($sinceTime)->setTimezone('America/Los_Angeles')->toRfc3339String();

		$dataService = $this->getDataService();

		if($dataService) {
			
			$response = $this->getDataService()->CDC($enities, $sinceTime);
		}

		return $response;
	}

	public function findById($enity, $id)
	{	
		$response = [];

		// if id is not valid then cast it properly to stop errors
		if(!$id) {
			$id = 0; 
		}
		

		/**
		 * FindbyId do not support for payment method
		 * Created as polyfill for payment method
		 */
		if($enity == 'payment_method') {

			$query = "SELECT *  FROM PaymentMethod Where Id = '$id'";
			
			$response = $this->getDataByQuery($query);
			
			if($this->isValidResponse($response, 'QuickBooksOnline\API\Data\IPPPaymentMethod')) {

				$response['entity'] = $response[0];
			}

			return $response;
		}

		$response['entity'] = $this->getDataService()->FindbyId($enity, $id);

		return $response;

	}

	/**
	 * Get Data By Query
	 * @param  Array $query [Query]
	 * @return Data
	 */

	public function getDataByQuery($query)
	{
		
		$result = $this->getDataService()->Query($query);
		return $result;		
	}

	/**
	 * Pagination For Accounts And Products Listing
	 * @param  $count         Total Records
	 * @param  $queryResponse Query Response
	 * @param  $limit         Limit
	 * @param  $page          Current Page
	 *
	 * @return meta data of pagination
	 */
	public function paginatedResponse($records, $total, $limit, $page)
	{
		$meta = [];
		$totalPages = ceil($total / $limit);
		$meta['pagination'] = [
			'total'    => $total,
			'count'	   => count($records),
			'per_page' => (int)$limit,
			'current_page' => (int)$page,
			'total_pages'  => $totalPages,
		];
		$data['data'] = $records;
		$data['meta'] = $meta; 

		return $data;
	}

	/**
	 * Set Company and User scope
	 *  If user context is not set then first user of the company will be used as default user
	 */
	
	public function setCompanyScope($realmId = null, $companyId = null)
	{	
		$quicbooks = null;

		if($realmId && !$companyId) {
			$quicbooks = QuickBook::where('quickbook_id', $realmId)->first();
		}

		if(!$quicbooks && $companyId) {

			$quicbooks = QuickBook::where('company_id', $companyId)->first();
		}

		if(!$quicbooks) {
			return false;
		}
		
		if ($quicbooks) {

			// If we have any existing session
			Auth::logout();

			$user = $this->getQuickBookContextUser($quicbooks->company_id);

			if(!$user) {

				throw new Exception('Company Context is not found!');
			};

			setAuthAndScope($user->id);

			return true;
		}

		return false;
	}

	public function getQuickBookContextUser($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		$userId = null;

		if(ine($settings, 'context')) {
			$userId = $settings['context'];
		}

		$user = User::where('company_id', $companyId)
			->where('id', $userId)
			->first();

		if(!$user) {

			$user = User::where('company_id', $companyId)
				->orderBy('created_at', 'asc')
				->first();
		}

		return $user;
	}

	public function getSyncSetting($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();
		
		if(ine($settings, 'sync_type')) {

			$syncType = $settings['sync_type'];
		} else {

			$syncType = 'with_jobs';
		}

		if(in_array($syncType, ['with_jobs', 'with_financial'])) {

			return $syncType;
		}

		return $syncType = 'with_jobs';
	}

	public function isJobSyncJPtoQBEnabled($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		if(ine($settings, 'jobs') 
			&& ine($settings['jobs'], 'jp_to_qb') 
				&& isset($settings['jobs']['jp_to_qb']['enabled'])) {
			return  $settings['jobs']['jp_to_qb']['enabled'];
		}

		return false;
	}

	/***
	 *  
	 */
	public function whenToSyncJPJob($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		$whenToSync = false;

		if(ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		){
			$jobSync = $settings['jobs_sync'];

			if(ine($jobSync, 'jp_to_qb')
				&& ine($jobSync['jp_to_qb'], 'sync_when')
			){
				$whenToSync = $jobSync['jp_to_qb']['sync_when'];
			}
		}

		if(!empty($whenToSync)) {

			return $whenToSync;
		}

		return false;
	}

	public function synJPJobOnStage($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		$stage = false;

		if(ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		){
			$jobSync = $settings['jobs_sync'];
			if(ine($jobSync, 'jp_to_qb')
				&& ine($jobSync['jp_to_qb'], 'on_stage')
				&& ine($jobSync['jp_to_qb'], 'sync_when')
				&& ($jobSync['jp_to_qb']['sync_when'] == 'stage')
			){
				$stage = $jobSync['jp_to_qb']['on_stage'];
			}
		}

		if(!empty($stage)) {

			return $stage;
		}

		return false;
	}

	public function isJobSyncQBtoJPEnabled($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		if(ine($settings, 'jobs')
			&& ine($settings['jobs'], 'qb_to_jp')
				&& isset($settings['jobs']['qb_to_jp']['enabled'])) {

					return  $settings['jobs']['qb_to_jp']['enabled'];
		}

		return false;
	}

	public function getDefaulJobTrade($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		$data['trade'] = 24;//By default Trade type id Other
		$data['note'] = '';

		if(ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		){
			$jobSync = $settings['jobs_sync'];

			if(ine($jobSync, 'qb_to_jp')
				&& ine($jobSync['qb_to_jp'], 'job_trade')
				&& ine($jobSync['qb_to_jp']['job_trade'], 'trade')
			){
				$data['trade'] = $jobSync['qb_to_jp']['job_trade']['trade'];

				if(($data['trade'] == 24) && ine($jobSync['qb_to_jp']['job_trade'], 'note')){
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

		$settings = $this->getQuickBookSettings();

		$description = '';

		if(ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		){
			$jobSync = $settings['jobs_sync'];

			if(ine($jobSync, 'qb_to_jp')
				&& ine($jobSync['qb_to_jp'], 'job_description')
			){
				$description = $jobSync['qb_to_jp']['job_description'];
			}
		}

		return $description;
	}

	public function getDefaultJobStage($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		$stage = '';

		if(ine($settings, 'jobs_sync')
			&& is_array($settings['jobs_sync'])
		){
			$jobSync = $settings['jobs_sync'];

			if(ine($jobSync, 'qb_to_jp')
				&& ine($jobSync['qb_to_jp'], 'job_stage')
			){
				if(ine($jobSync['qb_to_jp']['job_stage'], 'awarded_stage')
					&& ($jobSync['qb_to_jp']['job_stage']['awarded_stage'] == 'true')
				){
					$stage = JobAwardedStage::getJobAwardedStage($companyId);
				}elseif(ine($jobSync['qb_to_jp']['job_stage'], 'code')){
					$stage = $jobSync['qb_to_jp']['job_stage']['code'];
				}
			}
		}

		return $stage;
	}

	public function getCustomerMapSettings($companyId)
	{
		// Set company scope so that settings can work properly.
		setScopeId($companyId);

		$settings = $this->getQuickBookSettings();

		$maping = [
			'action' => 'decide later'
		];

		if(ine($settings, 'mappings') 
			&& ine($settings['mappings'], 'action')) {
				$maping['action'] = $settings['mappings']['action'];
		}

		if(ine($settings, 'mappings') 
			&& ine($settings['mappings'], 'fields')) {
				$maping['fields'] = $settings['mappings']['fields'];
		}

		return $maping;
	}

	protected function unhandledHook() 
	{	
		try {

			$this->log()->info('Unhandled Hook...', [$this->payload]);

			d("Unhandled Hook...");

		} catch (Exception $e) {

		}
	}
	
	public function process($payload, $webhook) 
	{
		foreach ($payload['eventNotifications'] as $eventNotification) {

			$this->payload = [];

			$this->payload['realmId'] = $eventNotification['realmId'];

			$realmId = $eventNotification['realmId'];	
			
			// Set Company Scope (Default Session for Webhook processing)
			if(!$this->setCompanyScope($realmId)) {
				
				// Log::error('QuickBook is not Connected..', [$payload]);

				echo "QuickBook is not Connected."; return;
			}

			$token = $this->getToken();

			if(!$token) {

				// Log::error('QuickBook is not Connected. Token not valid', [$payload]);

				echo "QuickBook is not Connected. Token not valid"; return;
			}

			foreach($eventNotification['dataChangeEvent']['entities'] as $entry) {

				$this->payload['operation'] = $entry['operation'];

				$this->payload['name'] = $entry['name'];

				$this->payload['id'] = $entry['id'];

				$this->payload['lastUpdated'] = $entry['lastUpdated'];

				$operation = $this->payload['operation'];

				$event = $this->payload['name'];
				if(in_array($event, WebHook::$events)) {

					$settings = $this->getQuickBookSettings();
					$entries = QuickbookWebhookEntry::where('realm_id', $realmId)
						->where('object_type', $entry['name'])
						->where('object_id', $entry['id'])
						->where('operation', $entry['operation'])
						->where('object_updated_at', date('Y-m-d H:i:s', strtotime($entry['lastUpdated'])))
						->count();
					
					if($entries == 0) {
						
						QuickbookWebhookEntry::create([
							'realm_id' => $realmId,
							'quickbook_webhook_id' => $webhook->id,
							'object_type' => $entry['name'],
							'object_id' => $entry['id'],
							'operation' => $entry['operation'],
							'company_id' => getScopeId(),
							'status' => QuickBookTask::STATUS_PENDING,
							'object_updated_at' => date('Y-m-d H:i:s', strtotime($entry['lastUpdated'])),
						]);
					};

				} else {

					$this->unhandledHook();
				}
			}
		}
	}

	/**
	 * Handle Quickbook Exception
	 * @param  Exception $e Exception
	 * @return error throw
	 */
	public function quickBookExceptionThrow(Exception $e, $context=[])
	{
		$meta = $this->errorMeta($e);
		$additional_context = ['subscriber_id' => getScopeId(), 'message' => $e->getMessage()];
		$context = array_merge($additional_context, $context);
		$message = 'Quickbook Error.';
		if($e->getCode() == 401){
			$message = trans('response.error.quickbook_unauthorized');
			throw new UnauthorizedException($message);
		}

		if(get_class($e) == 'QuickBooksOnline\API\Exception\ServiceException') {

			$faultHandler  = $this->getFaultHandlerObject($e);
			unset($context['message']);
			throw new QuickBookException($message, $faultHandler->getIntuitErrorCode(), null, $faultHandler, $context);
		}

		throw new QuickBookException($e);
	}

	public function getFaultHandlerObject($e)
	{
		$error = (string) $e;

		$start = strpos($error, '[<');

		$error = substr($error, $start + 1);

		$error = trim($error);

		$error = trim($error, ']."');

		$faultHandler = new FaultHandler;

		$faultHandler->parseResponse($error);

		return $faultHandler;

	}

	public function getJobByQBId($id, array $with = array())
	{
		$job = Job::withTrashed()->where('quickbook_id', $id)->where('company_id', '=', getScopeId())->first();

		return $job;
	}

	public function getGhostJobByCustomerId($customerId)
	{
		$job = Job::withTrashed()->where('customer_id', $customerId)
			->where('ghost_job', 1)
			->where('company_id', getScopeId())
			->first();

		return $job;
	}

	public function getGhostJobByQBId($customerQBId)
	{
		$job = Job::withTrashed()->where('quickbook_id', $customerQBId)
			->where('ghost_job', 1)
			->where('company_id', getScopeId())
			->first();

		return $job;
	}

	public function getJobInvoiceByQBId($qbId)
	{
		$invoice = JobInvoice::where('job_invoices.quickbook_invoice_id', $qbId)->join('jobs', function($join) {
			$join->on('job_invoices.job_id', '=', 'jobs.id')
				->where('jobs.company_id', '=', getScopeId());
		})->select('job_invoices.*')->first();

		return $invoice;		
	}

	public function getJobPaymentByQBId($qbId) 
	{

		$jobPayment = JobPayment::where('job_payments.quickbook_id', $qbId)->join('jobs', function($join) {
						$join->on('job_payments.job_id', '=', 'jobs.id')
							->where('jobs.company_id', '=', getScopeId());
					})->select('job_payments.*')->first();

		return $jobPayment;		
	}

	public function getJobBillByQBId($id, array $with = array())
	{
		$vendorBill = VendorBill::where('quickbook_id', $id)->where('company_id', '=', getScopeId())->first();

		return $vendorBill;
	}

	/**
	 * Get job credit from JP with id
	 */

	public function getJobCreditByQBId($qbId) 
	{
		$jobCredit = JobCredit::where('quickbook_id', $qbId)->where('company_id', '=', getScopeId())->first();

		return $jobCredit;
	}

	/**
	 * Get Division from JP with qb id
	 */

	public function getDivisionByQBId($qbId)
	{
		$division = Division::where('qb_id', $qbId)->where('company_id', '=', getScopeId())->first();

		return $division;
	}

	/**
	 * Batch Request
	 * @param  Array  $data  batch Data
	 * @return Response
	 */
	public function batchRequest($data) {

		try {
			
			$batch = self::getDataService()->CreateNewBatch();

			foreach($data['BatchItemRequest'] as $key => $item) {

				$batch->AddEntity($item['data'], $item['bId'], $item['operation']);
			}
			
			$batch->Execute();

			$batchItemResponse = $batch->intuitBatchItemResponses;

			return $batchItemResponse;

		} catch (Exception $e) {
			// Log::error($e);
			$this->quickBookExceptionThrow($e);
		}
	}

	public function isValidResponse($response, $class) 
	{
		if(!empty($response) 
			&& gettype($response) == 'array' 
			&& $response[0] instanceof $class) { 

			return true;
		}
		
		return false;
	}

	/**
	 * Get tax rate from QuickBooks
	 * Calculate and returns tax according to Quickbooks rules
	 */

	public function getTaxRates()
	{	

		$taxes = [];

		try {

			$batch = $this->getDataService()->CreateNewBatch();
			
			$batch->AddQuery("Select * From TaxRate  startposition 1 maxresults 500", "TaxRates", "TaxRateUniqueQuery");
			
			$batch->AddQuery("Select * From TaxCode  startposition 1 maxresults 500", "TaxCodes", "TaxCodeUniqueQuery");
			
			$batch->Execute();

			$response = $batch->intuitBatchItemResponses;

			$taxCodes = $response['TaxCodes'];

			$rates = $response['TaxRates'];

			$taxValues = [];

			foreach($rates->entities as $rate) {
				
				if(!$rate->RateValue) continue;

				$taxValues[$rate->Id] = $rate->RateValue;
			}

			foreach($taxCodes->entities as $code) {
				
				$tax = [
					'id' => $code->Id,
					'title' => $code->Name,
					'active' => $code->Active
				];

				$taxValue = 0;
				
				if($code->SalesTaxRateList) {

					$rateList = $code->SalesTaxRateList;

					/**
					 * If tax code has multiple tax rate
					 */

					if(is_array($rateList->TaxRateDetail)) {

						foreach($rateList->TaxRateDetail as $rateDetail) {

							$rateId = $rateDetail->TaxRateRef;
							
							if(isset($taxValues[$rateId])) {

								$taxValue = $taxValue + $taxValues[$rateId];
							}
						}

					} else {

						$rateId = $rateList->TaxRateDetail->TaxRateRef;
						
						if(isset($taxValues[$rateId])) {

							$taxValue = $taxValue + $taxValues[$rateId];
						}
					}

					if($taxValue) {

						$tax['tax_rate'] = $taxValue;

						$taxes[] = $tax;
					}
				}				
			}
			
		} catch (Exception $e) {
			throw new Exception($e);
		}

		return $taxes;
	}

	public function syncQuickBookTaxes()
	{
		$taxes = $this->getTaxRates();

		$taxCodesProcessed = [];

		if($taxes) {

			foreach($taxes as $tax) {

				$customTax = CustomTax::where('company_id', getScopeId())
					->withTrashed()
					->where('quickbook_tax_code_id',  $tax['id'])
					->first();

				if ($customTax) {
					
					$customTax->title = $tax['title'];
					$customTax->tax_rate = $tax['tax_rate'];
					$customTax->updated_at = Carbon::now();
					$customTax->save();

				} else if (!$customTax) {

					$customTax = new CustomTax(['company_id' => getScopeId(), 'quickbook_tax_code_id' => $tax['id']]);
					$customTax->title = $tax['title'];
					$customTax->tax_rate = $tax['tax_rate'];
					$customTax->created_by = Auth::user()->id;
					$customTax->save();
				}

				if($tax['active'] == 'true') {
					$taxCodesProcessed[] = $tax['id'];
				}				
			}

			// Soft Delete all other tax codes
			CustomTax::where('company_id', getScopeId())
				->whereNotNull('quickbook_tax_code_id')
				->whereNotIn('quickbook_tax_code_id', $taxCodesProcessed)
				->delete();

			// make sure all that came as active stays active in our database as well
			CustomTax::where('company_id', getScopeId())
			->whereNotNull('quickbook_tax_code_id')
			->whereIn('quickbook_tax_code_id', $taxCodesProcessed)
			->restore();
		}
	}

	/**
	 * Get TaxCode from QuickBooks
	 */
	public function getTaxCode($taxCodeId)
	{	

		try {

			$query = "Select * From TaxCode Where Active = true and Id = '{$taxCodeId}'";

			$response = $this->getDataByQuery($query);
			
			if($this->isValidResponse($response, '\QuickBooksOnline\API\Data\IPPTaxCode')) {

				$taxCode = [
					'id' => $response[0]->Id,
					'name' => $response[0]->Name,
				];

				return $taxCode;
			} 

			throw new Exception('Tax code not found on quickbooks!.');

		} catch (Exception $e) {
			throw new Exception($e);
		}
	}
	
	function qbCustomerCreateHandler()
	{
		QBCustomer::create($this->payload['id']);
	}

	function qbCustomerUpdateHandler()
	{
		QBCustomer::update($this->payload['id']);
	}

	/** Invoice Handlers */

	function qbInvoiceCreateHandler()
	{
		QBInvoice::create($this->payload['id']);
	}

	function qbInvoiceUpdateHandler()
	{
		QBInvoice::update($this->payload['id']);
	}

	function qbInvoiceDeleteHandler()
	{
		QBInvoice::delete($this->payload['id']);
	}

	/** End Invoice Handlers */

	/** Payment Handler Function */

	function qbPaymentCreateHandler()
	{
		QBPayment::create($this->payload['id']);
	}

	function qbPaymentUpdateHandler()
	{
		QBPayment::update($this->payload['id']);
	}

	function qbPaymentDeleteHandler()
	{
		QBPayment::delete($this->payload['id']);
	}

	/** End Payment Handlers */

	/** Department Handler Function */

	function qbDepartmentCreateHandler()
	{
		QBDepartment::create($this->payload['id']);
	}

	function qbDepartmentUpdateHandler()
	{
		QBDepartment::update($this->payload['id']);
	}

	function qbDepartmentDeleteHandler()
	{
		QBDepartment::delete($this->payload['id']);
	}

	/** End Department Handlers */

	/** CreditMemo Handler Function */

	function qbCreditMemoCreateHandler()
	{
		QBCreditMemo::create($this->payload['id']);
	}

	function qbCreditMemoUpdateHandler()
	{
		QBCreditMemo::update($this->payload['id']);
	}

	function qbCreditMemoDeleteHandler()
	{
		QBCreditMemo::delete($this->payload['id']);
	}

	/** End CreditMemo Handlers */

	/** Item Handler Function */

	function qbItemCreateHandler()
	{
		QBItem::create($this->payload['id']);
	}

	function qbItemUpdateHandler()
	{
		QBItem::update($this->payload['id']);
	}

	function qbItemDeleteHandler()
	{
		QBItem::delete($this->payload['id']);
	}

	/** End Item Handlers */



	public function isControlledSynced($type, $typeId, $origin=0)
	{
		
		$settings = $this->getQuickBookSettings();
		
		$isSynced = true;

		if(ine($settings, 'controlled_sync')) {

			if($settings['controlled_sync'] == 'true') {
				
				switch ($type) {

					case QuickBookTask::JOB:

						$isSynced = QBCustomer::isJobCustomerAccountSynced($typeId, $origin);
						break;
					case QuickBookTask::INVOICE:

						$isSynced = QBInvoice::isCustomerAccountSynced($typeId, $origin);
						break;
					case QuickBookTask::PAYMENT:

						$isSynced = QBPayment::isCustomerAccountSynced($typeId, $origin);
						break;
					case QuickBookTask::CREDIT_MEMO:

						$isSynced = QBCreditMemo::isCustomerAccountSynced($typeId, $origin);
						break;
					case QuickBookTask::CUSTOMER:

						$isSynced = QBCustomer::isCustomerAccountSynced($typeId, $origin);
						break;
					default:
						# code...
						break;
				}
			}
		}

		return $isSynced;

	}

	

	public function isCustomerLinked($type, $typeId, $origin = 0)
	{
		$isSynced = false;

		switch ($type) {

			case QuickBookTask::JOB:

				$isSynced = QBCustomer::isJobCustomerAccountSynced($typeId, $origin);
				break;
			case QuickBookTask::INVOICE:

				$isSynced = QBInvoice::isCustomerAccountSynced($typeId, $origin);
				break;
			case QuickBookTask::PAYMENT:

				$isSynced = QBPayment::isCustomerAccountSynced($typeId, $origin);
				break;
			case QuickBookTask::CREDIT_MEMO:

				$isSynced = QBCreditMemo::isCustomerAccountSynced($typeId, $origin);
				break;
			case QuickBookTask::CUSTOMER:

				$isSynced = QBCustomer::isCustomerAccountSynced($typeId, $origin);
				break;
			default:
				$isSynced = false;
				break;
		}

		return $isSynced;
	}

	public function getTotalTax($amount, $tax = null)
	{
		if(!$tax) return $amount;

		return round($amount*($tax / 100 ), 2);	
	}

	public function getAmountWithoutTax($amount, $tax = null)
	{
		if(!$tax) return $amount;

		$totalTax = round($amount*($tax / 100 ), 2);

		$amount = $amount - $totalTax;

		return $amount;	
	}

	/**
	 * Extract XML from QuickBook error string
	 */

	public function errorMeta(Exception $e)
	{

		try {

			$error = (string) $e;

			$start = strpos($error, '[<');

			$error = substr($error, $start + 1);

			$error = trim($error);

			$error = trim($error, ']."');

			$errorObject = simplexml_load_string($error);

			$error = $this->xmlToArray($errorObject);
			
			$error['code'] = $error['IntuitResponse']['Fault']['Error']['attributes']['code'];

			$error['message'] = $error['IntuitResponse']['Fault']['Error']['Detail']['value'];

			return $error;
			 
		} catch (Exception $exception) {

			return false;
		}
	}

	function xmlToArray(\SimpleXMLElement $xml)
	{
		$parser = function (\SimpleXMLElement $xml, array $collection = []) use (&$parser) {
			
			$nodes = $xml->children();

			$attributes = $xml->attributes();

			if (0 !== count($attributes)) {

				foreach ($attributes as $attrName => $attrValue) {

					$collection['attributes'][$attrName] = strval($attrValue);
				}
			}

			if (0 === $nodes->count()) {

				$collection['value'] = strval($xml);
				
				return $collection;
			}

			foreach ($nodes as $nodeName => $nodeValue) {
				
				if (count($nodeValue->xpath('../' . $nodeName)) < 2) {

					$collection[$nodeName] = $parser($nodeValue);
					
					continue;
				}

				$collection[$nodeName][] = $parser($nodeValue);
			}

			return $collection;
		};

		return [

			$xml->getName() => $parser($xml)
		];
	}

	/**
	 * Add Entry in the Webhook hooks entry table so that tasks can be scheduled from there.
	 */

	public function addWebhookEntry($meta)
	{	

		try {

            $entryMeta = [
                'realm_id' => $meta['realm_id'],
                'status' => QuickBookTask::STATUS_PENDING,
				'company_id' => getScopeId(),
				'operation' => $meta['operation'],
				'object_id' => $meta['object_id'],
				'object_type' => $meta['object_type']
            ];

            if(ine($meta, 'extra')) {
                
                $entryMeta['extra'] = $meta['extra'];
            }
            
            if(ine($meta, 'quickbook_webhook_id')) {
    
                $entryMeta['quickbook_webhook_id'] = $meta['quickbook_webhook_id'];
            }
 
            if(ine($meta, 'object_updated_at')) {
    
                $entryMeta['object_updated_at'] = date('Y-m-d H:i:s', strtotime($meta['object_updated_at']));
            }

			$entry = QuickbookWebhookEntry::where('company_id', getScopeId())
				->where('object_type', $entryMeta['object_type'])
				->where('object_id', $entryMeta['object_id'])
				->where('operation', $entryMeta['operation']);
				
			if(ine($entryMeta, 'object_updated_at')) {

				$entry->where('object_updated_at',  $entryMeta['object_updated_at']);
			}

			$entry = $entry->first();
			
			if($entry) {

				return $entry;
			}

			QuickbookWebhookEntry::create($entryMeta);

        } catch(Exception $e) {

            throw new Exception($e->getMessage());
        }
	}
	
	public function getQBEntitiesByParentId($companyId, $parentId, $entity=null)
    {
        $result =[];
    	if(!$entity || !$parentId) return $result;
        try {
            $this->setCompanyScope(null, $companyId);
            $start = 1;
            $limit = 500;
            $fetch = true;

            while($fetch) {
                $response = $this->getDataByQuery("SELECT *  FROM {$entity} WHERE CustomerRef ='{$parentId}' ORDER BY Id ASC STARTPOSITION {$start} MAXRESULTS {$limit}");
                $start = $start + $limit;

                if(empty($response)) {
                    $fetch = false;
                    break;
                }
                $result = array_merge($result, $response);
            }
        } catch (Exception $e) {
            Log::info('In Sync Service Get Entities By Parent Id Exception.');
            Log::info($e);
        }

        return $result;
	}
 
	public function isTwoWaySyncEnabled()
	{
		$settings = $this->getQuickBookSettings();

		if(ine($settings, 'sync_type')) {

			if($settings['sync_type'] == 'two_way') {

				return true;
			}
		}

		return false;
	}

	/**
	 * Get Controlled Synced Setting
	 */

	public function isControlledSyncEnabled()
	{
		$settings = $this->getQuickBookSettings();

		if (ine($settings, 'controlled_sync') && $settings['controlled_sync'] == 'true') {

			return true;
		}

		return false;
	}

	public function getQuickBookSettings()
	{
		$settings = Settings::forUser(null, getScopeId());

		$settings = $settings->get('QUICKBOOK_ONLINE');

		return $settings;
	}

	/**
	 * Common funciton to sync Job Or Customer to the QuickBooks
	 */

	public function syncJobOrCustomerToQuickBooks($job, $meta = null)
	{
		$this->setCompanyScope(null,$job->company_id);

		if(!$this->isConnected()) {

			return false;
		}

		Log::info('Sync Invoice To QuickBooks - start', [$job->id, $meta]);

		$companyId = $job->company_id;

		$customer = $job->customer;

		$whenToSycn = $this->whenToSyncJPJob($companyId);

		if($whenToSycn == 'stage') {

			$stage = $this->synJPJobOnStage(getScopeId());

			$workflowStages = $job->jobWorkflowHistory()->pluck('stage')->toArray();

			$currentStage = $job->jobWorkflow->current_stage;

			if((!in_array($stage, $workflowStages) || ($currentStage != $stage))) {

				return;
			}
		}

		$meta = [
			'customer_id' => $customer->id,
			'auth_user_id' => Auth::user()->id,
			'company_id' => getScopeId(),
			'origin' => QuickBookTask::ORIGIN_JP,
			'created_source' => QuickBookTask::SYSTEM_EVENT
		];

		if(!$customer->quickbook_id) {
			if($customer->disable_qbo_sync || $customer->unlinkCustomer){
				return ;
			}

			Queue::connection('qbo')->push(CustomerAccountHandler::class, $meta);
			
			return;
		}

		if(!$job->quickbook_id) {

			$meta['job_id'] = $job->id;
				
			Queue::connection('qbo')->push(JobAccountHandler::class, $meta);

			return;
		}
	}

	public function checkJobSyncSettings($job)
	{
		setScopeId($job->company_id);
		$syncWhen = $this->whenToSyncJPJob($job->company_id);

		if($syncWhen == 'created') return true;

		if(($syncWhen == 'first_financial')
			|| ($syncWhen == 'awarded')){
			$jobIds = [];

			if($job->isMultiJob()){
				$jobIds = Job::where('company_id', $job->company_id)
					->where('id', $job->id)
					->orWhere('parent_id', $job->id)
					->Pluck('id')
					->toArray();
			}else{
				$jobIds[] = $job->id;
			}

			$invoiceCount = JobInvoice::whereIn('job_id', (array)$jobIds)->count();

			if($invoiceCount){
				return true;
			}

			$paymentCount = JobPayment::whereIn('job_id', (array)$jobIds)
				->whereNull('canceled')
				->whereNull('credit_id')
				->count();
			if($paymentCount){
				return true;
			}

			$creditCount = JobCredit::where('company_id', $job->company_id)
				->whereIn('job_id', (array)$jobIds)
				->whereNull('canceled')
				->count();
			if($creditCount){
				return true;
			}

			$billCount =VendorBill::where('company_id', $job->company_id)
				->whereIn('job_id', (array)$jobIds)
				->count();
			if($billCount){
				return true;
			}

			$refundCount = JobRefund::where('company_id', $job->company_id)
				->whereIn('job_id', (array)$jobIds)
				->count();
			if($refundCount){
				return true;
			}

			if($syncWhen == 'awarded'){

				$awardedStage = JobAwardedStage::getJobAwardedStage($job->company_id);

				if(!$awardedStage){
					return false;
				}

				$workflowStages = $job->jobWorkflowHistory()->pluck('stage')->toArray();

				$currentStage = $job->jobWorkflow->current_stage;

				if((in_array($awardedStage, $workflowStages) || ($currentStage == $awardedStage))) {

					return true;
				}
			}

			return false;
		}

		if($syncWhen == 'stage'){
			$stage = $this->synJPJobOnStage($job->company_id);

			$workflowStages = $job->jobWorkflowHistory()->pluck('stage')->toArray();

			$currentStage = $job->jobWorkflow->current_stage;

			if((in_array($stage, $workflowStages) || ($currentStage == $stage))) {

				return true;
			}

			return false;
		}

		return false;
	}

	public function validateCustomerSyncSettings($customer)
	{
		setScopeId($customer->company_id);
		$syncWhen = $this->whenToSyncJPJob($customer->company_id);

		if($syncWhen == 'created') return true;
		$currentStageArray = [];
		$stageHistoryArray = [];
		if(($syncWhen == 'awarded') ||($syncWhen == 'stage')){
			$currentStageArray = CustomerModel::where('customers.id', $customer->id)
				->leftJoin('jobs', function($join)use($customer){
				$join->on('jobs.customer_id', '=', 'customers.id')
					->where('jobs.company_id', '=', $customer->company_id);
				})->leftJoin('job_workflow', 'job_workflow.job_id', '=', 'jobs.id')
			->where('job_workflow.company_id', $customer->company_id)
			->pluck('job_workflow.current_stage')->toArray();

			$stageHistoryArray = CustomerModel::where('customers.id', $customer->id)
				->leftJoin('jobs', function($join)use($customer){
				$join->on('jobs.customer_id', '=', 'customers.id')
					->where('jobs.company_id', '=', $customer->company_id);
				})->leftJoin('job_workflow_history', 'job_workflow_history.job_id', '=', 'jobs.id')
			->where('job_workflow_history.company_id', $customer->company_id)
			->pluck('job_workflow_history.stage')->toArray();
			$stageHistoryArray = arry_fu($stageHistoryArray);
			$currentStageArray = arry_fu($currentStageArray);
		}

		if(($syncWhen == 'first_financial')
			|| ($syncWhen == 'awarded')){

			$invoiceCount = $customer->invoices->count();
			if($invoiceCount){
				return true;
			}

			$paymentCount = $customer->payments->count();
			if($paymentCount){
				return true;
			}

			$creditCount = $customer->jobCredits->count();
			if($creditCount){
				return true;
			}

			$billCount = $customer->vendorbill->count();
			if($billCount){
				return true;
			}

			$refundCount = $customer->refund->count();
			if($refundCount){
				return true;
			}

			if($syncWhen == 'awarded'){

				$awardedStage = JobAwardedStage::getJobAwardedStage($customer->company_id);

				if(!$awardedStage){
					return false;
				}

				if((!empty($stageHistoryArray) && in_array($awardedStage, $stageHistoryArray))
					||(!empty($currentStageArray) && in_array($awardedStage, $currentStageArray))
				){
					return true;
				}
			}

			return false;
		}

		if($syncWhen == 'stage'){
			$stage = $this->synJPJobOnStage($customer->company_id);
			if((!empty($stageHistoryArray) && in_array($stage, $stageHistoryArray))
				||(!empty($currentStageArray) && in_array($stage, $currentStageArray))
			){

				return true;
			}

			return false;
		}

		return false;
	}

	/**
	 * Mark any existing task to retry
	 */

	public function retryTask($taskId, $origin, $parentId, $object) 
	{
		$task = QBOQueue::getTask([
			'id' => $taskId,
			'origin' => $origin
		]);

		// If we have task and it is failed
		// then to retry mark task status as Pending
		if(!empty($task)) {
			
			$parentTask = QBOQueue::getTask([
				'object_id' => $parentId,
				'object' => $object,
				'origin' => $origin,
				'company_id' => getScopeId()
			]);

			if($parentTask) {

				$parentTask->status = QuickBookTask::STATUS_PENDING;

				return $parentTask->save();
			}
		}

		return false;
	}

	/**
	 * Get All Financial from QuickBooks
	 *
	 */

	public function getAllFinancialEntities($parentId)
	{
		$data = [];

		try {

			$batch = $this->getDataService()->CreateNewBatch();

			$batch->AddQuery("SELECT *  FROM Invoice WHERE CustomerRef ='{$parentId}' ORDER BY Id ASC", "Invoices", "InvoiceUniqueQuery");
			$batch->AddQuery("SELECT *  FROM Payment WHERE CustomerRef ='{$parentId}' ORDER BY Id ASC", "Payments", "PaymentUniqueQuery");
			$batch->AddQuery("SELECT *  FROM CreditMemo WHERE CustomerRef ='{$parentId}' ORDER BY Id ASC", "CreditsMemo", "CreditMemoUniqueQuery");
			$batch->AddQuery("SELECT *  FROM RefundReceipt WHERE CustomerRef ='{$parentId}' ORDER BY Id ASC", "RefundsReceipt", "RefundReceiptUniqueQuery");

			$batch->Execute();

			$response = $batch->intuitBatchItemResponses;

			$invoices = $response['Invoices']->entities;
			$payments = $response['Payments']->entities;
			$creditsMemo = $response['CreditsMemo']->entities;
			$refunds = $response['RefundsReceipt']->entities;

			$data= [
				'invoices'=> $invoices,
				'payments'=> $payments,
				'credits'=> $creditsMemo,
				'refunds'=> $refunds,
			];

			return $data;
		} catch (Exception $e) {
			// Log::error($e);
			$this->quickBookExceptionThrow($e);
		}
	}

	/**
	 * Get All Financial from QuickBooks
	 *
	 */

	public function getAllFinancialEntitiesCount($customerId)
	{
		$count = [];
		$entities = self::getAllFinancialEntities($customerId);
		foreach ($entities as $name => $entity) {
			switch ($name) {
				case 'invoices':
					$count['TotalInvoiceCount'] = count($entity);
					break;
				case 'payments':
					$count['TotalPaymentCount'] = count($entity);
					break;
				case 'credits':
					$count['TotalCreditCount'] = count($entity);
					break;
			}
		}

		return $count;
	}

	public function getCustomerAllFinancials($id)
	{
		$customerIds = [];

		$customerIds = QBOCustomer::where('company_id', getScopeId())
			->where('qb_parent_id', $id)
			->pluck('qb_id')
			->toArray();
		//Author Anoop
		//Remove project financials from listing because we didn't create project from QBO
		// if(!empty($customerIds)){
		// 	$projectIds = QBOCustomer::where('company_id', getScopeId())
		// 		->whereIn('qb_parent_id', $customerIds)
		// 		->pluck('qb_id')
		// 		->toArray();
		// 	$customerIds = array_merge($customerIds, $projectIds);
		// }

		$customerIds[] = $id;

		$invoiceAmount = 0;
		$paymentAmount = 0;
		$creditAmount = 0;
		$invoiceCount = 0;
		$paymentCount = 0;
		$creditCount = 0;
		$billAmount = 0;
		$billCount = 0;
		$refundAmount = 0;
		$refundCount = 0;

		$bills = QBOBill::where('company_id', getScopeId())
			->whereIn('qb_customer_id', (array)$customerIds)
			->pluck('total_amount')
			->toArray();

		$billAmount = array_sum($bills);
		$billCount = count($bills);


		foreach ($customerIds as $customerId) {
			$entities = $this->getAllFinancialEntities($customerId);

			if(ine($entities, 'invoices')){
				$invoiceAmount += $this->getEntitiesAmount($entities['invoices']);
				$invoiceCount += count($entities['invoices']);
			}

			if(ine($entities, 'payments')){
				$paymentAmount += $this->getEntitiesAmount($entities['payments']);
				$paymentCount += count($entities['payments']);
			}

			if(ine($entities, 'credits')){
				$creditAmount += $this->getEntitiesAmount($entities['credits']);
				$creditCount += count($entities['credits']);
			}

			if(ine($entities, 'refunds')){
				$refundAmount += $this->getEntitiesAmount($entities['refunds']);
				$refundCount += count($entities['refunds']);
			}
		}

		$financials = [
			'total_invoice_amount' => (float)$invoiceAmount,
			'total_payment_amount' => (float)$paymentAmount,
			'total_credit_amount' => (float)$creditAmount,
			'total_invoice_count' => $invoiceCount,
			'total_payment_count' => $paymentCount,
			'total_credit_count' => $creditCount,
			'total_bill_amount' => $billAmount,
			'total_bill_count' => $billCount,
			'total_refund_amount' => $refundAmount,
			'total_refund_count' => $refundCount,
		];

		return $financials;

	}

	/**
	 * Temp location for the common function.
	 */
	public function saveToStagingArea($meta)
	{

		$data = [
			'object_id' => $meta['object_id'],
			'object_type' => $meta['object_type'],
			'type' => $meta['type'],
			'company_id' => getScopeId(),
		];

		$stagedEntry = QuickBookStagedEntry::where('object_id', $data['object_id'])
			->where('object_type', $data['object_type'])
			->where('company_id', getScopeId())
			->first();

		if($stagedEntry) {

			return false;
		}

		QuickBookStagedEntry::create($data);

		Log::info("Saved it staging area.", $meta);
	}

	public function getCustomerId($id)
	{
		$customer = QBOCustomer::where('qb_id', $id)->where('company_id', getScopeId())->first();

		// Customer
		if($customer && !$customer->qb_parent_id) {
			return $customer->qb_id;
		}

		if ($customer) {
			$customer = QBOCustomer::where('qb_id', $customer->qb_parent_id)->where('company_id', getScopeId())->first();
		}

		// Job
		if ($customer && !$customer->qb_parent_id) {
			return $customer->qb_id;
		}

		if ($customer) {
			$customer = QBOCustomer::where('qb_id', $customer->qb_parent_id)->where('company_id', getScopeId())->first();
		}

		// Project
		if ($customer) {
			return $customer->qb_id;
		}

		return false;
	}

	private function checkQbUnlinkedCustomer($entry)
	{
		$customerId = null;
		if($entry['object_type'] == QuickBookTask::CUSTOMER) {
			$customerId = $this->getCustomerId($entry['object_id']);
		} else {
			
			$response = $this->findById(strtolower($entry['object_type']), $entry['object_id']);

			if(ine($response, 'entity')) {
				
				$entity = $response['entity'];
				
				$entityArray = $this->toArray($entity);
				
				if(ine($entityArray, 'CustomerRef')) {

					$customerId = $this->getCustomerId($entityArray['CustomerRef']);
				}
			}
		}

		if($customerId){
			$unlinkCustomer = QuickbookUnlinkCustomer::where('company_id', getScopeId())
				->where('quickbook_id', $customerId)
				->first();

			if($unlinkCustomer){
				return true;
			}
		}
		return false;
	}

	

	private function getEntitiesAmount($entities)
	{
		$totalAmount = 0;

		foreach ($entities as $entity) {
			$totalAmount += $entity->TotalAmt;
		}

		return $totalAmount;
	}

	public function getQuickBookSyncStatus($status)
	{
		$syncStatus = null;

		switch ($status) {

			case QuickBookTask::STATUS_INPROGRESS:
				$syncStatus = '0';
				break;
			case QuickBookTask::STATUS_SUCCESS:
				$syncStatus = '1';
				break;
			case QuickBookTask::STATUS_ERROR:
				$syncStatus = '2';
				break;
			default:
				break;
		}

		return $syncStatus;
	}

	/**
	 * Returns minutes
	 * used for CDC 
	 */

	public function getQuickBookSyncChangesInterval()
	{
		$interval = 60;

		return $interval;
	}

	public function isCustomerExistsOnQuickbooks($customerId)
	{
		$isExists = false;
		$customer = $this->getQBCustomerByQuery($customerId);

		if($customer){
			$isExists = true;
		}

		return $isExists;

	}

	public function getQBCustomerByQuery($customerId)
    {
    	$customer = null;
    	if(!$customerId) return $customer;
        try {
           $customer = $this->getDataByQuery("SELECT *  FROM Customer WHERE Id ='{$customerId}' ");
        	return $customer;
        } catch (Exception $e) {
            Log::info('Get Customer By Query Exception.');
            Log::info($e);
        }

	}
	//unlink jp entities which are link on quickbooks
	public function unlinkJPEntities($job)
	{
		$this->deleteQBOCustomer($job->quickbook_id);

		if($job->ghost_job){
			$customer = $job->customer;
			$this->customerRepo->unlinkCustomerQuickbookEntities($customer);
			$customer->quickbook_sync_status = null;
    		$customer->quickbook_sync_token = null;
    		$customer->quickbook_sync = false;
    		$customer->quickbook_id = null;
    		$customer->save();

    		return $job;
		}

		$this->unlinkJobQuickbookEntities($job);
		return $job;

	}

	public function unlinkJobQuickbookEntities($job)
	{
		$data = [
			'quickbook_id' => null,
    		'quickbook_sync_status' => null,
    		'quickbook_sync_token' => null,
    		'quickbook_sync' => false,
    	];

    	Job::where('id', $job->id)
    		->where('company_id', $job->company_id)
    		->where('customer_id', $job->customer_id)
    		->update($data);

    	JobInvoice::where('job_id', $job->id)
    		->where('customer_id', $job->customer_id)
    		->whereNotNull('quickbook_invoice_id')
    		->update([
    			'quickbook_invoice_id' => null,
	    		'quickbook_sync_status' => null,
	    		'quickbook_sync_token' => null,
	    		'quickbook_sync' => false,
    		]);

    	JobPayment::where('job_id', $job->id)
    		->where('customer_id', $job->customer_id)
    		->whereNotNull('quickbook_id')
    		->update($data);

    	JobCredit::where('job_id', $job->id)
    		->where('company_id', $job->company_id)
    		->where('customer_id', $job->customer_id)
    		->whereNotNull('quickbook_id')
    		->update($data);

    	return $job;
    }

    public function deleteQBOCustomer($id)
    {
    	$customerIds = [];

		$customerIds = QBOCustomer::where('company_id', getScopeId())
			->where('qb_parent_id', $id)
			->pluck('qb_id')
			->toArray();

		if(!empty($customerIds)){
			$projectIds = QBOCustomer::where('company_id', getScopeId())
				->whereIn('qb_parent_id', $customerIds)
				->pluck('qb_id')
				->toArray();
			$customerIds = array_merge($customerIds, $projectIds);
		}

		$customerIds[] = $id;

		QBOCustomer::where('company_id', getScopeId())
		 	->whereIn('qb_id', $customerIds)
		 	->delete();

    }

    public function getQBDataByBatchRequest($companyId){
    	 try {

           	setScopeId($companyId);
            $start = 1;
            $limit = 1000;
            $fetch = true;
            $bills = [];
            while($fetch) {
            	$batch = $this->getDataService()->CreateNewBatch();
                //for now it only get bills but we can add queries to get other entities
				$batch->AddQuery("SELECT *  FROM Bill STARTPOSITION {$start} MAXRESULTS {$limit}", "Bills", "BillUniqueQuery");
				$batch->Execute();

				$response = $batch->intuitBatchItemResponses;
                $start = $start + $limit;
	            if(empty($response['Bills']->entities)) {
	                $fetch = false;
	                break;
	            }
				$bills = array_merge($bills, $response['Bills']->entities);
            }
            $data = [
            	'bills' => $bills
            ];
            return $data;

        } catch (Exception $e) {

            throw new Exception($e);
        }
    }

}