<?php
namespace App\Services\QuickBooks\QueueHandler\QB;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Sync\Customer as SyncCustomerService;
use App\Models\QuickbookSyncBatch;
use App\Models\QuickBookTask;
use Carbon\Carbon;

class SyncRequestHandler
{
	use CompanyScopeTrait;

	public function Analyzing($queueJob, $data)
	{
		try {
			$queueStartedAt = Carbon::now()->toDateTimeString();
			$userId = $data['user_id'];
			$this->setCompanyScope($userId);
			$payload = $data['payload'];
			$taskId = $payload['id'];
			$task = QBOQueue::get($taskId);
			$batch = QuickbookSyncBatch::find($data['payload']['object_id']);
			QBOQueue::markInProgress($taskId);
			$syncCustomerService = app()->make(SyncCustomerService::class);
			$syncCustomerService->mappingCustomers($batch);

			$batch->status = QuickbookSyncBatch::STATUS_AWAITING;
			$batch->save();

			QBOQueue::markSuccess($taskId);

			$task->queue_attempts = $queueJob->attempts();
			if($queueJob->attempts() == 1) {
				$task->queue_started_at = $queueStartedAt;
			}

			$task->queue_completed_at = Carbon::now()->toDateTimeString();
			$task->save();
			Log::info('Sync Request Success', [$taskId]);
			return $queueJob->delete();
		} catch (Exception $e) {
			Log::info('Sync Request Handler Exception', [$taskId]);

			$task = QBOQueue::markFailed($taskId, (string) $e);
			$task->queue_attempts = $queueJob->attempts();

			if($queueJob->attempts() == 1) {
				$task->queue_started_at = $queueStartedAt;
			}

			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {

				if($task) {
					$task->queue_completed_at = Carbon::now()->toDateTimeString();
					$task->save();
				}
				return $queueJob->delete();
			}
			$task->save();
			throw $e;

		}
	}
}