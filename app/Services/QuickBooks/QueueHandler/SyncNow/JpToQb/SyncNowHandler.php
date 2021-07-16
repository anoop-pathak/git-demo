<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\CustomerAccountHandler;

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
				$jobData['origin'] = QuickBookTask::ORIGIN_JP;
				$jobData['created_source'] = QuickBookTask::SYNC_MANAGER;

				Queue::connection('qbo')->push(CustomerAccountHandler::class, $jobData);
			}

		} catch (Exception $e) {
			Log::info('Jp to Qb Sync Now Handler Exceptions.');
			Log::info($e);
		}
		$job->delete();

	}
}
