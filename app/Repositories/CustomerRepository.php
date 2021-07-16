<?php

namespace App\Repositories;

use App\Events\CustomerRepAssigned;
use App\Exceptions\AccessForbiddenException;
use App\Models\Address;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Phone;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Queue;
use App\Models\Job;
use App\Models\CustomerCustomField;
use App\Exceptions\SystemReferralException;
use App\Models\Referral;
use App\Models\QuickbookUnlinkCustomer;
use App\Models\JobPayment;
use App\Models\JobCredit;
use App\Models\JobInvoice;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\VendorBill;
use App\Models\JobRefund;

class CustomerRepository extends ScopedRepository
{

    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $address;
    protected $phone;
    protected $scope;

    function __construct(Customer $model, Context $scope, Address $address, Phone $phone)
    {
        $this->model = $model;
        $this->address = $address;
        $this->phone = $phone;
        $this->scope = $scope;
    }

    public function getCustomerByEmail($email)
    {
        $customer = $this->model->where('email', '=', $email)->first();
        return $customer;
    }

    public function saveCustomer(
        $customerData,
        $addressData,
        $phones,
        $isSameAsCustomerAddress,
        $billingAddressData = null,
        $geocodingRequired = true,
        $flags = false,
        $customerContacts = [],
        $customFields = null
    ) {

        // if( $duplicate = $this->isDuplicateRecord($customerData, $phones, $addressData) ) {
        // 	throw new DuplicateRecordException($duplicate);
        // }

        //set company_name field empty if customer is a company
        if (isset($customerData['is_commercial']) && ($customerData['is_commercial'])) {
            $customerData['company_name'] = '';
            $customerData['last_name'] = '';
        }

        //create addresses...
        $existingCustomer = ine($customerData, 'id'); // edit case ..
        $addressId = $this->saveAddress($addressData, $geocodingRequired, $existingCustomer);

        $billingAddressId = $addressId;

        if (!$isSameAsCustomerAddress) {
            $billingAddressId = $this->saveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer);
        } else {
            $this->deleteBillingAddress($billingAddressData, $addressId);
        }

        $customerData['address_id'] = $addressId;
        $customerData['billing_address_id'] = $billingAddressId;
        $customerData['solr_sync'] = false;
        $customerData['quickbook_sync'] = false;
        $customerData['source_type'] = ine($customerData, 'source_type') ? $customerData['source_type'] : null;
        $customerData[$this->scope->column()] = $this->scope->id();
        //create or update customers...
        if ($customerData['id']) {
            $customer = $this->model->find($customerData['id']);

            // throw exception if user wants to update the referral of customer if system referral is selected.
            if (ine($customerData, 'referred_by') || ine($customerData, 'referred_by_type')) {
                if(!$customer->source_type) {
                    $systemReferral = Referral::systemReferral()->pluck('id')->toArray();
                    if(in_array($customerData['referred_by'], $systemReferral)){
                        throw new SystemReferralException(trans('response.error.mark_referral_as_zapier'));
                    }
                }

                if($customer->source_type == Customer::TYPE_ZAPIER) {
                    $customerData['referred_by'] = $customer->referred_by;
                    $customerData['referred_by_type'] = $customer->referred_by_type;
                    $customerData['referred_by_note'] = $customer->referred_by_note;
                }
            }
            $customerData['source_type'] = $customer->source_type;
            $customer->update($customerData);
            $customer->phones()->delete();
            $this->addPhones($phones, $customer->id);

            if(QuickBooks::isConnected() && $customer->quickbook_id){
				$task = QBOQueue::addTask(QuickBookTask::QUICKBOOKS_CUSTOMER_UPDATE, [
					'id' => $customer->id,
					'input' => $customerData
				], [
					'object_id' => $customer->id,
					'object' => 'Customer',
					'action' => QuickBookTask::UPDATE,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
			}

        } else {

            // throw exception if user wants to update the referral of customer if system referral is selected.
            if (ine($customerData, 'referred_by')) {
                $sourceType = ine($customerData, 'source_type') ? $customerData['source_type'] : null;
                if($sourceType != Customer::TYPE_ZAPIER) {
                    $id = $customerData['referred_by'];
                    $referral = Referral::where('id', $id)->systemReferral()->first();
                    if($referral) {
                        throw new SystemReferralException(trans('response.error.mark_referral_as_zapier'));
                    }
                }
            }
            $customer = $this->model->create($customerData);
            $this->addPhones($phones, $customer->id);
        }
        if ($flags) {
            $customer = $this->saveFlags($customer, array_filter($flags));
        }
        //delete exist contacts if any
        $customer->contacts()->delete();
        //save Customer Contacts
        if (!empty($customerContacts)) {
            foreach ($customerContacts as $key => $contactAttribute) {
                $contact = new CustomerContact($contactAttribute);
                $contact->customer()->associate($customer);
                $contact->save();
            }
        }

        // save customers custom fields
        if($customFields && is_array($customFields)) {
            $this->saveCustomFields($customer, $customFields);
        }

        return $customer;
    }

    public function getById($id, array $with = [])
    {

        $query = $this->make($with);

        $query->findOrFail($id);

        $query->select('customers.*');
		$query->attachNewFolderStructureKey();

		$customer = $query->where('customers.id', $id)->own()->first();

        if (!$customer) {
            throw new AccessForbiddenException(Lang::get('response.error.customer_access_forbidden'));
        }
        return $customer;
    }

    public function getFilteredCustomers($filters, $sortable = true)
    {

        $customers = $this->getCustomers($sortable, $filters);

        $this->applyFilters($customers, $filters);
        return $customers;
    }

    public function getCustomers($sortable = true, $params = [])
    {
        $customers = null;

        $includeData = $this->includeData($params);

        if ($sortable) {
            $customers = $this->make($includeData)->sortable();
        } else {
            $customers = $this->make($includeData);
        }

        if (!ine($params, 'keyword') && !ine($params, 'name') && !ine($params, 'customer_note')) {
            $customers->orderBy('customers.created_at', 'DESC');
        }

        $customers->leftJoin('jobs', function ($join) {
            $join->on('jobs.customer_id', '=', 'customers.id')
                ->whereNull('jobs.deleted_at');
        });

        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $lat = $params['lat'];
            $long = $params['long'];
            $customers->leftJoin(DB::raw("(select addresses.*,( 3959 * acos( cos( radians($lat) ) * cos( radians( addresses.lat ) )
					   * cos( radians(addresses.long) - radians($long)) + sin(radians($lat))
					   * sin( radians(addresses.lat)))) as distance from addresses) as addresses"), 'addresses.id', '=', 'customers.address_id');
        } else {
            $customers->leftJoin('addresses', 'addresses.id', '=', 'customers.address_id');
        }

        // calculate distance if required..
        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $customers->select(DB::raw('customers.*,addresses.distance as distance'));
        } else {
            $customers->select('customers.*');
        }

        $customers->groupBy('customers.id');

        return $customers;
    }

    public function changeRep(Customer $customer, $repId)
    {
        $oldRepId = $customer->rep_id;
        $customer->rep_id = $repId;
        $customer->save();

        //event for note added..
        $assignedBy = Auth::user();
        Event::fire('JobProgress.Customers.Events.CustomerRepAssigned', new CustomerRepAssigned($customer, $assignedBy, $repId, $oldRepId));
        return $customer;
    }

    public function getQuickbookCustomerList()
    {
        $quickbookLists = $this->make()->whereNotNull('quickbook_id')->pluck('quickbook_id')->toArray();

        return $quickbookLists;
    }


    public function isDuplicateRecord($data, $phones)
    {

        $phones = array_column($phones, 'number');
        $edit = ine($data, 'id') ? $data['id'] : 0;
        if (ine($data, 'email')) {
            $customerExist = $this->make()->whereEmail($data['email'])
                ->where('id', '!=', $edit)
                ->whereHas('phones', function ($q) use ($phones) {
                    $q->whereIn('number', $phones);
                })->count();
        } else {
            $customerExist = $this->make()
                ->where('id', '!=', $edit)
                ->whereFirstName($data['first_name'])
                ->whereLastName($data['last_name'])
                ->whereHas('phones', function ($q) use ($phones) {
                    $q->whereIn('number', $phones);
                })->count();
        }

        if ($customerExist) {
            return 'Duplicate Customer.';
        }

        return false;
    }

    public function getDuplicateCustomer($data, $phones)

    {
    	$phones = array_column($phones, 'number');
    	$edit = ine($data,'id') ? $data['id'] : 0;
    	if(ine($data, 'email')) {
	    	$customerExist = $this->make()->whereEmail($data['email'])
	    		->where('id', '!=', $edit)
	    		->whereHas('phones', function($q)use($phones){
	    			$q->whereIn('number', $phones);
	    		})->first();
    	} else {
	    	$customerExist = $this->make()
	    		->where('id', '!=', $edit)
	    		->whereFirstName($data['first_name'])
	    		->whereLastName($data['last_name'])
	    		->whereHas('phones', function($q) use($phones){
	    			$q->whereIn('number', $phones);
	    		})->first();
    	}

    	return $customerExist;
    }

    public function unlinkFromQuickbooks($customer, $meta = [])
    {
    	if(!$customer->quickbook_id && !$customer->qb_desktop_id){
    		return $customer;
    	}
    	$type = ine($meta, 'type') ? $meta['type'] : null;
    	$qbId = null;

    	$userId = Auth::user()->id;

    	if($type && $type == QuickbookUnlinkCustomer::QBD){
    		$qbId = $customer->qb_desktop_id;
    	}else if($type && $type ==QuickbookUnlinkCustomer::QBO){
    		$qbId = $customer->quickbook_id;
    	}

    	if(!$qbId){
    		return $customer;
    	}

    	$unlinkCustomer = QuickbookUnlinkCustomer::firstOrNew([
    		'company_id' => getScopeId(),
    		'customer_id' => $customer->id,
    		'quickbook_id' => $qbId,
    		'type' => $type,
    	]);

    	$unlinkCustomer->created_by = Auth::user()->id;
    	$unlinkCustomer->save();

    	if($type == QuickbookUnlinkCustomer::QBD){
			//Unlink its QBD entities
	    	$this->unlinkCustomerQBDEntities($customer);

	    	$customer->qb_desktop_id = null;
	    	$customer->qb_desktop_sequence_number = null;
	    	$customer->qb_desktop_delete = false;
	    	$customer->qb_desktop_sync_status = null;
	    	$customer->save();
	    	return $customer;
    	}

    	//Unlink its entities
    	$this->unlinkCustomerQuickbookEntities($customer);

    	$customer->quickbook_sync_status = null;
    	$customer->quickbook_sync_token = null;
    	$customer->quickbook_sync = false;
    	$customer->quickbook_id = null;
    	$customer->save();

    	return $customer ;
    }

    public function unlinkCustomerQuickbookEntities($customer)
    {
    	$jobs = Job::where('company_id', $customer->company_id)
    		->where('customer_id', $customer->id)
    		->whereNotNull('quickbook_id');

    	$jobIds = $jobs->pluck('id')->toArray();

    	if(!empty($jobIds)){

    		$data = [
    			'quickbook_id' => null,
	    		'quickbook_sync_status' => null,
	    		'quickbook_sync_token' => null,
	    		'quickbook_sync' => false,
	    	];

	    	$jobData = $data;
	    	$jobData['ghost_job'] = false;

	    	$jobs->update($jobData);

	    	JobInvoice::whereIn('job_id', $jobIds)
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('quickbook_invoice_id')
	    		->update([
	    			'quickbook_invoice_id' => null,
		    		'quickbook_sync_status' => null,
		    		'quickbook_sync_token' => null,
		    		'quickbook_sync' => false,
	    		]);

	    	JobPayment::whereIn('job_id', $jobIds)
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('quickbook_id')
	    		->update($data);

	    	JobCredit::whereIn('job_id', $jobIds)
	    		->where('company_id', getScopeId())
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('quickbook_id')
	    		->update($data);

	    	VendorBill::whereIn('job_id', $jobIds)
	    		->where('company_id', getScopeId())
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('quickbook_id')
	    		->update([
    			'quickbook_id' => null,
	    		'quickbook_sync_status' => null,
	    		'quickbook_sync_token' => null,
	    	]);

	    	JobRefund::whereIn('job_id', $jobIds)
	    		->where('company_id', getScopeId())
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('quickbook_id')
	    		->update([
    			'quickbook_id' => null,
	    		'quickbook_sync_status' => null,
	    		'quickbook_sync_token' => null,
	    	]);
		}
    	return ;
    }

    public function unlinkCustomerQBDEntities($customer)
    {
    	$jobs = Job::where('company_id', $customer->company_id)
    		->where('customer_id', $customer->id)
    		->whereNotNull('qb_desktop_id');

    	$jobIds = $jobs->pluck('id')->toArray();

    	if(!empty($jobIds)){

    		$data = [
    			'qb_desktop_txn_id' => null,
	    		'qb_desktop_sequence_number' => null,
	    		'qb_desktop_sync_status'=>null,
	    		'qb_desktop_delete' => false,
	    	];

	    	$jobData =[
    			'qb_desktop_id' => null,
	    		'qb_desktop_sequence_number' => null,
	    		'qb_desktop_sync_status'=>null,
	    		'qb_desktop_delete' => false,
	    		'ghost_job' => false,
	    	];

	    	$jobs->update($jobData);

	    	JobInvoice::whereIn('job_id', $jobIds)
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('qb_desktop_txn_id')
	    		->update($data);

	    	JobPayment::whereIn('job_id', $jobIds)
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('qb_desktop_txn_id')
	    		->update($data);

	    	JobCredit::whereIn('job_id', $jobIds)
	    		->where('company_id', getScopeId())
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('qb_desktop_txn_id')
	    		->update($data);

	    	VendorBill::whereIn('job_id', $jobIds)
	    		->where('company_id', getScopeId())
	    		->where('customer_id', $customer->id)
	    		->whereNotNull('qb_desktop_txn_id')
	    		->update($data);
	    		//to do
	    	// JobRefund::whereIn('job_id', $jobIds)
	    	// 	->where('company_id', getScopeId())
	    	// 	->where('customer_id', $customer->id)
	    	// 	->whereNotNull('qb_desktop_txn_id')
	    	// 	->update($data);
		}

    	return ;
    }

	/**
	 * add new customer address used in QBO.
	 * @param  Array $addressData | Address data
	 * @param  Bool $geocodingRequired | Geocoding required or not
	 * @param  Bool $existingCustomer | Address for new customer or existing..
	 * @return int | address id
	 */
	public function qbSaveAddress($addressData, $geocodingRequired, $existingCustomer = true)
	{
		$addressId = $this->saveAddress($addressData, $geocodingRequired, $existingCustomer);

		return $addressId;
	}

	/**
	 * save billing Address used in QBO.
	 * @param  Array $addressData | Address data
	 * @param  Bool $geocodingRequired | Geocoding required or not
	 * @param  Bool $existingCustomer | Address for new customer or existing..
	 * @return int | address id
	 */
	public function qbSaveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer = true)
	{
		$billingId = $this->saveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer);

		return $billingId;
	}

	/**
	 *	add new customer phones used in QBO.
	 *
	 *	@return void.
	 */

	public function qbAddPhones($phones, $customerId)
	{
		$this->addPhones($phones, $customerId);
	}

	public function getByQBId($id, array $with = array())
	{
		$query = $this->make($with);

		$customer = $query->withTrashed()->where('quickbook_id', $id)->first();

		return $customer;
	}

	public function findMatchingQBCustomer($phones, $email, $fullName, $companyName, $isQBD = false)
	{

		$qbColumn = 'quickbook_id';

		if($isQBD) {
			$qbColumn = 'qb_desktop_id';
		}

		$customer = Customer::on('mysql2')
			->where('company_id', getScopeId())
			->whereNull($qbColumn);

		$customer->orderBy('created_at', 'desc');
		$customerQueryExact = clone $customer;
		$customerQueryPhone = clone $customer;
		$customerQueryEmail = clone $customer;
		if($companyName) {
			$customer->where('is_commercial', true);
			$customer->where('first_name', $companyName);

			return $customer->first();
		}
		if($email) {
			$customerQueryExact->where('email', $email);
		} else {
			$customerQueryExact->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . trim($fullName)]);
		}
		$customerQueryExact->phones($phones);
		$customer = $customerQueryExact->first();

		if (!$customer) {
			if($email) {
				$customerQueryPhone->phones($phones);
				$customerQueryPhone->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . trim($fullName)]);
				$customer = $customerQueryPhone->first();
			}
			if (!$customer && $email && $fullName) {
				$customerQueryEmail->where('email', $email);
				$customerQueryEmail->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . trim($fullName)]);
				$customer = $customerQueryEmail->first();
			}
		}

		return $customer;
	}

	/************** Protected Section *****************/

	private function qbDeleteBillingAddress($billingAddressData, $addressId)
	{
		$this->deleteBillingAddress($billingAddressData, $addressId);
	}

    protected function applyFilters($query, $filters = [])
    {
        $query->own();

        if(ine($filters, 'deleted_customers')) {
			$query->onlyTrashed();
		}

        //keyword (by full name or address search)
        if (ine($filters, 'keyword')) {
            $query->keywordSearch($filters['keyword'], $this->scope->id());
            // (other filters will not apply in case of keyword filter)
        }

        // by email and full name
        if (ine($filters, 'query')) {
            $query->where(function ($query) use ($filters) {
                $query->where(function($query) use($filters) {
                    $query->where('email','Like','%'.$filters['query'].'%');
                    $query->orWhere('additional_emails','Like','%'.$filters['query'].'%');
                });
                $query->orWhereRaw("CONCAT(customers.first_name,' ',customers.last_name) LIKE ?", ['%' . $filters['query'] . '%']);
            })->where('email', '!=', '');
        }

        // has job or not..
        if (isset($filters['has_jobs'])) {
            if ($filters['has_jobs']) {
                $query->has('allJobs');
                // $query->whereHas('jobs', function($query){
                // 	$query->excludeLostJobs();
                // });
            } else {
                $query->has('allJobs', '=', 0);
            }
        }

        //distance range
        if (ine($filters, 'lat') && ine($filters, 'long') && ine($filters, 'distance')) {
            $query->having('addresses.distance', "<=", $filters['distance']);
        }

        //job stages
        if (ine($filters, 'trades')) {
            $query->whereIn('jobs.id', function ($query) use ($filters) {
                $query->select('job_id')->from('job_trade')->whereIn('trade_id', $filters['trades']);
            });
        }

        //job stages
        if (ine($filters, 'stages')) {
            $query->whereIn('jobs.id', function ($query) use ($filters) {
                $query->select('job_id')->from('job_workflow')->whereIn('current_stage', $filters['stages']);
            });
        }

        //job number
        if (ine($filters, 'job_number')) {
            $query->where('jobs.number', $filters['job_number']);
        }

        //job alt id
        if (ine($filters, 'job_alt_id')) {
            $query->where(DB::Raw('CONCAT(jobs.division_code, "-", jobs.alt_id)'), 'LIKE', '%'.$filters['job_alt_id'].'%');
        }

        if(ine($filters, 'job_address') || ine($filters, 'job_state_id')) {
			$query->leftJoin('addresses as job_address','job_address.id','=','jobs.address_id');
		}

        //job address
        if (ine($filters, 'job_address')) {
            $query->whereRaw("CONCAT(job_address.address,' ',job_address.city,' ',job_address.zip) LIKE ?", ['%' . $filters['job_address'] . '%']);
        }

        if(ine($filters, 'job_state_id')){
			$query->whereIn('job_address.state_id', (array)$filters['job_state_id']);
		}

        //customers phone numbers
        if (ine($filters, 'phone')) {
            $phone = $filters['phone'];
            $query->whereIn('customers.id', function ($query) use ($phone) {
                $query->select('customer_id')->from('phones')->whereIn('number', (array)$phone);
            });
        }

        //customer name
        if (ine($filters, 'name')) {
            $query->nameSearch($filters['name'], $this->scope->id());
        }

        //customer email
        if (ine($filters, 'email')) {
            $query->where('email', $filters['email']);
        }

        if (ine($filters, 'first_name') && ine($filters, 'or_last_name')) {
            $query->where(function ($query) use ($filters) {
                $query->where('customers.first_name', 'Like', '%' . $filters['first_name'] . '%');
                $query->orWhere('customers.last_name', 'Like', '%' . $filters['or_last_name'] . '%');
            });
        } else {
            //first name
            if (ine($filters, 'first_name')) {
                $query->firstName($filters['first_name']);
            }

            //last name
            if (ine($filters, 'last_name')) {
                $query->lastName($filters['last_name']);
            }

            //last name
            if (ine($filters, 'or_last_name')) {
                $query->where('customers.last_name', 'Like', '%' . $filters['or_last_name'] . '%');
                $query->orderByRaw(orderByCaseQuery('last_name', $filters['or_last_name']));
            }
        }

        //city
        if (ine($filters, 'city')) {
            $query->where('addresses.city', $filters['city']);
        }

        //cities
        if (ine($filters, 'cities')) {
            $query->whereIn('addresses.city', $filters['cities']);
        }

        //country_id
        if (ine($filters, 'country_id')) {
            $query->where('addresses.country_id', $filters['country_id']);
        }

        //customer address
        if (ine($filters, 'address')) {
            $query->whereRaw("CONCAT(addresses.address,' ',addresses.city,' ',addresses.zip) LIKE ?", ['%' . $filters['address'] . '%']);
        }

        //customer rep_ids
        if (ine($filters, 'rep_ids')) {
            $query->where(function ($query) use ($filters) {
                $ids = (array)$filters['rep_ids'];
                if (in_array('unassigned', $ids)) {
                    $query->where('customers.rep_id', 0);
                    $ids = unsetByValue($ids, 'unassigned');
                }
                $query->orWhereIn('customers.rep_id', $ids);
            });
        }

        //customer id
        if (ine($filters, 'id')) {
            $query->where('customers.id', '=', $filters['id']);
        }

        //customer id
        if(ine($filters, 'customer_note')){
            $query->noteSearch($filters['customer_note']);
        }

        //customer id
        if (ine($filters, 'states')) {
            $query->whereIn('customers.address_id', function ($query) use ($filters) {
                $query->select('id')->from('addresses')->whereIn('state_id', (array)$filters['states']);
            });
        }

        if(ine($filters, 'state_id')){
			$query->whereIn('customers.address_id',function($query) use($filters){
				$query->select('id')->from('addresses')->whereIn('state_id',(array)$filters['state_id']);
			});
		}

        // job_rep_ids
        if (ine($filters, 'job_rep_ids')) {
            $query->whereIn('customers.id', function ($query) use ($filters) {
                $query->select('customer_id')->from('jobs')->whereIn('id', function ($query) use ($filters) {
                    $query->select('job_id')->from('job_rep')->whereIn('rep_id', (array)$filters['job_rep_ids']);
                });
            });
        }

        // customer id (which not required)
        if (ine($filters, 'not_required')) {
            $query->where('customers.id', '!=', $filters['not_required']);
        }

        // zip code
        if (ine($filters, 'zip_code')) {
            $query->where('addresses.zip', $filters['zip_code']);
        }

        // customer flag
        if (ine($filters, 'flag_ids')) {
            $query->flags($filters['flag_ids']);
        }

        // customer referred by
        if (ine($filters, 'referred_by_type')) {
            $query->where(function ($query) use ($filters) {
                $types = (array)$filters['referred_by_type'];

                if (in_array('referral', $types) && ine($filters, 'referred_by')) {
                    unset($types[array_search('referral', $types)]);
                    $query->whereReferredByType('referral')
                        ->whereIn('referred_by', (array)$filters['referred_by']);
                }
                $query->orWhereIn('referred_by_type', $types);
            });

            // $query->whereReferredByType( $filters['referred_by_type']);
            // if (ine($filters, 'referred_by')) {
            // 	$query->whereReferredBy( $filters['referred_by']);
            // }
        }

        //company name
        if (ine($filters, 'company_name')) {
            $query->where('company_name', 'Like', '%' . $filters['company_name'] . '%');
            $query->orderByRaw(orderByCaseQuery('company_name', $filters['company_name']));
        }

        //properity name
        if (ine($filters, 'property_name')) {
            $query->where('property_name', 'Like', '%' . $filters['property_name'] . '%');
            $query->orderByRaw(orderByCaseQuery('property_name', $filters['property_name']));
        }

        //management name
        if (ine($filters, 'management_company')) {
            $query->where('management_company', 'Like', '%' . $filters['management_company'] . '%');
            $query->orderByRaw(orderByCaseQuery('management_company', $filters['management_company']));
        }

        //job_contact_person
        if (ine($filters, 'job_contact_person')) {
            $query->jobContactPerson($filters['job_contact_person']);
        }

        //commercial filter
        if (isset($filters['commercial'])) {
            $query->commercial($filters['commercial']);
        }

        if(ine($filters,'deleted_customers_duration')) {
			$startDate = ine($filters, 'start_date') ? $filters['start_date'] : null;
			$endDate = ine($filters, 'end_date') ? $filters['end_date'] : null;

			$query->deletedCustomers($startDate, $endDate);
        }


		if (ine($filters, 'division_ids')) {
			$query->divisions($filters['division_ids'], ine($filters, 'with_archived'));
		}

        // date range filters
        if((ine($filters,'start_date') || ine($filters,'end_date'))
            && ine($filters, 'date_range_type')) {
            $startDate = isSetNotEmpty($filters, 'start_date') ?: null;
            $endDate = isSetNotEmpty($filters, 'end_date') ?: null;
            switch ($filters['date_range_type']) {
                case 'customer_created_date':
                    $query->createdDate($startDate, $endDate);
                    break;
                case 'customer_updated_date':
                    $query->updatedDate($startDate, $endDate);
                break;
            }
        }
    }

    /** Private Functions **/

    /**
     * add new customer address.
     * @param  Array $addressData | Address data
     * @param  Bool $geocodingRequired | Geocoding required or not
     * @param  Bool $existingCustomer | Address for new customer or existing..
     * @return int | address id
     */
    private function saveAddress($addressData, $geocodingRequired, $existingCustomer = true)
    {
        $addressData[$this->scope->column()] = $this->scope->id();

        // if customer is not an existing customer create new address by unset the address id key..
        if (!$existingCustomer && isset($addressData['id'])) {
            unset($addressData['id']);
        }

        if (!isset($addressData['id']) || empty($addressData['id'])) {
            $address = $this->address->create($addressData);
        } else {
            $address = $this->address->find($addressData['id']);
            if ($address) {
                $addressData['geocoding_error'] = false;
                $address->update($addressData);
            } else {
                $address = $this->address->create($addressData);
            }
        }

        if($geocodingRequired && !($address->lat && $address->long)) {
            $this->attachGeoLocation($address);
        }

        return $address->id;
    }

    /**
     * save billing Address.
     * @param  Array $addressData | Address data
     * @param  Bool $geocodingRequired | Geocoding required or not
     * @param  Bool $existingCustomer | Address for new customer or existing..
     * @return int | address id
     */
    private function saveBillingAddress($billingAddressData, $addressId, $geocodingRequired, $existingCustomer = true)
    {
        $billingAddressData[$this->scope->column()] = $this->scope->id();

        // if customer is not an existing customer create new address by unset the address id key..
        if (!$existingCustomer && isset($billingAddressData['id'])) {
            unset($billingAddressData['id']);
        }

        if (!isset($billingAddressData['id']) || empty($billingAddressData['id'])) {
            $address = $this->address->create($billingAddressData);
        } else {
            $address = $this->address->find($billingAddressData['id']);
            if (!empty($address) && ($billingAddressData['id'] != $addressId)) {
                $billingAddressData['geocoding_error'] = false;
                $address->update($billingAddressData);
            } else {
                $address = $this->address->create($billingAddressData);
            }
        }

        if ($geocodingRequired && (!ine($billingAddressData, 'lat') || !ine($billingAddressData, 'long'))) {
            $this->attachGeoLocation($address);
        }

        return $address->id;
    }

    private function deleteBillingAddress($billingAddressData, $addressId)
    {
        if (!isset($billingAddressData['id'])) {
            return;
        }
        if ($billingAddressData['id'] != $addressId) {
            Address::where('id', $billingAddressData['id'])->delete();
        }
    }

    /**
     *  add new customer phones.
     *
     * @return void.
     */
    private function addPhones($phones, $customerId)
    {
        foreach ($phones as $phone) {
            $phone['customer_id'] = $customerId;
            if (ine($phone, 'label') && ine($phone, 'number')) {
                //remove  special character, alphabets, and special symbols
                $phone['number'] = preg_replace('/\D/', '', $phone['number']);
                $this->phone->create($phone);
            }
        }
    }

    /*
	 * attach geo location
	*/
    private function attachGeoLocation(Address $address)
    {
        //$geocode = Geocoder::geocode('525 3rd St #100, Beloit, WI 53511, United States');
        try {
            Queue::push('\App\Handlers\Events\CustomerQueueHandler@attachGeoLocation', ['address_id' => $address->id]);
        } catch (\Exception $e) {
            // No exception will be thrown here
            Log::error('Customer Address - Geocoder Error: ' . $e->getMessage());
        }
    }


    private function distanceCalculation($query, $data)
    {
        $lat = $data['lat'];
        $long = $data['long'];
        $query->select(DB::raw('customers.*,
			( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( addresses.lat ) )
		   * cos( radians(addresses.long) - radians(' . $long . ')) + sin(radians(' . $lat . '))
		   * sin( radians(addresses.lat)))) AS distance'));
    }

    private function saveFlags($customer, $flags)
    {
        $customer->flags()->detach();
        if (!empty($flags)) {
            $customer->flags()->attach($flags);
        }
        return $customer;
    }

    /**
     * save customers custom fields
     * @param  Customer $customer
     * @param  Array $fields
     */
    private function saveCustomFields($customer, $fields)
    {
        $data = [];
        $customer->customFields()->delete();
        if(empty($fields)) return false;
        foreach ($fields as $key => $value) {
            if(!ine($value, 'name')) continue;
            $value['value'] = isset($value['value']) ? $value['value'] : '';
            $value['type']  = ine($value, 'type') ? $value['type'] : CustomerCustomField::STRING_TYPE;
            $data[] = new CustomerCustomField($value);
        }
        if(empty($data)) return false;
        $customer->customFields()->saveMany($data);
    }

    /**
	 * includeData
	 * @param  Array $input | Input Array
	 * @return Array
	 */
	private function includeData($input = [])
	{
		$with = [];

		$includes = isset($input['includes']) ? $input['includes'] : [];

        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('jobs.division', $includes)) {
			$with[] = 'jobs.division';
        }

        if(in_array('flags', $includes)) {
			$with[] = 'flags';
		}

        if(in_array('flags.color', $includes)) {
			$with[] = 'flags.color';
		}

        if(in_array('meta', $includes)) {
			$with[] = 'customerMeta';
		}

		return $with;
	}
}
