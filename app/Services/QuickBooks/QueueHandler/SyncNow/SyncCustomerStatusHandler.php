<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\Log;

class SyncCustomerStatusHandler
{
	/**
	 * Handle request to add Customer Task
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function fire($queueJob, $meta)
	{
		if(!ine($meta, 'company_id')
			|| !ine($meta, 'group_id')
			|| !ine($meta, 'auth_user_id')
		){
			return $queueJob->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$companyId = $meta['company_id'];
		$groupId = $meta['group_id'];

		try {
			$syncCustomer = QuickbookSyncCustomer::where('company_id', $companyId)
				->where('group_id', $groupId)
				->first();
			Log::info('Sync Customer Status Handler');
			Log::info($syncCustomer);

			if($syncCustomer && ($syncCustomer->sync_status == QuickbookSyncCustomer::SYNCHING)){
				Log::info('Update Sync Customer Status.');
				QBOQueue::updateCustomerAccountSyncStatus($groupId, $companyId);
			}


		} catch (Exception $e) {
			Log::info('Sync Customer Status Handler Exception.');
			Log::info($e);
		}

		$queueJob->delete();
	}
}