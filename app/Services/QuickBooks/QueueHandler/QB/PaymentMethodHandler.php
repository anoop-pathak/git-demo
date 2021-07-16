<?php
namespace App\Services\QuickBooks\QueueHandler\QB;

use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Services\QuickBooks\Facades\QBOQueue;
use Carbon\Carbon;
use App\Services\QuickBooks\Exceptions\PaymentMethodNotSyncedException;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\PaymentMethod as QBPaymentMethod;

class PaymentMethodHandler
{
	use CompanyScopeTrait;

	public function create($queueJob, $data)
	{
		try {
			DB::beginTransaction();

			$userId = $data['user_id'];

			$this->setCompanyScope($userId);

			$payload = $data['payload'];

			$taskId = $payload['id'];

			$task = QBOQueue::get($taskId);

			if($queueJob->attempts() == 1) {
				$task->queue_started_at = Carbon::now()->toDateTimeString();
			}

			$objectId = $task->object_id;

			$task = QBOQueue::checkParentTaskStatus($task);

			if ($task->status != QuickBookTask::STATUS_INPROGRESS) {

				DB::commit();

				return $queueJob->delete();
			}

			$createdObject = null;

            $createdObject = QBPaymentMethod::create($objectId);

			if ($createdObject) {

				$task = QBOQueue::markSuccess($taskId);
				$task->jp_object_id = $createdObject->id;

				Log::info('Payment Method:Create - Success', [$task->id]);

			} else {

				Log::info('Payment Method:Create - Empty returned', [$task->id]);
			}

			$task->qb_object_id = $task->object_id;
			$task->queue_completed_at = Carbon::now()->toDateTimeString();

			$task->save();

			DB::commit();

			return $queueJob->delete();

		} catch (Exception $e) {

			DB::rollback();

			QBOQueue::markFailed($taskId, (string) $e);

			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {

				return $queueJob->delete();
			}
		}
	}

	public function update($queueJob, $data)
	{
		try {

			DB::beginTransaction();

			$userId = $data['user_id'];

			$this->setCompanyScope($userId);

			$payload = $data['payload'];

			$taskId = $payload['id'];

			$task = QBOQueue::get($taskId);

			if($queueJob->attempts() == 1) {
				$task->queue_started_at = Carbon::now()->toDateTimeString();
			}

			$entry = $task['payload'];

			$objectId = $task->object_id;

			$task = QBOQueue::checkParentTaskStatus($task);

			if ($task->status != QuickBookTask::STATUS_INPROGRESS) {

				DB::commit();

				return $queueJob->delete();
			}

			$returnedObject = null;

			try {

				$returnedObject = QBPaymentMethod::update($objectId);

			} catch (PaymentMethodNotSyncedException $e) {

				$meta = $e->getMeta();

				if (!ine($meta, 'id')) {

					throw $e;
				}

				$name = QBOQueue::getQuickBookTaskName([
					'object' => QuickBookTask::PAYMENT_METHOD,
					'operation' => QuickBookTask::CREATE
				]);

				$parentTask = QBOQueue::addTask($name, [
					'queued_by' => 'update payment method',
				], [
					'object_id' => $meta['id'],
					'object' => QuickBookTask::PAYMENT_METHOD,
					'action' => QuickBookTask::CREATE,
					'origin' => QuickBookTask::ORIGIN_QB,
					'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
				]);

				QBOQueue::markFailed($taskId, (string) $e);

				DB::commit();
				return $queueJob->delete();

			} catch (Exception $e) {

				throw $e;
			}

			if ($returnedObject) {

				$task = QBOQueue::markSuccess($taskId);
				$task->jp_object_id = $returnedObject->id;

				Log::info('Payment Method:Update - Success', [$task->id]);

			} else {

				Log::info('Payment Method:Update - Empty returned', [$task->id]);
			}

			$task->qb_object_id = $task->object_id;
			$task->queue_completed_at = Carbon::now()->toDateTimeString();

			$task->save();

			DB::commit();

			return $queueJob->delete();

		} catch (Exception $e) {

			DB::rollback();

			QBOQueue::markFailed($taskId, (string) $e);

			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {

				return $queueJob->delete();
			}
		}
	}
}