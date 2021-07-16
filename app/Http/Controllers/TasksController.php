<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\Task;
use App\Repositories\TasksRepository;
use App\Transformers\TasksTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\UsersRequiredException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\PreviousStageNotAllowedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Repositories\JobRepository;
use App\Exceptions\InvalideAttachment;
use App\Models\WorkflowStage;

class TasksController extends ApiController
{

    protected $repo;
    protected $response;

    public function __construct(TasksRepository $repo, Larasponse $response, JobRepository $jobRepo)
    {
        $this->repo = $repo;
        $this->response = $response;
        $this->jobRepo = $jobRepo;
        parent::__construct();
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function index()
    {
        $input = Request::all();
        $userId = Auth::id();
        $tasks = $this->repo->getFilteredTasks($userId, $input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        if (!$limit) {
            return ApiResponse::success($this->response->collection($tasks->get(), new TasksTransformer));
        }
        $tasks = $tasks->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($tasks, new TasksTransformer));
    }

    public function store()
    {
        $input = Request::onlyLegacy('users', 'title', 'notes', 'due_date', 'job_id', 'stage_code', 'wf_task_id', 'notify_users', 'email_notification', 'message_notification', 'notify_user_setting', 'assign_to_setting', 'reminder_type', 'reminder_frequency', 'is_due_date_reminder', 'locked', 'is_high_priority_task', 'task_template_id', 'attachments');
        $validator = Validator::make($input, Task::getCreateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if(ine($input, 'is_due_date_reminder') && $input['reminder_type'] == 'hour') {

			return ApiResponse::errorGeneral('Selected duration is not valid for this type of reminder.');
		}

		DB::beginTransaction();
        try {
            $input = $this->getJobAdditionalData($input);
            $createdBy = Auth::id();
			$input['fire_new_task_event'] = true;
            $task = $this->repo->createTask($createdBy, $input['users'], $input['title'], $input);
            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.task_created'),
                'data' => $this->response->item($task, new TasksTransformer)
            ]);
        } catch(UsersRequiredException $e){
            DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalideAttachment $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PreviousStageNotAllowedException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {
			DB::rollback();

			return ApiResponse::errorNotFound($e->getMessage());
        } catch (Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function update($taskId)
    {
        $task = $this->repo->getById($taskId);

		$input = Request::onlyLegacy('title', 'notes', 'due_date', 'reminder_type', 'reminder_frequency', 'is_due_date_reminder', 'locked', 'stage_code', 'notify_users', 'notify_user_setting' , 'is_high_priority_task', 'attachments', 'delete_attachments');

		$createdBy = Auth::id();
		$rules = Task::getUpdateRules();

		$appVersion = Request::header('app-version');
		if(config('is_mobile') && version_compare($appVersion, '2.6.11', '<')){
			unset($input['is_high_priority_task']);
		}

		$validator = Validator::make($input, $rules);
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		if(ine($input, 'is_due_date_reminder') && $input['reminder_type'] == 'hour') {

			return ApiResponse::errorGeneral('Selected duration is not valid for this type of reminder.');
		}

		if (($task->locked) && ($task->wf_task_id) && ($task->completed)) {

			return ApiResponse::errorGeneral('The task cannot be updated as it is bound with Sales automation and it is completed.');
		}

		DB::beginTransaction();
		try {
			if(ine($input, 'locked')) {
				$this->validateLockedStage($task->job, $input['stage_code']);
			}
			$task = $this->repo->updateTask($task, $input);

			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Task']),
				'data' => $this->response->item($task, new TasksTransformer)
			]);
		} catch(UnauthorizedException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalideAttachment $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PreviousStageNotAllowedException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {
			DB::rollback();

			return ApiResponse::errorNotFound($e->getMessage());
		} catch(Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    public function change_due_date()
    {
        $input = Request::onlyLegacy('task_id', 'due_date');
        $validator = Validator::make($input, Task::getChangeDueDateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $task = $this->repo->getById($input['task_id']);

        try {
            $task = $this->repo->changeDueDate($task, $input['due_date']);
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Task']),
                'data' => $this->response->item($task, new TasksTransformer)
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function show($id)
    {
        $task = $this->repo->getById($id);
        return ApiResponse::success(['data' => $this->response->item($task, new TasksTransformer)]);
    }

    public function destroy($taskId)
    {
        $task = $this->repo->getById($taskId);
        try {
            $this->repo->deleteTask($task);
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Task'])
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function delete_all()
    {
        $input = Request::onlyLegacy('status');
        $validator = Validator::make($input, ['status' => 'required|in:completed,pending,all']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $this->repo->deleteAllTasks(Auth::id(), $input['status']);
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Task'])
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function mark_as_completed($taskId)
    {
        $task = $this->repo->getById($taskId);
        try {
            $userId = Auth::id();
            $completed = Carbon::now()->toDateTimeString();
            $task = $this->repo->markAsCompletedOrPending($task, $userId, $completed);
            return ApiResponse::success([
                'message' => trans('response.success.task_completed'),
                'data' => $this->response->item($task, new TasksTransformer)
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function mark_as_pending($taskId)
    {
        $task = $this->repo->getById($taskId);
		$validateStage = $this->repo->validateStages($task);

		if (($task->locked) && ($task->completed) && ($validateStage)) {

			return ApiResponse::errorGeneral('This task cannot be marked as pending since the linked workflow stage is already complete.');
		}
		try {
			$userId = Auth::id();
			$task = $this->repo->markAsCompletedOrPending($task, $userId, null);

			return ApiResponse::success([
				'message' => trans('response.success.task_pending'),
				'data' => $this->response->item($task, new TasksTransformer)
			]);
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    public function pending_tasks_count()
    {
        $input = Request::all();
        $userId = Auth::id();

        if (!ine($input, 'type')) {
            $input['type'] = 'assigned'; // by default users's assigned task.
        }
        if (!ine($input, 'status')) {
            $input['status'] = 'pending'; // by default pending tasks.
        }

        $tasks = $this->repo->getFilteredTasks($userId, $input, false);
        return ApiResponse::success([
            'count' => $tasks->count()
        ]);
    }

    public function link_to_job()
    {
        $input = Request::onlyLegacy('task_id', 'job_id');
        $validator = Validator::make($input, ['task_id' => 'required', 'job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $task = $this->repo->getById($input['task_id']);
        $input = $this->getJobAdditionalData($input);
        if ($task->update($input)) {
            return ApiResponse::success(['message' => trans('response.success.task_linked_to_job')]);
        }
        return ApiResponse::errorInternal();
    }

    /**
     * Create multiple task for worklfow
     * @return [type] [description]
     */
    public function createWorkflowtask()
    {
        $input = Request::onlyLegacy('job_id', 'tasks', 'notify_users');
		$validator = Validator::make($input, Task::getWorkflowTasksRules($input));

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}
		$job =  $this->jobRepo->getById($input['job_id']);
		$createdBy = Auth::id();
		DB::beginTransaction();
		try {
			foreach ($input['tasks'] as $key => $taskData) {
				if(ine($taskData, 'locked')) {
					$this->validateLockedStage($job, $taskData['stage_code']);
				}
				$input['tasks'][$key]['job_id'] = $job->id;
				$input['tasks'][$key]['customer_id'] = $job->customer_id;
				$input['tasks'][$key]['stage_code'] = $job->jobWorkflow->current_stage;
			}

			foreach ($input['tasks'] as $key => $taskData) {
				$messageNotification = ine($taskData, 'message_notification') ? $taskData['message_notification'] : null;
				$emailNotification = ine($taskData, 'email_notification') ? $taskData['email_notification'] : null;

				$task = $this->repo->createTask(
					$createdBy,
					$taskData['users'],
					$taskData['title'],
					$taskData
				);

				$queueData[] = [
					'task_id' => $task->id,
					'email_notification' => $emailNotification,
					'message_notification' => $messageNotification,
				];
			}
		} catch(UnauthorizedException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PreviousStageNotAllowedException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {
			DB::rollback();
			$message = class_basename($e->getModel()).' Not Found';

			return ApiResponse::errorNotFound($message);
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
		DB::commit();

		foreach ($queueData as $key => $queue) {
			\Queue::push('\App\Handlers\Events\NewTaskAssignedQueueHandler', $queue);
		}

		return ApiResponse::success([
			'message' => trans('response.success.saved', ['attribute' => 'Tasks'])
		]);
    }

    public function markAsUnlock($taskId)
	{
		$input = Request::onlyLegacy('locked');
		$validator = Validator::make($input, Task::getMarkAsUnlockRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		try {
			//check if user has permission to lock task
			$this->repo->markAsUnlock($input['locked']);

			$task = $this->repo->getById($taskId);
			$task->locked = $input['locked'];
			$task->save();

            $message = 'Task marked as unlocked';

			if($task->locked) {
				$message = 'Task marked as locked';
			}

            return ApiResponse::success([
				'message' => $message,
			]);
		} catch(UnauthorizedException $e){

			return ApiResponse::errorNotFound($e->getMessage());
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function lockTasksCount()
	{
		$input = Request::onlyLegacy('job_id', 'stage_code');
        $validator = Validator::make($input, Task::getTaskLockCountRules());

		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		$lockTasksCount = $this->repo->lockTasksCount($input['job_id'], $input['stage_code']);
		$lockCount['incomplete_task_lock_count'] = $lockTasksCount;

		return ApiResponse::success([
			'data' => $lockCount
		]);
	}

    /****************** Private function *********************/

    private function getJobAdditionalData($input)
    {
        if(empty($input['job_id'])) return $input;

		$job = Job::findOrFail($input['job_id']);
		$input['customer_id'] = $job->customer->id;
		if(ine($input, 'stage_code') && ine($input, 'locked')) {
			//check if stage code exist or not
			$workflowStages = WorkflowStage::where('workflow_id', $job->workflow_id)
				->where('code', $input['stage_code'])
				->first();

			if($workflowStages) {
				$previousStages = $job->jobWorkflowHistory->pluck('stage')->toArray();
				if(in_array($input['stage_code'], $previousStages)) {
					throw new PreviousStageNotAllowedException("You are not allowed to lock an already completed workflow stage with a task.");
				}
			} else {
				throw new ModelNotFoundException("Workflow stage not found.");
			}
		} else {
			$input['stage_code'] = $job->jobWorkflow->current_stage;
		}

		return $input;
    }

    private function validateLockedStage($job, $stageCode) {
		$workflowStages = WorkflowStage::where('workflow_id', $job->workflow_id)
			->where('code', $stageCode)
			->first();

		if($workflowStages) {
			$previousStages = $job->jobWorkflowHistory->pluck('stage')->toArray();
			if(in_array($stageCode, $previousStages)) {
				throw new PreviousStageNotAllowedException("You are not allowed to lock an already completed workflow stage with a task.");
			}
		} else {
			throw new ModelNotFoundException("Workflow stage not found.");
		}

		return true;
	}
}
