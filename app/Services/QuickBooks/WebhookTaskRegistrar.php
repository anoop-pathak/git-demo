<?php

namespace App\Services\QuickBooks;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Exception;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Services\QuickBooks\Facades\Refund as QBRefund;
use App\Models\QuickbookWebhookEntry;
use App\Models\QuickbookUnlinkCustomer;
use App\Models\QuickBookStagedEntry;
use App\Models\Subscription;
use App\Models\Customer as CustomerModel;
use App\Services\QuickBooks\Entity\Bill;
/**
 * @author Ankit <ankit@logicielsolutions.co.in>
 * This class is responsible for converting webhook entries
 * into tasks. This exposes only one public method register which
 * is expected to be called repeatedly.
 */
class WebhookTaskRegistrar{


	/**
	 * Loop through all pending webhook entries which are yet to be converted
	 * to task one by one.
	 * Acquires a lock for the entry for a short while for changing the status
	 * @return void
	 */
    public function register($howMany)
	{
		try {

			$entries = null;

			//lock one pending tasks
			app('db')->transaction(function () use (&$entries, $howMany) {
				//Get 1 pending tasks
				$entries = $this->pollEntry($howMany);

				$entry_ids = $entries->pluck('id')->toArray();

                if(sizeof($entry_ids) > 0){
					QuickbookWebhookEntry::whereIn('id', $entry_ids)->update(['status'=> QuickbookWebhookEntry::STATUS_PROCESSED]);
				}
			});

			// process the task
			foreach($entries as $entry){
				// Log::info($entry);
				$this->registerTask($entry);
			}

		} catch (Exception $e) {

			Log::error("QuickBook Task Enqueue: Error", [(string) $e]);
		}
		
	}


	/**
	 * Poll one record from webhook entry table and return it
	 *
	 * @return QuickbookWebhookEnntry
	 */	
    public function pollEntry($limit){
        
        $entries = QuickbookWebhookEntry::where('status', QuickbookWebhookEntry::STATUS_PENDING)
            ->with('webhook')
            ->orderBy('object_updated_at', 'asc') // make sure sequencial updates because webhook could be out of order
            ->lockForUpdate()
            ->limit($limit)
            ->get();

        return $entries;
    }

	/**
	 * Convert webhook entry into tasks after performaing 
	 * all the neccessary validation. sometimes one entry can result in creation of 2 tasks
	 * one additional task is created for updating customer dump table
	 *
	 * @param QuickbookWebhookEntry $entry
	 * @return void
	 */
	private function registerTask($entry)
	{
		try {

			$entryArray = $entry->toArray();


			if(!QuickBooks::setCompanyScope($entry->realm_id)) {

				Log::info('QuickBook AddTask: unable to set company scope.', $entryArray);

				return false;
            }

			$token =QuickBooks::getToken();

			if(!$token){
				return $entry->abandon("Quickbooks token not found");
			}
			
			
			// $this->directTempTriggers($entry);

			$taskName = $this->getQuickBookTaskName($entry);


			if(!$this->checkCompanySubscription()){
				return $entry->abandon("Company is inactive");
			}

			$this->registerQBODumpTask($entry);

			$settings = Quickbooks::getQuickBookSettings();
                
			if($this->isTwoWayDisabled($settings)) {
				return $entry->abandon("two way synch disabled");
			}

			/**
			 * @todo We'll break up validateEntry
			 */
			$errors = $this->validateEntry($entryArray);
			if(!empty($errors)) {
				return $entry->abandon("validate entry failed: ".$errors[0]);
			}

			if($this->isAlreadyRegistered($entry)){
				return $entry->abandon("this entity was already registered");

			}

			// Prevent loop 
			if($this->isTaksOriginatedFromJP($entry)){
				return $entry->abandon("This entity in jp was created from JP originally");

			}
			

			$task = QBOQueue::addTask($taskName, $entryArray, [
				'object' => $entryArray['object_type'],
				'object_id' => $entryArray['object_id'],
				'origin' => QuickBookTask::ORIGIN_QB,
				'action' => $entryArray['operation'],
				'status' => QuickBookTask::STATUS_PENDING,
				'created_source' => $this->getSource($entryArray),
				'quickbook_webhook_id' => ($entryArray['quickbook_webhook_id']) ? $entryArray['quickbook_webhook_id']: null,
				'quickbook_webhook_entry_id' => $entryArray['id']
			]);

			if(!$task){
				$entry->markFailed();			
			}
			

		} catch(Exception $e) {
			$entry->markFailed();
			Log::error('QuickBook AddTask: Error', [(string) $e]);
		}		
    }
    
    private function getSource($entryArray){
        $createdSource = QuickBookTask::WEBHOOK_EVENT;

        if(ine($entryArray, 'extra') && ine($entryArray['extra'], 'queued_by')) {	
            
            $createdSource = QuickBookTask::POLL_EVENT;
        }
        return $createdSource;
	}
	
    /***
     * Get QuickBook task name.
     */
    private function getQuickBookTaskName($entry)
    {
        $name = $entry->object_type . ' ' .$entry->operation;

        return $name;
	}
	
	private function isAlreadyRegistered($entry){
		if ($entry->operation == 'Create' || $entry->operation == 'Delete') {

			$task = QBOQueue::getTask([
				'name' => $this->getQuickBookTaskName($entry),
				'company_id' => getScopeId(),
				'object' => $entry->object_type,
				'object_id' => $entry->object_id,
				'origin' => QuickBookTask::ORIGIN_QB
			]);

			if ($task) {
				return true;
			}

			return false;
		}
	}


	private function isTaksOriginatedFromJP($entry){

		if ($entry->operation == 'Create' || $entry->operation == 'Delete') {

			$objects = [$entry->object_type];
			if($entry->object_type == 'Customer') {
				$objects = [$entry->object_type, QuickBookTask::JOB];
			}

			$task = QuickBookTask::where([
				'company_id' => getScopeId(),
				'qb_object_id' => $entry->object_id,
				'origin' => QuickBookTask::ORIGIN_JP,
				'action' => $entry->operation,
			])->whereIn('object', $objects)->first();
	
			if ($task) {
				return true;
			}
		}

		return false;

	}

	private function isTwoWayDisabled($settings){

		if(ine($settings, 'sync_type') && ($settings['sync_type'] != 'two_way')) {
			return true;
		}
		return false;
	}

	private function validateEntry($entry)
	{
		$entity = null;

		$customerId = null;

		$qbId = $entry['object_id'];

		$isCustomer = false;

		$errors = [];
		$entityObject = null;

		if($entry['operation'] == 'Emailed'){
			$errors[] = 'Invalid Operation';
			return $errors;
		}

		if(in_array($entry['object_type'], [QuickBookTask::ACCOUNT, QuickBookTask::VENDOR])){
			return $errors;
		}


		if ($entry['operation'] != QuickBookTask::DELETE) {

			$response = Quickbooks::findById(strtolower($entry['object_type']), $entry['object_id']);

			if (ine($response, 'entity')) {
				$entity = $response['entity'];
				$entityObject = $entity;
				$entity = Quickbooks::toArray($entity);

				if($entity && isset($entity['Job']) && $entity['Job'] == 'false') {
					$isCustomer = true;
				}
			}
		}

		if($entry['operation'] == QuickBookTask::UPDATE) {

			if ($entry['object_type'] == QuickBookTask::INVOICE) {

				$invoice = Quickbooks::getJobInvoiceByQBId($qbId);

				if ($invoice && $invoice->quickbook_sync_token == $entity['SyncToken']) {

					$errors[] = 'Already updated';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::PAYMENT) {

				$payment = Quickbooks::getJobPaymentByQBId($qbId);

				if ($payment && $payment->quickbook_sync_token == $entity['SyncToken']) {

					$errors[] = 'Already updated';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::CREDIT_MEMO) {

				$jobCredit = Quickbooks::getJobCreditByQBId($qbId);

				if ($jobCredit && $jobCredit->quickbook_sync_token == $entity['SyncToken']) {

					$errors[] = 'Already updated';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::CUSTOMER 
				&& isset($entity['Job']) && $entity['Job'] == 'true') {

				$job = Quickbooks::getJobByQBId($qbId);

				if ($job && $job->quickbook_sync_token == $entity['SyncToken']) {

					$errors[] = 'Already updated';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::CUSTOMER 
				&& isset($entity['Job']) && $entity['Job'] == 'false') {

				$customer = CustomerModel::where('quickbook_id', $qbId)
					->where('company_id', getScopeId())->first();
				
				if($customer && $customer->quickbook_sync_token == $entity['SyncToken']) {
					
					$errors[] = 'Already updated';
					return $errors;
				}
			} elseif ($entry['object_type'] == QuickBookTask::BILL) {

				$bill = Quickbooks::getJobBillByQBId($qbId);

				if ($bill && $bill->quickbook_sync_token == $entity['SyncToken']) {

					$errors[] = 'Already updated';
					return $errors;
				}

			} elseif ($entry['object_type'] == QuickBookTask::REFUND_RECEIPT) {

				$jobRefund = QBRefund::getJpEntity($qbId);

				if ($jobRefund && $jobRefund->quickbook_sync_token == $entity['SyncToken']) {

					$errors[] = 'Already updated';
					return $errors;
				}

			}
		}

		if($entry['operation'] == QuickBookTask::CREATE) {

			if ($entry['object_type'] == QuickBookTask::INVOICE) {

				$invoice = Quickbooks::getJobInvoiceByQBId($qbId);

				if ($invoice) {

					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::PAYMENT) {

				$payment = Quickbooks::getJobPaymentByQBId($qbId);

				if ($payment) {

					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::CREDIT_MEMO) {

				$jobCredit = Quickbooks::getJobCreditByQBId($qbId);

				if ($jobCredit) {

					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::REFUND_RECEIPT) {

				$jobRefund = QBRefund::getJpEntity($qbId);

				if ($jobRefund) {

					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::CUSTOMER
				&& isset($entity['Job']) && $entity['Job'] == 'true') {

				$job = Quickbooks::getJobByQBId($qbId);

				if ($job) {

					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}

			} else if ($entry['object_type'] == QuickBookTask::CUSTOMER
				&& isset($entity['Job']) && $entity['Job'] == 'false') {

				$customer = CustomerModel::where('quickbook_id', $qbId)
					->where('company_id', getScopeId())->first();
				if($customer) {
					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}
			} elseif($entry['object_type'] == QuickBookTask::BILL) {

				$bill = Quickbooks::getJobBillByQBId($qbId);

				if ($bill) {

					$errors[] = 'This entity was created from JP originally';
					return $errors;
				}

			}
		}

		if($isCustomer && ($entry['object_type'] == QuickBookTask::CUSTOMER)) {

			$customerId = Quickbooks::getCustomerId($entry['object_id']);

		} elseif(!$isCustomer
			&& $entity
			&& ($entry['object_type'] == QuickBookTask::CUSTOMER)
		) {

			$customerId = Quickbooks::getCustomerId($entity['ParentRef']);
		}elseif(!$isCustomer
			&& $entityObject
			&& ($entry['object_type'] == QuickBookTask::BILL)
		) {
			$customerIds = QBBill::getCustomerId($entityObject);

			if(count($customerIds) != 1){
				$errors[] = 'Invalid Bill.';
				return $errors;
			}

			$customerId = Quickbooks::getCustomerId($customerIds[0]);
		} elseif($entity && ($entry['operation'] != QuickBookTask::DELETE) && ine($entity, 'CustomerRef')) {
				
			$customerId = Quickbooks::getCustomerId($entity['CustomerRef']);
		}

		if(Quickbooks::isControlledSyncEnabled()){			
			if(!$customerId){
				$errors[] = 'Controlled Sync Setting Enabled.';
				return $errors;
			}

			$customer = CustomerModel::where('quickbook_id', $customerId)
				->where('company_id', getScopeId())->first();
			if(!$customer){
				$errors[] = 'Controlled Sync Setting Enabled.';
				return $errors;
			}

		}

		if ($customerId) {

			$unlinkCustomer = QuickbookUnlinkCustomer::where('company_id', getScopeId())
				->where('quickbook_id', $customerId)
				->first();

			if ($unlinkCustomer) {

				$errors[] = 'Unlinked Customer event.';

				return $errors;
			}
		}

		if ($entity && !$isCustomer && $customerId) {

			$stagedCustomer = QuickBookStagedEntry::where('company_id', getScopeId())
				->where('object_type', 'Customer')
				->where('object_id', $customerId)
				->first();

			if ($stagedCustomer) {

				$errors[] = 'Staged customer event.';

				return $errors;
			}
		}

		return $errors;
	}

    public function registerQBODumpTask($entry){
        $operation = $entry->operation;
        
        // switch ($entry->object_type) {
        //     case 'Invoice':
        //         $operation = 'invoiceCountUpdate';
        //         break;
        //     case 'CreditMemo':
        //         $operation = 'creditCountUpdate';
        //         break;
        //     case 'Payment':
        //         $operation = 'paymentCountUpdate';
        //         break;
        // }

        // if(in_array($entry->object_type, ['Invoice', 'CreditMemo', 'Customer', 'Payment'])) {
		if(in_array($entry->object_type, ['Customer'])) {

            QBOQueue::addTask("Dump Update", $entry->toArray(), [
                'object' => QuickBookTask::DUMP_QBO_CUSTOMER,
                'object_id' => $entry->object_id,
                'origin' => QuickBookTask::ORIGIN_QB,
                'action' => $operation,
                'status' => QuickBookTask::STATUS_PENDING,
				'created_source' => QuickBookTask::WEBHOOK_EVENT,
				'quickbook_webhook_entry_id' => $entry->id,
            ]);
        }
	}
	
	public function directTempTriggers($entry){

		//Bill
		if($entry->object_type == 'Bill' and ($entry->operation == 'Create' or $entry->operation == 'Update')){

			$billEntity = App::make(Bill::class);
			$bill = $billEntity->get($entry->object_id);
			$billEntity->actionImport($bill);
		}

	}

	private function checkCompanySubscription()
	{
		$companySubscription = Subscription::where('company_id', getScopeId())
			->where('status', Subscription::ACTIVE)
			->first();

		if(!$companySubscription){
			return false;
		}
		return true;

	}
}