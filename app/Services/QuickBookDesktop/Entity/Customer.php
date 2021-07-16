<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Setting\Settings;
use App\Repositories\CustomerRepository;
use App\Services\Validators\CustomerValidator;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\QuickBooks\Exceptions\CustomerDuplicateException;
use Carbon\Carbon;
use QuickBooks_XML_Parser;
use App\Models\State;
use Exception;
use App\Models\Customer as CustomerModal;
use App\Models\QBOCustomer;
use App\Models\QBDBill;
use App\Models\QBDInvoice;
use App\Models\QBDPayment;
use App\Models\QBDCreditMemo;
use QuickBooks;
use App\Models\QuickbookUnlinkCustomer;
use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Customer extends BaseEntity
{
	public function __construct(CustomerRepository $customerRepos, Settings $settings)
	{
		$this->customerRepo = $customerRepos;
		$this->settings = $settings;
	}

	public function getCustomerByQbdId($id)
	{
		return CustomerModal::withTrashed()->where('qb_desktop_id', $id)->where('company_id', '=', getScopeId())->first();
	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$customer = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CustomerQueryRs');

			foreach ($List->children() as $Customer) {

				$level = $Customer->getChildDataAt('CustomerRet Sublevel');

				// skip all jobs
				if ($level != '0') {
					continue;
				}

				$customer = [
					'ListID' => $Customer->getChildDataAt('CustomerRet ListID'),
					'Name' => $Customer->getChildDataAt('CustomerRet Name'),
					'FullName' => $Customer->getChildDataAt('CustomerRet FullName'),
					'FirstName' => $Customer->getChildDataAt('CustomerRet FirstName'),
					'LastName' => $Customer->getChildDataAt('CustomerRet LastName'),
					'Email' => $Customer->getChildDataAt('CustomerRet Email'),
					'EditSequence' => $Customer->getChildDataAt('CustomerRet EditSequence'),
					'CompanyName' => $Customer->getChildDataAt('CustomerRet CompanyName'),
					'Notes' => $Customer->getChildDataAt('CustomerRet Notes'),
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

				$phones = [
					'Phone' => $Customer->getChildDataAt('CustomerRet Phone'),
					'AltPhone' => $Customer->getChildDataAt('CustomerRet AltPhone'),
					'Fax' => $Customer->getChildDataAt('CustomerRet Fax'),
				];

				$customer['BillAddress'] = $billAddress;

				$customer['ShipAddress'] = $shipAddress;

				$customer['Phones'] = $phones;
			}
		}

		return $customer;
	}

	function create($qbdCustomer)
	{
		$mappedInput = $this->reverseMap($qbdCustomer);

		$validate = CustomerValidator::make($mappedInput['customer'])->scope([]);

		if ($validate->fails()) {
			throw new Exception("Invalid Customer Details");
		}

		$customerName = $mappedInput['customer']['first_name'] . ' ' . $mappedInput['customer']['last_name'];

		if ($this->isDuplicate(
			$mappedInput['customer']['phones'],
			$mappedInput['customer']['email'],
			$customerName,
			$mappedInput['customer']['company_name'],
			$isQBD = true
		)) {

			QuickBooks::saveToStagingArea([
				'object_id' => $qbdCustomer['ListID'],
				'object_type' => 'Customer',
				'type' => 'duplicate'
			]);

			throw new CustomerDuplicateException(['customer_id' => $qbdCustomer['ListID']]);
		}

		$customer = $this->saveCustomer(
			$mappedInput['customer'],
			$mappedInput['customer']['address'],
			$mappedInput['customer']['phones'],
			false,
			$mappedInput['customer']['billing'],
			false
		);

		$this->linkEntity($customer, $qbdCustomer);

		Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($customer->id));

		return $customer;
	}

	function update($qbdCustomer, CustomerModal $customer)
	{
		$mappedInput = $this->reverseMap($qbdCustomer, $customer);

		if ($qbdCustomer['IsActive'] == 'false') {

			$customer->qb_desktop_sequence_number = $qbdCustomer['EditSequence'];

			$customer->save();

			$customer->delete();

			return $customer;

		} else if ($customer->trashed() && $qbdCustomer['IsActive'] == 'true') {

			$customer->restore();

		} else {

			$customer = $this->saveCustomer(
				$mappedInput['customer'],
				$mappedInput['customer']['address'],
				$mappedInput['customer']['phones'],
				false,
				$mappedInput['customer']['billing'],
				false
			);
		}

		$this->linkEntity($customer, $qbdCustomer);

		Event::fire('JobProgress.Customers.Events.CustomerUpdated', new CustomerUpdated($customer->id));

		return $customer;
	}

	public function reverseMap($input, $customer = null)
	{
		$meta = [];

		$customer = $this->mapCustomerInput($input, $customer);
		$customer['address'] = $this->mapAddressInput($input);
		$customer['billing'] = $this->mapBillingAddressInput($input);
		$customer['phones']  = $this->mapPhonesInput($input);
		$meta['customer']  = $customer;

		return $meta;
	}

	public function mapCustomerInput($input = array(), CustomerModal $jpCustomer = null)
	{
		$map = [
			'qb_desktop_id' => 'ListID',
			'first_name' => 'FirstName',
			'last_name'  => 'LastName',
			'email' => 'Email',
			'company_name' => 'CompanyName',
			'qb_desktop_sequence_number' => 'EditSequence',
			'note' => 'Notes',
			'name' => 'Name'
		];

		$customer = $this->mapInputs($map, $input);

		if ($jpCustomer) {
			$customer['id'] = $jpCustomer->id;
		}

		//for save dirty record i.e no first_name, last_name
		$displayNameArray = explode(" ", $customer['name'], 2);

		$firstName = $displayNameArray[0];

		$lastName = isset($displayNameArray[1]) ? $displayNameArray[1] : $displayNameArray[0];

		if (!ine($customer, 'first_name')) {
			$customer['first_name'] = $firstName;
		}

		if (!ine($customer, 'last_name')) {
			$customer['last_name'] = $lastName;
		}

		//check if customer has company name than make it as commercial.
		$customer['is_commercial'] = ine($customer, 'company_name');

		if ($customer['is_commercial']) {
			$customer['first_name'] =  issetRetrun($customer, 'company_name') ?: $customer['first_name'] . ' ' . $customer['last_name'];
			$customer['company_name'] = "";
			$customer['last_name'] = "";
		}

		return $customer;
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

	private function mapPhonesInput($input = array())
	{

		$phones = [];
		//deafault phone number if number not found
		$phones[0]['label'] = 'phone';
		$phones[0]['number'] = 1111111111;
		$key = 0;

		if (isset($input['Phones']['Phone'])) {
			$number = preg_replace('/\D+/', '', $input['Phones']['Phone']);
			if (strlen($number) == 10) {
				$phones[$key]['label'] = 'phone';
				$phones[$key]['number'] = $number;
				$key++;
			}
		}

		if (isset($input['Phones']['Fax'])) {

			$number = preg_replace('/\D+/', '', $input['Phones']['Fax']);

			if (strlen($number) == 10) {

				$phones[$key]['label'] = 'fax';
				$phones[$key]['number'] = $number;
				$key++;
			}
		}

		if (isset($input['Phones']['AltPhone'])) {

			$number = preg_replace('/\D+/', '', $input['Phones']['AltPhone']);

			if (strlen($number) == 10) {

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

	public function saveCustomer(
		$customerData,
		$addressData,
		$phones,
		$isSameAsCustomerAddress,
		$billingAddressData = null,
		$geocodingRequired = false
	) {

		$existingCustomer = ine($customerData, 'id');

		if ($addressData) {
			ksort($addressData);
		}

		if ($billingAddressData) {
			ksort($billingAddressData);
		}

		$addressId = $this->customerRepo->qbSaveAddress($addressData, $geocodingRequired, $existingCustomer);

		$customerData['company_id'] = getScopeId();

		$billingAddressId = $addressId;

		if (!$isSameAsCustomerAddress) {
			$billingAddressId = $this->customerRepo->qbSaveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer);
		} else {
			$this->customerRepo->qbDeleteBillingAddress($billingAddressData, $addressId);
		}

		$customerData['address_id'] = $addressId;

		$customerData['billing_address_id'] = $billingAddressId;

		$customerData['solr_sync'] = false;

		if($existingCustomer) {

			$customerData['last_modified_by'] = Auth::user()->id;

			$customer = CustomerModal::withTrashed()->find($customerData['id']);

			unset($customerData['id']);

			$customer->update($customerData);

			$customer->phones()->delete();

			$this->customerRepo->qbAddPhones($phones, $customer->id);

			return $customer;

		} else {

			$customerData['referred_by'] = false;
			$customerData['created_by'] = Auth::user()->id;

			$customer = CustomerModal::create($customerData);

			$customer->qb_desktop_id = $customerData['qb_desktop_id'];
			$customer->qb_desktop_sequence_number = $customerData['qb_desktop_sequence_number'];
			$customer->origin = $this->getOrigin();
			$customer->save();

			$this->customerRepo->qbAddPhones($phones, $customer->id);

			return $customer;
		}
	}

	public function getParentCustomer($id)
	{
		$customer = QBOCustomer::where('qb_id', $id)
			->where('company_id', getScopeId())
			->first();

		// Customer
		if ($customer && !$customer->qb_parent_id) {
			return $customer->qb_id;
		}

		if ($customer) {
			$customer = QBOCustomer::where('qb_id', $customer->qb_parent_id)
				->where('company_id', getScopeId())
				->first();
		}

		// Job
		if ($customer && !$customer->qb_parent_id) {
			return $customer->qb_id;
		}

		if ($customer) {
			$customer = QBOCustomer::where('qb_id', $customer->qb_parent_id)
				->where('company_id', getScopeId())
				->first();
		}

		// Project
		if ($customer) {
			return $customer->qb_id;
		}

		return false;
	}

	public function getCustomerAllFinancials($id)
	{
		$customerIds = [];

		$customerIds = QBOCustomer::where('company_id', getScopeId())
			->where('qb_parent_id', $id)
			->pluck('qb_id')
			->toArray();

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

		$bills = QBDBill::where('company_id', getScopeId())
			->whereIn('customer_ref', (array)$customerIds)
			->pluck('amount_due')
			->toArray();

		$billAmount = array_sum($bills);
		$billCount = count($bills);

		$invoices = QBDInvoice::where('company_id', getScopeId())
			->whereIn('customer_ref', (array)$customerIds)
			->get();
		foreach ($invoices as $invoice) {
			$invoiceAmount += ($invoice->sub_total + $invoice->sales_tax_total);
		}
		$invoiceCount = count($invoices);

		$payments = QBDPayment::where('company_id', getScopeId())
			->whereIn('customer_ref', (array)$customerIds)
			->pluck('total_amount')
			->toArray();

		$paymentAmount = array_sum($payments);
		$paymentCount = count($payments);

		$credits = QBDCreditMemo::where('company_id', getScopeId())
			->whereIn('customer_ref', (array)$customerIds)
			->pluck('total_amount')
			->toArray();

		$creditAmount = array_sum($credits);
		$creditCount = count($credits);

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

	public function isDuplicate($phones = [], $fullName, $email, $companyName)
	{
		$phoneList = [];

		foreach ($phones as $phone) {

			if (ine($phone, 'number')) {

				$phoneList[] = $phone['number'];
			}
		}

		$customer = $this->customerRepo->findMatchingQBCustomer($phoneList, $fullName, $email, $companyName);

		return (bool) $customer;
	}

	public function mapCustomerInQuickBooks($customerId, $qbCustomer)
	{
		$customer = CustomerModal::find($customerId);
		$customer->qb_desktop_id = $qbCustomer['ListID'];
		$customer->qb_desktop_sequence_number = $qbCustomer['EditSequence'];
		$customer->save();

		$jpUnlinkCustomer = QuickbookUnlinkCustomer::where('company_id', $customer->company_id)
			->where('customer_id', $customer->id)
			->where('type', QuickbookUnlinkCustomer::QBD)
			->first();

		if($jpUnlinkCustomer){
			$jpUnlinkCustomer->delete();
		}

		$qbUnlinkCustomer = QuickbookUnlinkCustomer::where('company_id', $customer->company_id)
			->where('type', QuickbookUnlinkCustomer::QBD)
			->where('quickbook_id', $qbCustomer['ListID'])
			->first();

		if($qbUnlinkCustomer){
			$qbUnlinkCustomer->delete();
		}

		return $customer;
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

				if ($level != 0) {
					continue;
				}
				$addressMeta = [];
				$customnerFinancials = $this->getCustomerAllFinancials($item->getChildDataAt('CustomerRet ListID'));


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

	private function deleteInactiveCustomer($customerId)
	{
		QBOCustomer::where('company_id', getScopeId())
			->where('qb_id', $customerId)
			->delete();
		return true;
	}
}