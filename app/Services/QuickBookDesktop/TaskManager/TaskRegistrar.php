<?php
namespace App\Services\QuickBookDesktop\TaskManager;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;
use App\Models\QuickBookDesktopTask;
use App\Models\QuickbookUnlinkCustomer;
use App\Models\QuickBookStagedEntry;
use App\Models\Customer;

class TaskRegistrar
{
	function __construct(
		Settings $settings,
		QBDCustomer $qbdCustomer,
		TaskScheduler $taskScheduler
	) {
		$this->settings = $settings;
		$this->qbdCustomer = $qbdCustomer;
		$this->taskScheduler = $taskScheduler;
	}


	public function addTask($action, $user, $taskMeta)
	{
		try {

			$this->registerTask($action, $user, $taskMeta);

		} catch (Exception $e) {
			Log::error("QuickBook Task Enqueue: Error", [(string) $e]);
		}
	}

	private function registerTask($action, $user, $taskMeta)
	{
		try {

			if(!$this->settings->setCompanyScope($user)) {
				Log::info('QuickBook AddTask: unable to set company scope.', [$action, $user]);
				return false;
            }

			// $this->registerQBDDumpTask($action, $user, $taskMeta);

			$settings = $this->settings->getSettings(getScopeId());

			if($this->isTwoWayDisabled($settings) && !in_array($taskMeta['action'], [QuickBookDesktopTask::DUMP, QuickBookDesktopTask::IMPORT, QuickBookDesktopTask::SYNC_ALL])) {
				Log::warning('Two way sync disabled.', [$user]);
				return false;
			}

			$errors = $this->validateTask($taskMeta);

			if(!empty($errors)) {
				Log::warning('validate entry failed.', [$user, $errors]);
				return false;
			}

			if($this->isAlreadyRegistered($action, $user, $taskMeta)) {
				Log::warning('Task already exists');
				return false;
			}

			// Prevent loop
			if($this->isTaksOriginatedFromJP($action, $user, $taskMeta)){
				Log::warning('This entity in jp was created from JP originally');
				return false;
			}

			$taskMeta['origin'] = QuickBookDesktopTask::ORIGIN_QBD;
			$taskMeta['created_source'] = QuickBookDesktopTask::POLL_EVENT;

			return $this->taskScheduler->addTask($action, $user, $taskMeta);

		} catch(Exception $e) {
			Log::info('QuickBook AddTask: Error', [(string) $e]);
		}
    }

	private function isAlreadyRegistered($action, $user, $taskMeta)
	{
		if (
			$taskMeta['action'] == QuickBookDesktopTask::CREATE ||
			$taskMeta['action'] == QuickBookDesktopTask::DELETE
		) {

			$task = QuickBookDesktopTask::where([
				'qb_username' => $user,
				'qb_action' => $action,
				'object' => $taskMeta['object'],
				'status' => [QuickBookDesktopTask::STATUS_QUEUED, QuickBookDesktopTask::STATUS_INPROGRESS],
				'object_id' => $taskMeta['object_id'],
				'action' => $taskMeta['action'],
				'origin' => QuickBookDesktopTask::ORIGIN_QBD
			])->first();

			if ($task) {
				return true;
			}

			return false;
		}
	}


	private function isTaksOriginatedFromJP($action, $user, $taskMeta)
	{
		if (
			$taskMeta['action'] == QuickBookDesktopTask::CREATE ||
			$taskMeta['action'] == QuickBookDesktopTask::DELETE
		) {

			$objects = [$taskMeta['object']];

			if ($taskMeta['object'] == QuickBookDesktopTask::CUSTOMER) {
				$objects = [$taskMeta['object'], QuickBookDesktopTask::JOB];
			}

			$task = QuickBookDesktopTask::where([
				'qb_username' => $user,
				'action' => $taskMeta['action'],
				'qb_object_id' => $taskMeta['object_id'],
				'origin' => QuickBookDesktopTask::ORIGIN_JP
			])->whereIn('object', $objects)->first();

			if ($task) {
				return true;
			}

			return false;
		}
	}

	private function isTwoWayDisabled($settings){

		if(ine($settings, 'sync_type') && ($settings['sync_type'] != 'two_way')) {
			return true;
		}
		return false;
	}

	private function validateTask($taskMeta)
	{
		$customerId = null;

		$isCustomer = false;

		$errors = [];
		if(ine($taskMeta, 'action')
			&& in_array($taskMeta['action'], [QuickBookDesktopTask::DUMP, QuickBookDesktopTask::IMPORT, QuickBookDesktopTask::SYNC_ALL])
		){
			return $errors;
		}

		if (in_array($taskMeta['object'], [
				QuickBookDesktopTask::ACCOUNT,
				QuickBookDesktopTask::VENDOR,
				QuickBookDesktopTask::ITEM,
				QuickBookDesktopTask::UNIT_OF_MEASUREMENT,
				QuickBookDesktopTask::SALES_TAX_CODE,
				QuickBookDesktopTask::ITEM_SALES_TAX,
				QuickBookDesktopTask::ITEM_SALES_TAX_GROUP,
				QuickBookDesktopTask::PAYMENT_METHOD,
			])) {
			return $errors;
		}

		if(!$isCustomer && ($taskMeta['object'] == QuickBookDesktopTask::CUSTOMER)) {

			$customerId = $this->qbdCustomer->getParentCustomer($taskMeta['object_id']);

			$isCustomer = true;

		} elseif($taskMeta['object'] == QuickBookDesktopTask::JOB) {

			$customerId = $this->qbdCustomer->getParentCustomer($taskMeta['parent_ref']);

		} elseif($taskMeta['object'] == QuickBookDesktopTask::BILL) {

			$customerId = null;

		} elseif(ine($taskMeta, 'entity_ref') && ine($taskMeta['entity_ref'], 'qb_desktop_id')) {

			$customerId = $this->qbdCustomer->getParentCustomer($taskMeta['entity_ref']['qb_desktop_id']);
		}

		if($this->settings->isControlledSyncEnabled()) {

			if(!$customerId) {
				$errors[] = 'Controlled Sync Setting Enabled.';
				return $errors;
			}

			$customer = Customer::where('qb_desktop_id', $customerId)
				->where('company_id', getScopeId())->first();

			if(!$customer) {
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

		if (!$isCustomer && $customerId) {

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

	public function registerQBDDumpTask($action, $user, $taskMeta)
	{
		$metaData = [];
		$priority = 0;
		try{
			//To do need to remove it after creating dump delete handler.
			if(ine($taskMeta, 'action')
				&& in_array($taskMeta['action'], [
					QuickBookDesktopTask::DELETE,  QuickBookDesktopTask::UPDATE
				])
			){
				return true;
			}

	        if(ine($taskMeta, 'object') && ine($taskMeta, 'object_id')){

				if (in_array($taskMeta['object'], [
						QuickBookDesktopTask::CUSTOMER,
						QuickBookDesktopTask::JOB
					])) {

					if(in_array($taskMeta['object'], [QuickBookDesktopTask::CUSTOMER, QuickBookDesktopTask::JOB])){
						$priority = QuickBookDesktopTask::PRIORITY_CUSTOMER_DUMP_UPDATE;
					}else{
						$priority = QuickBookDesktopTask::PRIORITY_ENTITY_DUMP_UPDATE;
					}

					$metaData =  [
		        		'action' => QuickBookDesktopTask::DUMP_UPDATE,
		        		'object' => $taskMeta['object'],
		        		'object_id' => $taskMeta['object_id'],
		        		'priority' => $priority,
		        		'origin' => QuickBookDesktopTask::ORIGIN_QBD,
		    		];

		    		$this->taskScheduler->addTask($action, $user, $metaData);
		        }
	        }
		} catch(Exception $e){
			Log::info($e);
		}
	}

	public function addJpTask($action, $user, $taskMeta)
	{
		try {
			$this->registerJpTask($action, $user, $taskMeta);
		} catch (Exception $e) {
			Log::error("JobProgress Add Task Error", [(string) $e]);
		}
	}

	private function registerJpTask($action, $user, $taskMeta)
	{
		try {

			if (!$this->settings->setCompanyScope($user)) {
				Log::info('JobProgress AddTask: unable to set company scope.', [$action, $user]);
				return false;
			}

			$settings = $this->settings->getSettings(getScopeId());

			$errors = $this->validateJpTask($taskMeta);

			if (!empty($errors)) {
				Log::warning('validate entry failed.', [$user, $errors]);
				return false;
			}

			$taskMeta['origin'] = QuickBookDesktopTask::ORIGIN_JP;

			if(!ine($taskMeta, 'created_source')) {
				$taskMeta['created_source'] = QuickBookDesktopTask::SYSTEM_EVENT;
			}

			return $this->taskScheduler->addTask($action, $user, $taskMeta);

		} catch (Exception $e) {
			Log::info('JobProgress AddTask: Error', [(string) $e]);
		}
	}

	private function validateJpTask($taskMeta)
	{
		$errors = [];

		return $errors;
	}
}