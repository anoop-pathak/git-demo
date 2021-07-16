<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired\CustomerAccountHandler;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;

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
				$jobData['origin'] = QuickBookTask::ORIGIN_JP;
				$jobData['created_source'] = QuickBookTask::SYNC_MANAGER;

				Queue::connection('qbo')->push(CustomerAccountHandler::class, $jobData);
			}

		} catch (Exception $e) {
			Log::info('Action Required Sync Now Handler Exceptions.');
			Log::info($e);
		}

		$job->delete();

	}
}
