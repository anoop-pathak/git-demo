<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\QbToJp;

use Exception;
use App\Models\QuickbookSyncCustomer;
use Illuminate\Support\Facades\Queue;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\QbToJp\CustomerAccountHandler;

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
				->whereIn('qb_id', (array)$jobData['customer_ids'])
				->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
				->where('ignored', false)
				->whereNull('customer_id')
				->get();

			foreach ($objects as $object) {
				$groupId = generateUniqueToken();
				$object->sync_status = QuickbookSyncCustomer::SYNCHING;
				$object->group_id = $groupId;
				$object->save();
				$jobData['customer_id'] = $object->qb_id;
				$jobData['group_id'] = $groupId;
				$jobData['origin'] = QuickBookTask::ORIGIN_QB;
				$jobData['created_source'] = QuickBookTask::SYNC_MANAGER;

				Queue::connection('qbo')->push(CustomerAccountHandler::class, $jobData);
			}

		} catch (Exception $e) {
			Log::info('QBD to JP Sync Now Handler Exceptions.');
			Log::info($e);
		}

		$job->delete();

	}
}
