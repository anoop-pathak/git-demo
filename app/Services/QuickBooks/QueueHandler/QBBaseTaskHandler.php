<?php
namespace App\Services\QuickBooks\QueueHandler;

use Exception;
use PDOException;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Models\QuickBookTask;
use App\Models\QuickbooksActivity;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use Illuminate\Support\Facades\Log;

abstract class QBBaseTaskHandler
{
	use CompanyScopeTrait;
	use CustomerAccountHandlerTrait;

	protected $task = null;
	protected $qbEntity = null;
	protected $entity = null;
	protected $queueJob = null;
	protected $resubmittedFromSynch = false;
	protected $failSilently = false;

	public function fire($queueJob, $payload)
	{
		$this->queueJob = $queueJob;

		try {
			$this->setCompanyScope($payload['user_id']);

            $task = $payload['payload'];

            //refetch to get the latest state
            $this->task = $task = QuickBookTask::find($task['id']);

			$task->markStarted($queueJob->attempts());

			if($task->isParentTaskFailed()){
				if($queueJob->attempts() < QuickBookTask::QUEUE_ATTEMPTS){
					// throw new Exception("Parent Task failed exception.");
					$task->markFailed("it's parent task failed so due to this task is also mark as failed.", $queueJob->attempts());
					return $queueJob->release(5);
				}
				$task->markFailed("it's parent task failed so due to this task is also mark as failed.", $queueJob->attempts());
				return $queueJob->delete();
			}

			if(!$task->isParentTaskComplete()){
				$task->reSubmit();
				return $queueJob->delete();
			}

			$this->qbEntity = $this->getQboEntity($task->object_id);

            if(!ine($this->qbEntity, 'entity')){
				// for some reason Entity we are trying to create in JP doesn't exists in QB.. close this tasks
				$task->markFailed("Object not found on QB. how is it possible!!! No idea");
				return $queueJob->delete();
			}

			if(!$this->checkPreConditions($this->qbEntity)){
				return $queueJob->delete();
			}

			// synch the qbo entity to jp and returns the resulting jp entity
			$this->entity = $this->synch($task, $this->qbEntity);

			if($this->resubmittedFromSynch || $this->failSilently){
				return  $queueJob->delete();
			}

			$task->markSuccess($this->entity, $queueJob->attempts());
			$this->recordLog();
			return $queueJob->delete();

		} catch(PDOException $e) {
			if((int)$e->getCode() == 40001){
				$task->reSubmit();
				//This is temporary code need to remove after fixing the issue.
				$task->msg = 'This task is Resubmit due to deadlock exception'.$task->msg;
				$task->save();
				return $queueJob->delete();
			}
			$task->markFailed((string) $e, $queueJob->attempts());
			$this->taskFailed($e);
		} catch (Exception $e) {

			$task->markFailed((string) $e, $queueJob->attempts());
			$this->taskFailed($e);

			Log::error($e);
		}
    }

	/**
	 * allows child handlers to check for additional preconditions
	 * which should delete the queue if failed.
	 *
	 * @param SynchEntityInterface $entity
	 * @return Boolean
	 */
	protected function checkPreConditions($entity){
		return true;
	}


	/**
	 * Mark the task as failed silently. This task wouldn't be retried and there wouldn't be any log for it.
	 *
	 * @param [type] $msg
	 * @return void
	 */
	protected function failSilently($msg){
		$this->task->markFailed($msg, $this->queueJob->attempts());
		$this->failSilently = true;
	}
	/**
	 * Log message will based based on the task status (Error, Success)
	 */
	function recordLog(){

		if($this->task->status == QuickBookTask::STATUS_SUCCESS){
			$msg = $this->getSuccessLogMessage();
			$type = QuickbooksActivity::SUCCESS;
			$customer_id = $this->entity ? $this->entity->getCustomerId() : null;
		}

		if($this->task->status == QuickBookTask::STATUS_ERROR){
			$msg = $this->getErrorLogMessage();
			$type = QuickbooksActivity::ERROR;
			$customer_id = null;
		}

		/**
		 * @TODO save the message in db
		 */

		$activityLog = new QuickbooksActivity;
		$activityLog->company_id = $this->task->company_id;
		$activityLog->customer_id = $customer_id;
		$activityLog->task_id = $this->task->id;
		$activityLog->activity_type = $type;
		$activityLog->msg = $msg;
		$activityLog->save();
	}

	protected function getSuccessLogMessage(){
		$format = "%s %s has been successfully %sd in JP";
		$displayName = $this->entity ? $this->entity->getLogDisplayName() : '';
		$action = strtolower($this->task->action);
		$message = sprintf($format, $this->task->object, $displayName, $action);
		Log::info($message);
		return $message;
	}

	/**
	 * Return a string message to log in case we failed to perform
	 * this task
	 *
	 * @return string
	 */
	abstract function getErrorLogMessage();

	/**
	 * Provide a base implementation for failure case.
	 * For most cases this should be sufficient but this provide
	 * a place for child classes to extend the behaviour of failed cases
	 */
	protected function taskFailed($e){
		if($this->queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
			$this->recordLog();
			return $this->queueJob->delete();
		}else{
			return $this->queueJob->release(5);
		}
	}

	protected function reSubmit(){
		$this->resubmittedFromSynch = true;
		$this->task->reSubmit();
	}

	/** return the corresponding QBo entity
	 * that we are trying to import to JP
	 */
	abstract function getQboEntity($entity_id);

	/**
	 * Main function that is responsible for doing the actual work of synching the entity to JP
	 * Should throw an exception if it fails to perform the task and intent to mark the task
	 * as failure
	 * @return SynchEntityInterface
	 */
	abstract function synch($task, $entity);
}