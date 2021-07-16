<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\QuickbookSyncCustomer;
use Queue;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb\CustomerAccountHandler;
use Log;

class SyncNowHandler
{
	/**
	 * Handle customer account from sync request
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function handle($job, $jobData)
	{
		if(!ine($jobData, 'batch_id')
			|| !ine($jobData, 'company_id')
			|| !ine($jobData, 'auth_user_id')
			|| !ine($jobData, 'customer_ids')
		){
			return $job->delete();
		}


		setAuthAndScope($jobData['auth_user_id']);

		try {

			$objects = QuickbookSyncCustomer::where('company_id', $jobData['company_id'])
				->where('batch_id', $jobData['batch_id'])
				->whereIn('customer_id', (array)$jobData['customer_ids'])
				->where('origin', QuickbookSyncCustomer::ORIGIN_JP)
				->where('ignored', false)
				->whereNull('qb_id')
				->get();

			// $objectIds = $jobData['customer_ids'];
			foreach ($objects as $object) {
				$groupId = generateUniqueToken();
				$object->sync_status = QuickbookSyncCustomer::SYNCHING;
				$object->group_id = $groupId;
				$object->save();

				$jobData['customer_id'] = $object->customer_id;
				$jobData['group_id'] = $groupId;
				$jobData['origin'] = QuickBookDesktopTask::ORIGIN_JP;
				$jobData['created_source'] = QuickBookDesktopTask::SYNC_MANAGER;
				Queue::connection('qbo')->push(CustomerAccountHandler::class, $jobData);
			}

		} catch (Exception $e) {
			Log::info('Jp to QBD Sync Now Handler Exceptions.');
			Log::info($e);
		}

		$job->delete();

	}
}
