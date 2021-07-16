<?php
namespace App\Services\QuickBooks\QueueHandler;

use Exception;
use PDOException;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Models\QuickBookTask;
use App\Models\QuickbooksActivity;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBooks\QBEntityErrorTrait;

abstract class BaseTaskHandler
{
	use CompanyScopeTrait;
	use CustomerAccountHandlerTrait;
	use QBEntityErrorTrait;

	protected $task = null;
	protected $entity = null;
	protected $queueJob = null;
	protected $resubmittedFromSynch = false;
	protected $ensureCreationBeforeUpdate = true;

	public function fire($queueJob, $payload)
	{
		$this->queueJob = $queueJob;

		try {
			$this->setCompanyScope($payload['user_id']);

            $task = $payload['payload'];

            //refetch to get the latest state
            $this->task = $task = QuickBookTask::find($task['id']);

			$task->markStarted($queueJob->attempts());

			$this->entity = $this->getEntity($task->object_id);

            if(!$this->entity){
				// for some reason Entity we are trying to create in qb doesn't exists in JP.. close this tasks
				$task->markFailed("Object got deleted from JP somehow. how is it possible!!! No idea");
				return $queueJob->delete();
			}

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

			// if this task is to create new entity but we already have entity synched
			if(($this->task->action == QuickBookTask::CREATE || $this->task->action == QuickBookTask::MAP) &&  $this->entity->getQBOId()) {
				// Looks like entity has been already created on QBO somehow. So let's just change the status and quit
				$task->markSuccess($this->entity);
				Log::info($task->object.':Create', ['Entity was already synched .'.$this->entity->id]);
				return $queueJob->delete();

			}

			//task is for updating entity but entity was not synched
			if(($this->task->action == QuickBookTask::UPDATE || $this->task->action == QuickBookTask::APPLY)
				&&  !$this->entity->getQBOId()
				&& $this->ensureCreationBeforeUpdate) {

				$task->markFailed("this entity is not syched to QBO so need to synch it first.", $queueJob->attempts());
				$this->resynchCustomerAccount($this->entity->getCustomerId(), QuickBookTask::SYSTEM_EVENT);
				return $queueJob->delete();
			}

			//$task is for deletion
			if($this->task->action == QuickBookTask::DELETE && !$this->entity->getQBOId()) {
				$task->markFailed("this entity is not syched to QBO so nothing to delete.", $queueJob->attempts());
				return $queueJob->delete();

			}

			if(!$this->checkPreConditions($this->entity)){
				return $queueJob->delete();
			}

			$this->entity = $this->synch($task, $this->entity);

			if($this->resubmittedFromSynch){
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
			$this->taksFailed($e);
		} catch(QuickbookException $e) {

			$task->markFailed((string) $e, $queueJob->attempts());
			$this->taksFailed($e, $e->getFaultHandler());
			$e->context['task_id'] = $this->task->id;
			$e->context['task'] = $this->task->name;
			$e->context['object_id'] = $this->task->object_id;
			$e->context['queue_attempt'] = $queueJob->attempts();

			if(isset($this->entity)){
				$e->context['entity'] = $this->entity->id;
				$e->context['entity_type'] = get_class($this->entity);
			}

			Log::error($e);
		} catch (Exception $e) {
			$task->markFailed((string) $e, $queueJob->attempts());
			$this->taksFailed($e);

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
	 * Log message will based based on the task status (Error, Success)
	 */
	function recordLog(){
		if($this->task->status == QuickBookTask::STATUS_SUCCESS){
			$msg = $this->getSuccessLogMessage();
		}

		if($this->task->status == QuickBookTask::STATUS_ERROR){
			$msg = $this->getErrorLogMessage();
		}

		/**	save the message in db **/
		QuickbooksActivity::record($msg,  $this->entity->getCustomerId(), $this->task);
	}

	protected function getSuccessLogMessage(){
		$format = "%s %s has been successfully %sd in QBO";
		$action =strtolower($this->task->action);
		$message = sprintf($format, $this->task->object, $this->entity->getLogDisplayName(), $action);
		Log::info($message);
		return $message;
	}

	protected function getErrorLogMessage(){
		$format = "%s %s failed to be %sd in QBO";
		$action = strtolower($this->task->action);
		$message = sprintf($format, $this->task->object, $this->entity->getLogDisplayName(), $action);
		Log::info($message);
		return $message;
	}

	/**
	 * Provide a base implementation for failure case.
	 * For most cases this should be sufficient but this provide
	 * a place for child classes to extend the behaviour of failed cases
	 */
	protected function taksFailed($e, $faultHandler = null){
		if($this->queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
			$this->recordLog();
			if($faultHandler){
				$errorType = $faultHandler->getIntuitErrorType();
				$errorCode = $faultHandler->getIntuitErrorCode();
				$message = $faultHandler->getIntuitErrorMessage();
				$details = $faultHandler->getIntuitErrorDetail();
				$meta = [
					'type' => $errorType,
					'code' => $errorCode,
					'message' => $message,
					'details' => $details,
					'element' => $faultHandler->getIntuitErrorElement(),
				];
				$this->SaveEntityErrorLog($this->task->object, $this->task->object_id, $errorCode, $message, $details, $errorType, $meta);
			}
			return $this->queueJob->delete();
		}else{
			return $this->queueJob->release(5);
		}
	}

	protected function reSubmit(){
		$this->resubmittedFromSynch = true;
		$this->task->reSubmit();
	}

	abstract function getEntity($entity_id);

	/**
	 * Main function that is responsible for doing the actual work of synching the entity
	 * Should throw an exception if it fails to perform the task
	 */
	abstract function synch($task, $entity);
}