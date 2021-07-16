<?php
namespace App\Services\QuickBookDesktop\TaskManager;

use Exception;
use Carbon\Carbon;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Models\QuickBookDesktopTask;
use Illuminate\Support\Facades\Log;

class TaskManager
{
	use CompanyScopeTrait;

	protected $handler = null;
	protected $task = null;
	protected $entity = null;
	protected $qbdEntity = null;
	protected $queueJob = null;
	protected $resubmittedFromSynch = false;

	public function sync($task, $meta, $timeSettings)
	{
		try {

			$this->task = $task;

			if(!$this->validateSession()) {
				throw new Exception('Company Scope is not set.');
			}

			$this->handler = $this->resolveHandler($this->task);

			if (!$this->handler) {

				$task->markFailed("Task hander not found");
				return false;
			}

			$this->handler->task = $task;

			if(!in_array($this->task->action, [
				QuickBookDesktopTask::IMPORT,
				QuickBookDesktopTask::DUMP,
				QuickBookDesktopTask::DELETE,
				QuickBookDesktopTask::DELETE_FINANCIAL,
				QuickBookDesktopTask::DUMP_UPDATE,
				QuickBookDesktopTask::SYNC_ALL
			])) {

				$this->handler->setQBDEntity($meta['xml']);

				$this->qbdEntity = $this->handler->getQBDEntity();

				if (!$this->qbdEntity) {

					$task->markFailed("QBD Entity not found!");
					return false;
				}
			}

			if (!in_array($this->task->action, [
				QuickBookDesktopTask::IMPORT,
				QuickBookDesktopTask::DUMP,
				QuickBookDesktopTask::SYNC_ALL,
				QuickBookDesktopTask::DUMP_UPDATE])) {

				$this->entity = $this->handler->getEntity($task->object_id);
			}

			if(($this->task->action != QuickBookDesktopTask::CREATE
				&& $this->task->action != QuickBookDesktopTask::IMPORT
				&& $this->task->action != QuickBookDesktopTask::SYNC_ALL
				&& $this->task->action != QuickBookDesktopTask::DUMP
				&& $this->task->action != QuickBookDesktopTask::MAP
				&& $this->task->action != QuickBookDesktopTask::DELETE_FINANCIAL
				&& $this->task->action != QuickBookDesktopTask::DUMP_UPDATE) && !$this->entity) {
				// for some reason Entity we are trying to create in qb doesn't exists in JP.. close this tasks
				$task->markFailed("Object got deleted from JP somehow. how is it possible!!! No idea");
				return false;
			}

			if($this->task->action != QuickBookDesktopTask::IMPORT
				&& $this->task->action != QuickBookDesktopTask::SYNC_ALL
				&& $this->task->action != QuickBookDesktopTask::DUMP
				&& $task->isParentTaskFailed()) {
				$task->markFailed("it's parent task failed so due to this task is also mark as failed.");
				return false;
			}

			if(!$task->isParentTaskComplete()) {
				$task->reSubmit();
				return false;
			}

			// if this task is to create new entity but we already have entity synched
			if(($this->task->action == QuickBookDesktopTask::CREATE)
				&& $this->entity
				&& $this->entity->getQBDId()
			) {

				$task->markSuccess($this->entity);
				Log::info($task->object.':Create', ['Entity was already synched .' .
					$this->entity->id]);
				return false;
			}

			//task is for updating entity but entity was not synched
			if(($this->task->action == QuickBookDesktopTask::UPDATE)
				&& !$this->entity->getQBDId()) {

				$task->markFailed("this entity is not syched to JP so need to synch it first.");
				return false;
			}

			if(($this->task->action == QuickBookDesktopTask::UPDATE)
				&& $this->entity
				&& $this->entity->getQBDId()
				&& $this->qbdEntity
				&& ($this->entity->qb_desktop_sequence_number == $this->qbdEntity['EditSequence'])
			) {

				$task->markSuccess($this->entity);
				Log::info($task->object.':Update', ['Entity was already updated .' .$this->entity->id]);

				return false;
			}

			//$task is for deletion
			if($this->task->action == QuickBookDesktopTask::DELETE
				&& !$this->entity->getQBDId()) {

				$task->markFailed("this entity is not syched to QBO so nothing to delete.");
				return false;
			}

			if(!$this->handler->checkPreConditions()) {

				if($this->handler->isReSubmitted()) {
					$this->reSubmit();
					return false;
				}

				$task->markFailed("Preconditions failed.");

				return false;
			}

			$this->entity = $this->handler->synch($task, $meta);

			if ($this->task->action == QuickBookDesktopTask::IMPORT) {

				$idents = $meta['idents'];

				if(!ine($idents, 'iteratorRemainingCount')) {

					$date = Carbon::now();

					$timeSettings->setLastRun($meta['user'], $this->task->qb_action, $date->toRfc3339String());
				}
			}

			if($this->task->action != QuickBookDesktopTask::IMPORT
				&& $this->task->action != QuickBookDesktopTask::DELETE
				&& $this->task->action != QuickBookDesktopTask::DELETE_FINANCIAL
				&& $this->task->action != QuickBookDesktopTask::SYNC_ALL
				&& $this->task->action != QuickBookDesktopTask::DUMP
				&& $this->task->action != QuickBookDesktopTask::DUMP_UPDATE
				&& !$this->entity
			) {
				throw new Exception('Empty object returned from hander.');
			}

			$task->markSuccess($this->entity);

			return $this->entity;

		} catch(\PDOException $e) {

			if((int)$e->getCode() == 40001) {

				$this->task->reSubmit();
				//This is temporary code need to remove after fixing the issue.
				$this->task->message = 'This task is Resubmit due to deadlock exception' .$task->message;
				$this->task->save();
				return false;
			}

			Log::info($e);

			$this->task->markFailed((string) $e);

		} catch (Exception $e) {

			Log::info($e);

			$this->task->markFailed((string) $e);

			return false;
		}
    }

	protected function getSuccessLogMessage()
	{
		$logDisplayName = $this->task->action;

		if ($this->entity) {
			$logDisplayName = $this->entity->getLogDisplayName();
		}

		$format = "%s %s has been successfully %sd in JP";
		$action =strtolower($this->task->action);
		$message = sprintf($format, $this->task->object, $logDisplayName, $action);
		Log::info($message, []);
		return $message;
	}

	protected function getErrorLogMessage()
	{
		$logDisplayName = $this->task->action;

		if ($this->entity) {
			$logDisplayName = $this->entity->getLogDisplayName();
		}

		$format = "%s %s failed to be %sd in JP";
		$action = strtolower($this->task->action);
		$message = sprintf($format, $this->task->object, $logDisplayName, $action);
		Log::info($message, []);
		return $message;
	}

	protected function reSubmit()
	{
		$this->task->reSubmit();
	}

	public function resolveHandler($task)
	{
		$handler = '\App\Services\QuickBookDesktop\QueueHandler\QBD\\' .
			$task->object . '\\' . $task->action . 'Handler';

		if (class_exists($handler)) {
			// create handler instance
			return app()->make($handler);
		}

		Log::info('QueueHandler Not Found: ', [$handler]);

		return false;
	}

	private function validateSession()
	{
		if(!getScopeId()) {
			return false;
		}

		return true;
	}
}