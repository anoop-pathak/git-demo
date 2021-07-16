<?php

namespace App\Services\QuickBookDesktop;

use App\Models\Job;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Exception;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Models\QuickBook;
use App\Models\QuickbookUnlinkCustomer;
use Settings;

class QBDesktopCustomerJob extends BaseHandler
{
	use CustomerAccountHandlerTrait;

	public function __construct()
	{
		parent::__construct();
	}

    public function addCustomerRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$customer = Customer::find($ID);

			if (!$customer) {
				$this->task->markFailed('Customer not found.');
				return QUICKBOOKS_NOOP;
			}

			$billingAddress = $customer->billing;
			$state = ($billingAddress && ($state = $billingAddress->state)) ? $state->code : null;
			$country = ($billingAddress && ($country = $billingAddress->country)) ? $country->name : null;
			$numbers = $customer->phones()->lists('number', 'label');

			$company = $customer->company;
			$countryCode = $company->country->code;

			$phone = ine($numbers, 'phone') ? phoneNumberFormat($numbers['phone'], $countryCode) : null;
			$fax   = ine($numbers, 'fax') ? phoneNumberFormat($numbers['fax'], $countryCode) : null;

			//Generate a QBXML object
			$customerQBXML = new \QuickBooks_QBXML_Object_Customer();

			$customerQBXML->setFirstName($customer->first_name);
			$customerQBXML->setLastName($customer->last_name);
			$customerQBXML->setNotes(substr($customer->note, 0, 4095)); //4095 max limit

			if ($customer->is_commercial) {
				$customerQBXML->setFirstName(" ");
				$customerQBXML->setLastName(" ");
			}
			$settings = Settings::get('QUICKBOOK_ONLINE');
			$displayNameFormat = $settings['customer_display_name_format'];

			if(($displayNameFormat == QuickBook::LAST_NAME_FIRST_NAME)
				&& !$customer->is_commercial
			){
				$displayName = $customer->last_name .' '.$customer->first_name .' '.'(' . $customer->id.')';
				$customerQBXML->setFullName($displayName);
			} else{
				$customerQBXML->setFullName($customer->getQBDCustomerName());
			}

			$customerQBXML->setCompanyName($customer->getCompanyName());
			$customerQBXML->setEmail($customer->email);
			$customerQBXML->setBillAddress(
				$billingAddress->address,
				$billingAddress->address_line_1,
				null,
				null,
				null,
				$billingAddress->city,
				$state,
				null,
				$billingAddress->zip,
				$country
			);

			$customerQBXML->setFax($fax);
			$customerQBXML->setPhone($phone);

			if ($customer->qb_desktop_id) {
				$customerQBXML->set('ListID', $customer->qb_desktop_id);
				$customerQBXML->set('EditSequence', $customer->qb_desktop_sequence_number);
				$qbxml = $customerQBXML->asQBXML('CustomerModRq');
			} else {
				$qbxml = $customerQBXML->asQBXML('CustomerAddRq');
			}

			//Format the proper QBXML to return to the web connector
			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;
		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
		}
    }

    public function addCustomerResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$customer = Customer::find($ID);

		DB::table('customers')->where('id', $ID)->update([
			'qb_desktop_id'              => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence']
		]);

		 //delete unlink entry if this customer was ever unlinked earlier
        if($customer->unlinkQBDCustomer){
            QuickbookUnlinkCustomer::where('company_id', $customer->company_id)
                ->where('customer_id', $customer->id)
                ->delete();
        }

		$customer->qb_desktop_id = $idents['ListID'];

		$this->task->markSuccess($customer);
    }

    public function customerQueryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$customer = Customer::find($ID);

		if (!$customer) {
			$this->task->markFailed('Customer not found.');
			return QUICKBOOKS_NOOP;
		}

		//fix: extra parmeter 'search_by_name' added if it is comming from error handler to prevent going in the loop.
		// if (!$customer->qb_desktop_id && !ine($extra, 'search_by_name')) {
		// 	$this->task->markFailed('Customer not synced found.');
		// 	$this->resynchCustomerAccount($ID, QuickBookDesktopTask::QUEUE_HANDLER_EVENT);
		// 	return QUICKBOOKS_NOOP;
		// }

		$customerQBXML = new \QuickBooks_QBXML_Object_Customer();
		if(ine($extra, 'search_by_name')) {
			$customerQBXML->set('FullName', substr($customer->getQBDCustomerName(), 0, 41));
		} elseif(empty($extra) && $customer->qb_desktop_id) {
			$customerQBXML->set('ListID', $customer->qb_desktop_id);
		} else {
			$this->task->markFailed('Customer search is not valid.');
			return QUICKBOOKS_NOOP;
		}

		$qbxml = $customerQBXML->asQBXML('CustomerQueryRq');
		$xml = QBDesktopUtilities::formatForOutput($qbxml);

		return $xml;
    }

    public function customerQueryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {

        $this->setTask($this->getTask($requestID));

		$customer = Customer::where('id', $ID)->first();

		DB::table('customers')->where('id', $ID)->update([
			'qb_desktop_id' => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence']
		]);

		$customer->qb_desktop_id = $idents['ListID'];

		$this->task->markSuccess($customer);
    }

    public function addJobRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$job = Job::find($ID);

			$customer = $job->customer;

			if ($job->isGhostJob()) {
				$this->task->markFailed('Ghost Job can not be synced found.');
				return QUICKBOOKS_NOOP;
			}

			if (!$customer) {
				$this->task->markFailed('Customer not found.');
				return QUICKBOOKS_NOOP;
			}

			if (!$customer->qb_desktop_id) {
				$this->task->markFailed('Customer not synced found.');
				// $this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::QUEUE_HANDLER_EVENT);
				return QUICKBOOKS_NOOP;
			}

			$parentId = $customer->qb_desktop_id;

			if ($job->isProject()) {
				$parentId = $job->parentJob->qb_desktop_id;
			}

			if (!$parentId) {
				$this->task->markFailed('List id not found.');
				return QUICKBOOKS_NOOP;
			}

			$jobAddress  = $job->address;
			$state   = ($jobAddress && ($state = $jobAddress->state)) ? $state->code : null;
			$country = ($jobAddress && ($country = $jobAddress->country)) ? $country->name : null;

			//Generate a QBXML object
			$jobQBXML = new \QuickBooks_QBXML_Object_Customer();

			$jobQBXML->setFirstName($customer->first_name);
			$jobQBXML->setLastName($customer->last_name);

			if ($customer->is_commercial) {
				$jobQBXML->setFirstName(" ");
				$jobQBXML->setLastName(" ");
			}

			$jobQBXML->setCompanyName($customer->getCompanyName());
			$jobQBXML->setFullName($job->getQuickbookDisplayName());

			$jobQBXML->setBillAddress(
				$jobAddress->address,
				$jobAddress->address_line_1,
				null,
				null,
				null,
				$jobAddress->city,
				$state,
				null,
				$jobAddress->zip,
				$country
			);

			$jobQBXML->setParentListID($parentId);
			$desc = preg_replace('/\s+/', ' ', substr($job->description, 0, 99));
			$jobQBXML->set('JobDesc', $desc);

			if ($job->qb_desktop_id) {
				$jobQBXML->set('ListID', $job->qb_desktop_id);
				$jobQBXML->set('EditSequence', $job->qb_desktop_sequence_number);
				$qbxml = $jobQBXML->asQBXML('CustomerModRq');
			} else {
				$qbxml = $jobQBXML->asQBXML('CustomerAddRq');
			}

			//Format the proper QBXML to return to the web connector
			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;

		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
		}
    }

    public function addJobResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$job = Job::find($ID);

		DB::table('jobs')->where('id', $ID)->update([
			'qb_desktop_id' => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence']
		]);

		$job->qb_desktop_id = $idents['ListID'];

		$this->task->markSuccess($job);
    }

    public function jobQueryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		// $customer = Customer::find($ID);
		$job = Job::find($ID);

		if (!$job) {
			$this->task->markFailed('job not found.');
			return QUICKBOOKS_NOOP;
		}

		if ($job->isGhostJob()) {
			$this->task->markFailed('Ghost Job can not be queried.');
			return QUICKBOOKS_NOOP;
		}

		// if (!$job->qb_desktop_id) {
		// 	$this->task->markFailed('job not synced found.');
		// 	$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::QUEUE_HANDLER_EVENT);
		// 	return QUICKBOOKS_NOOP;
		// }

		$customerQBXML = new \QuickBooks_QBXML_Object_Customer();

		if ($job && ine($extra, 'sarch_by_name')) {
			$customer = $job->customer;
			$name = $customer->getQBDCustomerName();
			$parentJob = $job->parentJob;
			if($parentJob) {
				$parentName = substr($parentJob->getQuickbookDisplayName(), 0, 41);
				$name .= ":{$parentName}";
			}
			$jobName = substr($job->getQuickbookDisplayName(), 0, 41);
			$name .=":{$jobName}";
			$customerQBXML->set('FullName', $name);
		} elseif (empty($extra) && ($job->qb_desktop_id)) {
			$customerQBXML->set('ListID', $job->qb_desktop_id);
		} else {
			$this->task->markFailed('Job search is not valid.');
			return QUICKBOOKS_NOOP;
		}

		$qbxml = $customerQBXML->asQBXML('CustomerQueryRq');

		$xml = QBDesktopUtilities::formatForOutput($qbxml);

		return $xml;
    }

    public function jobQueryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {

        $this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$job = Job::where('id', $ID)->first();

		DB::table('jobs')->where('id', $ID)->update([
			'qb_desktop_id' => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence'],
		]);

		$job->qb_desktop_id = $idents['ListID'];

		$this->task->markSuccess($job);

		// $Queue = new \QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $user);
		// $Queue->enqueue(QUICKBOOKS_ADD_JOB, $ID, QBDesktopUtilities::QB_UPDATE_JOB_PRIORITY, null, $user);
    }
}
