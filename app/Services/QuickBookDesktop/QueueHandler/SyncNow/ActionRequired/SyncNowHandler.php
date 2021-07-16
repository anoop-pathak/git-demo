<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use Queue;
use App\Models\QuickBookDesktopTask;
use Log;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired\CustomerAccountHandler;

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
			|| !ine($jobData, 'qb_customer_ids')
		){
			return $job->delete();
		}


		setAuthAndScope($jobData['auth_user_id']);

		try {

			$objects = QuickbookSyncCustomer::where('company_id', $jobData['company_id'])
				->where('batch_id', $jobData['batch_id'])
				->where('ignored', false)
				->where('mapped', true)
				->where('action_required', true)
				->whereIn('qb_id', $jobData['qb_customer_ids'])
				->whereIn('customer_id', $jobData['customer_ids'])
				->get();

			foreach ($objects as $object) {
				$groupId = generateUniqueToken();
				$object->group_id = $groupId;
				$object->save();

				$jobData['customer_id'] = $object->customer_id;
				$jobData['qb_customer_id'] = $object->qb_id;
				$jobData['group_id'] = $groupId;
				$jobData['origin'] = QuickBookDesktopTask::ORIGIN_JP;
				$jobData['created_source'] = QuickBookDesktopTask::SYNC_MANAGER;

				Queue::connection('qbo')->push(CustomerAccountHandler::class, $jobData);
			}

		} catch (Exception $e) {
			Log::info('QBD Action Required Sync Now Handler Exceptions.');
			Log::info($e);
		}

		$job->delete();

	}
}
