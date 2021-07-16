<?php
namespace App\Services\Queue;

use App\Models\QueueStatus;
use Illuminate\Support\Facades\Queue;
use App\Exceptions\Queue\InvalidQueueActionException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class JobQueue
{
	//STATUS
	const STATUS_QUEUED = 'queued';
	const STATUS_IN_PROCESS = 'in_process';
	const STATUS_FAILED = 'failed';
	const STATUS_COMPLETED = 'completed';

	//SRS
	const SRS_SAVE_BRANCH_PRODUCT = 'srs_save_branch_product';
	const SRS_SYNC_DETAILS = 'srs_sync_details';
	const CONNECT_SRS = 'connect_srs';
	const PROPOSAL_DIGITAL_SIGN = 'proposal_digital_sign';

	/**
	 * add a job in queue
	 * @param  String 	| $action    | Action name of queue
	 * @param  Integer 	| $companyId | Company Id
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be added in queue
	 * @param  Array 	| $data      | Contains fields of queue_status table like(status, attempts, message)
	 * @return boolean
	 */
	public function enqueue($action, $companyId, $entityId, $data)
	{
		if(self::isExist($action, $entityId, $companyId)) return;

		$connection = config('queue.default');

		switch ($action) {
			case self::CONNECT_SRS:
				$queue = 'App\Services\SRS\SRSQueueHandler@saveSRSDetail';
				$connection = 'long_task';
				break;
			case self::SRS_SAVE_BRANCH_PRODUCT:
				$queue = 'App\Services\SRS\SRSQueueHandler@saveBranchProducts';
				$connection = 'long_task';
				break;
			case self::SRS_SYNC_DETAILS:
				$queue = 'App\Services\SRS\SRSQueueHandler@updateSRSProducts';
				$connection = 'long_task';
				break;
			case self::PROPOSAL_DIGITAL_SIGN:
				$queue = 'App\Services\Queue\ProposalDigitalAuthorizationHandler';
				break;
			default:
				throw new InvalidQueueActionException(trans('response.error.invalid_job_queue_action'));
				break;
		}

		$queueStatus = QueueStatus::create([
			'company_id'	=> $companyId,
			'action'		=> $action,
			'entity_id'		=> $entityId,
			'status'		=> self::STATUS_QUEUED,
			'job_queue'		=> $queue,
			'data'			=> $data,
			'parent_id'		=> issetRetrun($data, 'parent_queue_id') ?: null,
		]);

		// this id we will use for further updations like status of the queue
		$data['queue_status_id'] = $queueStatus->id;

		Queue::connection($connection)->push($queue, $data);

		return true;
	}

	/**
	 * add a job in queue according to the given date time
	 * @param  DateTime | $dateTime  | DateTime for adding a job in queue at particular time
	 * @param  String 	| $action    | Action name of queue
	 * @param  Integer 	| $companyId | Company Id
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be added in queue
	 * @param  Array 	| $data      | Contains fields of queue_status table like(status, attempts, message)
	 * @return boolean
	 */
	public function later($dateTime, $action, $companyId, $entityId, $data)
	{
		if(self::isExist($action, $entityId, $companyId)) return;

		$connection = config('queue.default');

		switch ($action) {
			case self::SRS_SYNC_DETAILS:
				$queue = 'App\Services\SRS\SRSQueueHandler@updateSRSProducts';
				$connection = 'long_task';
				break;
			default:
				throw new InvalidQueueActionException(trans('response.error.invalid_job_queue_action'));
				break;
		}

		QueueStatus::create([
			'company_id'	=> $companyId,
			'action'		=> $action,
			'entity_id'		=> $entityId,
			'status'		=> self::STATUS_QUEUED,
			'job_queue'		=> $queue,
			'data'			=> $data,
			'parent_id'		=> issetRetrun($data, 'parent_queue_id') ?: null,
		]);

		Queue::connection($connection)->later($dateTime, $queue, $data);

		return true;
	}

	/**
	 * update queue status table
	 * @param  String 	| $action    | Action name of queue
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be added in queue
	 * @param  Integer 	| $companyId | Company Id
	 * @param  Array 	| $data      | Contains fields of queue_status table like(status, attempts, message)
	 * @return boolean
	 */
	public function update($action, $entityId, $companyId, $data)
	{
		$queue = QueueStatus::where('entity_id', $entityId)
			->where('action', $action)
			->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED]);

		if($companyId) {
			$queue->where('company_id', $companyId);
		}
		$queue->update($data);

		return $queue->latest('id')->first();
	}

	/**
	 * check if a queue already in process
	 * @param  String  	| $action    | Action name of queue
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be update
	 * @param  Integer 	| $companyId | Company Id
	 * @return boolean
	 */
	public function isExist($action, $entityId, $companyId)
	{
		$yesterday = Carbon::yesterday()->toDateString();

		$queue = QueueStatus::where('entity_id', $entityId)
			->where('company_id', $companyId)
			->where('action', $action)
			->whereDate('created_at', '>', $yesterday)
			->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED])
			->exists();

		return $queue;
	}

	/**
	 * update queue status as in process
	 * @param  String  	| $action    | Action name of queue
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be update
	 * @param  Integer 	| $attempts	 | Total queue attempts
	 * @param  Integer 	| $companyId | Company Id
	 * @return boolean
	 */
	public function statusInProcess($action, $entityId, $attempts, $companyId = null)
	{
		$data = [
			'queue_started_at' => Carbon::now()->toDateTimeString(),
			'status' => self::STATUS_IN_PROCESS,
			'attempts' => $attempts
		];

		return self::update($action, $entityId, $companyId, $data);
	}

	/**
	 * update queue status as completed
	 * @param  String  	| $action    | Action name of queue
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be update
	 * @param  Integer 	| $companyId | Company Id
	 * @return boolean
	 */
	public function statusCompleted($action, $entityId, $companyId = null)
	{
		$data = [
			'queue_completed_at' => Carbon::now()->toDateTimeString(),
			'status' => self::STATUS_COMPLETED,
		];

		return self::update($action, $entityId, $companyId, $data);
	}

	/**
	 * update queue status as completed
	 * @param  String  	| $action    | Action name of queue
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be update
	 * @param  Integer 	| $companyId | Company Id
	 * @return boolean
	 */
	public function statusFailed($action, $entityId, $companyId = null)
	{
		$data = [
			'queue_completed_at' => Carbon::now()->toDateTimeString(),
			'status' => self::STATUS_FAILED,
		];

		return self::update($action, $entityId, $companyId, $data);
	}

	/**
	 * save error detail of a queue
	 * @param  String  	| $action    | Action name of queue
	 * @param  Integer 	| $entityId  | Id of an entity which needs to be update
	 * @param  String 	| $error     | Error message
	 * @param  Integer 	| $companyId | Company Id
	 * @return boolean
	 */
	public function saveErrorDetail($action, $entityId, $error, $companyId = null)
	{
		$queue = QueueStatus::where('entity_id', $entityId)
			->where('action', $action)
			->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED]);

		if($companyId) {
			$queue->where('company_id', $companyId);
		}

		if(!($queue = $queue->first())) return false;

		$now = Carbon::now();
		$data = [
			'queue_status_id'	=> $queue->id,
			'error_detail'		=> $error,
			'created_at'		=> $now,
			'updated_at'		=> $now,
		];

		DB::table('queue_status_meta')->insert($data);

		$queue->has_error = true;
		$queue->save();

		return $queue;
	}

	/**
	|
	| Newer functions. These functions will be used to update info by QueueStatus ID.
	| We will use these functions in our next features/modules
	| Above functions will be removed by checking the impacted areas
	|
	*/

	public function updateById($id, $data)
	{
		$queue = QueueStatus::find($id);

		if(!$queue) return;

		if($queue->queue_started_at) {
			unset($data['queue_started_at']);
		}
		if($queue->queue_completed_at) {
			unset($data['queue_completed_at']);
		}
		$queue->update($data);

		return $queue;
	}

	public function markInProcess($id, $attempts)
	{
		$data = [
			'queue_started_at' => Carbon::now()->toDateTimeString(),
			'status' => self::STATUS_IN_PROCESS,
			'attempts' => $attempts,
		];

		return self::updateById($id, $data);
	}

	public function markCompleted($id, $attempts)
	{
		$data = [
			'queue_completed_at' => Carbon::now()->toDateTimeString(),
			'status' => self::STATUS_COMPLETED,
			'attempts' => $attempts,
		];

		return self::updateById($id, $data);
	}

	public function markFailed($id, $attempts)
	{
		$data = [
			'queue_completed_at' => Carbon::now()->toDateTimeString(),
			'status' => self::STATUS_FAILED,
			'attempts' => $attempts,
		];

		return self::updateById($id, $data);
	}

	public function saveErrorMessage($id, $errorMsg)
	{
		$queue = QueueStatus::find($id);

		if($queue) {
			$now = Carbon::now();
			$data = [
				'queue_status_id'	=> $queue->id,
				'error_detail'		=> $errorMsg,
				'created_at'		=> $now,
				'updated_at'		=> $now,
			];

			DB::table('queue_status_meta')->insert($data);

			if($errorMsg instanceof Exception) {
				$errorMsg = $errorMsg->getMessage();
			}

			$queue->has_error = true;
			$queue->error_reason = $errorMsg;
			$queue->save();
		}

		return $queue;
	}

	public function defaultConnection($connection)
	{
		$this->connection = $connection;
	}
}