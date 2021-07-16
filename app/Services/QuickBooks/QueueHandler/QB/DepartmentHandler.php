<?php
namespace App\Services\QuickBooks\QueueHandler\QB;

use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Department;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Services\QuickBooks\Facades\QBOQueue;
use Carbon\Carbon;
use Exception;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\DB;

class DepartmentHandler
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

			$entry = $task['payload'];

			$objectId = $task->object_id;

			$task = QBOQueue::checkParentTaskStatus($task);
			if ($task->status != QuickBookTask::STATUS_INPROGRESS) {
				DB::commit();
				return $queueJob->delete();
			}

			Department::create($objectId);

			QBOQueue::markSuccess($taskId);

			Log::info('Department:Create', $entry);

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

			Department::update($objectId);

			QBOQueue::markSuccess($taskId);

			Log::info('Department:Update', $entry);

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

	public function delete($queueJob, $data)
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

			Department::delete($objectId);

			QBOQueue::markSuccess($taskId);

			Log::info('Department:Delete', $entry);

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