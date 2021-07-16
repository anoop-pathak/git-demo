<?php
namespace App\Repositories;

use App\Models\Task;
use App\Services\Contexts\Context;
use App\Services\Google\GoogleTasksService;
use App\Events\NewTaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskUpdated;
use App\Events\TaskDeleted;
use Event;
use Auth;
use Carbon\Carbon;
use Settings;
use App\Exceptions\UsersRequiredException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\SecurityCheck;
use App\Repositories\JobRepository;
use Request;
use App\Models\WorkflowStage;
use App\Models\ActivityLog;
use ActivityLogs;

class TasksRepository extends ScopedRepository
{
	protected $model;
	protected $googleTaskService;
	protected $scope;
	protected $notificationRepo;

	public function __construct(Task $model, GoogleTasksService $googleTaskService, Context $scope, NotificationsRepository $notificationRepo, JobRepository $jobRepo) {
		$this->model = $model;
		$this->googleTaskService = $googleTaskService;
		$this->scope = $scope;
		$this->notificationRepo = $notificationRepo;
		$this->jobRepo = $jobRepo;
	}

	public function createTask($createdBy, $users, $title, $meta = array())
	{
		if(isset($meta['locked'])) {
			$this->markAsUnlock($meta['locked']);
		}

		$taskData['title'] 		= $title;
		$taskData['notes']		= isset($meta['notes']) ? $meta['notes'] : null;
		$taskData['due_date'] 	= isset($meta['due_date']) ? $meta['due_date'] : null;
		$taskData['job_id'] 	= isset($meta['job_id']) ? $meta['job_id'] : null;
		$taskData['customer_id'] = isset($meta['customer_id']) ? $meta['customer_id'] : null;
		$taskData['stage_code'] = isset($meta['stage_code']) ? $meta['stage_code'] : null;
		$taskData['wf_task_id'] = isset($meta['wf_task_id']) ? $meta['wf_task_id'] : null;
		$taskData['is_high_priority_task'] = ine($meta, 'is_high_priority_task');
		$taskData['task_template_id'] = ine($meta, 'task_template_id');

		$taskData['company_id'] = isset($meta['company_id']) ? $meta['company_id'] : $this->scope->id();
		$taskData['created_by'] = $createdBy;
		$taskData['reminder_type'] = isSetNotEmpty($meta, 'reminder_type') ?: null;
		$taskData['reminder_frequency'] = isSetNotEmpty($meta, 'reminder_frequency') ?: null;
		$taskData['is_due_date_reminder'] = ine($meta, 'is_due_date_reminder');
		$taskData['locked'] = ine($meta, 'locked');
		if(ine($meta, 'assign_to_setting') && ine($taskData, 'job_id')) {
			$taskData['assign_to_setting'] = $meta['assign_to_setting'];
		}
		if(ine($meta, 'notify_user_setting') && ine($taskData, 'job_id')) {
			$taskData['notify_user_setting'] = $meta['notify_user_setting'];
		}

		$task = Task::create($taskData);

		if(ine($meta, 'reminder_type')) {
			$task = $this->updateReminderDateTime(
				$task,
				Carbon::now(),
				$meta['reminder_type'],
				$meta['reminder_frequency']
			);
		}

		$assignToUsers = $this->getSettingUser($task->job, $task->assign_to_setting);
		$notifyUsers = $this->getSettingUser($task->job,  $task->notify_user_setting);

		if(!empty($assignToUsers)) {
			$users = arry_fu(array_merge($assignToUsers, (array)$users));
		}

		if(empty($users)) {

			throw new UsersRequiredException("Please assign user to Customer Rep / Estimator / Company Crew / Sub Contractor as chosen.");
		}

		$task->participants()->attach((array)$users);
		$nofifyUserInput = ine($meta, 'notify_users') ? $meta['notify_users'] : [];

		if (!empty($nofifyUserInput) || !empty($notifyUsers)) {
			$notifyUses = array_merge($nofifyUserInput, $notifyUsers);
			$task->notifyUsers()->attach((array)$notifyUses);
		}

		if(ine($meta, 'wf_task_id') && ine($meta, 'stage_code') && ine($meta, 'job_id')) {
			$this->maintainActivityLog($meta);
		}

		//event for new task..
		if(ine($meta, 'fire_new_task_event')) {
			Event::fire('JobProgress.Tasks.Events.NewTaskAssigned', new NewTaskAssigned($task, $meta));
		}

		$task = $this->getById($task->id);

		if (ine($meta,'attachments')) {
			$type = Task::TASK;
			$attachments = $task->moveAttachments($meta['attachments']);
			$task->saveAttachments($task, $type, $attachments);
		}

		return $task;
	}

	public function updateTask(Task $task, $data)
	{
		if(isset($data['locked'])) {
			$this->markAsUnlock($data['locked']);
		}
		$oldTodayTask = $task->isToday();
		$task->title = $data['title'];
		$task->notes = $data['notes'];
		$task->due_date = $data['due_date'];

		if(isset($data['locked'])) {
			$task->locked = ine($data, 'locked');
		}

		if(isset($data['is_due_date_reminder'])) {
			$task->is_due_date_reminder = $data['is_due_date_reminder'];
		}

		if(isset($data['is_high_priority_task'])) {
			$task->is_high_priority_task = (bool)$data['is_high_priority_task'];
		}

		if(ine($data, 'stage_code')) {
			$task->stage_code = $data['stage_code'];
		}

		if(isset($data['reminder_type'])) {
			$task = $this->updateReminderDateTime(
				$task,
				Carbon::now(),
				$data['reminder_type'],
				$data['reminder_frequency']
			);
			$task->reminder_type = $data['reminder_type'];
			$task->reminder_frequency = $data['reminder_frequency'];
		} else {
			$task->reminder_type = null;
			$task->reminder_frequency = null;
			$task->reminder_date_time = null;
		}

		if(isset($data['notify_user_setting'])) {
			$task->notify_user_setting = $data['notify_user_setting'];
			$notifyUsers = $this->getSettingUser($task->job,  $task->notify_user_setting);
			if (isset($data['notify_users'])) {
				$data['notify_users'] = array_merge($notifyUsers, (array)$data['notify_users']);
			} else {
				$data['notify_users'] = $notifyUsers;
			}
		}
		$task->save();

		if (isset($data['notify_users'])) {
			$task->notifyUsers()->detach();
			if (ine($data, 'notify_users') && arry_fu((array) $data['notify_users'])) {
				$task->notifyUsers()->attach((array) $data['notify_users']);
			}
		}

		Event::fire('JobProgress.Tasks.Events.TaskUpdated', new TaskUpdated($task));

		$task = $this->getById($task->id);

		$type = Task::TASK;
		if (ine($data,'attachments')) {
			$attachments = $task->moveAttachments($data['attachments']);
			$task->updateAttachments($task, $type, $attachments);

		}

		if(ine($data,'delete_attachments')) {
			$task->deleteAttachments($task, $type, $data['delete_attachments']);
		}

		return $task;
	}

	public function changeDueDate(Task $task, $dueDate)
	{
		$oldTodayTask = $task->isToday();
		$task->due_date = $dueDate;
		$task->save();
		Event::fire('JobProgress.Tasks.Events.TaskUpdated', new TaskUpdated($task));

		$task = $this->getById($task->id);

		return $task;
	}

	public function markAsCompletedOrPending(Task $task, $userId, $completed)
	{
		$task->completed = $completed;
		$task->completed_by = Null;

		if($completed) {
			$task->completed_by = $userId;
		}

		$task->save();

		Event::fire('JobProgress.Tasks.Events.TaskCompleted', new TaskCompleted($task));

		$task = $this->getById($task->id);

		return $task;
	}

	public function deleteAllTasks($userId, $status = Task::COMPLETED) {
		$tasks = $this->make()->assignedTo($userId);
		if($status == Task::COMPLETED) {
			$tasks->whereNotNull('completed');
		}elseif($status == Task::PENDING) {
			$tasks->where('completed',Null);
		}
		$tasks = $tasks->get();
		foreach ($tasks as $task) {
			$this->deleteTask($task);
		}
		return true;
	}

	public function deleteTask(Task $task) {
		$users = $task->participants()->pluck('google_task_id', 'user_id')->toArray();
		$task->participants()->detach();
		$task->notifyUsers()->detach();
		$task->deleteAllAttachments($task, Task::TASK);
		$followUp = $task->jobFollowUp;
		if($followUp) {
			$followUp->update(['task_id' => null]);
		}

		$task->delete();

		Event::fire('JobProgress.Tasks.Events.TaskDeleted', new TaskDeleted($task, $users));

		return true;
	}

	public function getFilteredTasks( $currnetUserId , $filters = array(), $sortable = true)
	{
		$tasks = $this->getTasks($sortable, $filters);

		if(ine($filters, 'includes') && in_array('stage', (array)$filters['includes'])) {
			$tasks->attachWorkflowStage();
		}

		$with = $this->getIncludes($filters);
		$tasks->with($with);
		$this->applyFilters( $tasks, $currnetUserId, $filters );

		return $tasks;
	}

	public function getTasks( $sortable = true , $filters=[]) {
		$tasks = null;
		$with = $this->getIncludes($filters);
		$tasks = $this->make($with);
		if($sortable) {

			if(ine($filters, 'sort_by')
				&& ine($filters,'sort_order')
				&& ($filters['sort_by'] == 'due_date')
			){
				$sortBy = $filters['sort_by'];
				$sortOrder = $filters['sort_order'];

				$order = "due_date IS NULL, $sortBy $sortOrder";

				if(strtolower($sortOrder) == 'desc'){
					$order = "due_date IS NOT NULL, $sortBy $sortOrder";
				}

				$tasks->orderByRaw("$order");
			}
			$tasks->Sortable();
		}

		$tasks->orderBy('tasks.created_at','desc')->select('tasks.*');

		return $tasks;
	}

	public function getById($id, array $with = array())
	{
		$query = $this->make($with);

		// get sub contractor tasks
		if(Auth::check() && Auth::user()->isSubContractorPrime()) {
			$query->subOnly(Auth::id());
		}
		$query->select('tasks.*');

		if(Request::has('includes') && in_array('stage', (array)Request::get('includes'))) {
			$query->attachWorkflowStage();
		}

		$query->groupBy('tasks.id');

		return $query->findOrFail($id);
	}

	public function findById($id, array $with = array())
	{
		$query = $this->make($with);

		// get sub contractor tasks
		if(Auth::user()->isSubContractorPrime()) {
			$query->subOnly(Auth::id());
		}

		return $query->whereId($id)->first();
	}

	/************************ Private Section ***************************/

	private function applyFilters($query, $currnetUserId, $filters = array())
	{
		// get sub contractor tasks
		if(Auth::user()->isSubContractorPrime()) {
			$query->subOnly(Auth::id());
		}

		if(ine($filters,'type')) {
			$assigTo = issetRetrun($filters, 'user_id') ?: $currnetUserId;

			if($filters['type'] == 'assigned') {
				$query->assignedTo($assigTo);
			} elseif($filters['type'] == 'created') {
				$query->where('tasks.created_by', $assigTo);
			}

		} else {
			if(ine($filters, 'user_id')) {
				$query->where(function($query) use($filters) {
					$query->assignedTo($filters['user_id'])
					->orWhere('tasks.created_by', $filters['user_id']);
				});
			} else {
				$query->division();
			}
		}

		if(ine($filters,'status')) {
			if($filters['status'] == Task::COMPLETED) {
				$query->completed();
			}elseif ($filters['status'] == Task::PENDING) {
				$query->pending();
			}
		}

		if(ine($filters, 'only_high_priority_tasks')) {
			$query->highPriorityTask();
		}

		if(ine($filters, 'title')) {
			$query->title($filters['title']);
		}

		if(ine($filters, 'duration')) {
			if($filters['duration'] == 'upcoming') {
				$query->upcoming();
			}
			elseif($filters['duration'] == 'today') {
				$query->today();
			}
			elseif($filters['duration'] == 'past') {
				$query->past();
			}
			elseif($filters['duration'] == 'date') {
				if(ine($filters,'date')) {
					$query->date($filters['date']);
				}else{
					$query->today();
				}
			}
		}

		if(ine($filters, 'date_range_type') && ine($filters,'start_date') || ine($filters,'end_date')) {
			$startDate = isSetNotEmpty($filters, 'start_date') ?: null;
			$endDate   = isSetNotEmpty($filters, 'end_date') ?: null;
			switch ($filters['date_range_type']) {
				case 'task_due_date':
				$query->taskDueDate($startDate, $endDate);
				break;
				case 'task_completion_date':
				$query->taskCompletionDate($startDate, $endDate);
				break;
			}
		}

		if(ine($filters, 'job_id')) {
			$query->whereIn('tasks.job_id', (array)$filters['job_id']);
		}

		if(ine($filters, 'stage_code')) {
			$query->whereIn('tasks.stage_code', (array)$filters['stage_code']);
		}

		if(ine($filters, 'include_locked_task')) {
			$query->where('tasks.locked', true);
		}
	}

	private function maintainActivityLog($data)
	{
		//maintain log for job stage email
		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::JOB_STAGE_TASK_CREATED,
			[],
			[],
			$data['customer_id'],
			$data['job_id'],
			$data['stage_code'],
			false
		);
	}

	/**
	 * update reminder date time field for send task reminder by CRON
	 */
	public function updateReminderDateTime($task, $dateTime, $reminderType, $reminderFrequency)
	{
		$reminderDate = Carbon::parse($dateTime);

		$action = 'add';
		if($task->is_due_date_reminder) {
			$action = 'sub';
			$timezone = Settings::forUser(Auth::id(), getScopeId())->get('TIME_ZONE');

			// convert reminder date according to login user's timezone
			$reminderDate = Carbon::parse($task->due_date)->addHours(7);
			$reminderDate = utcConvert($reminderDate, $timezone);
		}

		switch ($reminderType) {
			case 'hour':
				$method = $action."Hours";

				$reminderDate = $reminderDate->$method($reminderFrequency);
				break;
			case 'day':
				$method = $action."Days";

				$reminderDate = $reminderDate->$method($reminderFrequency);
				break;
			case 'week':
				$method = $action."Weeks";

				$reminderDate = $reminderDate->$method($reminderFrequency);
				break;
			case 'month':
				$method = $action."Months";

				$reminderDate = $reminderDate->$method($reminderFrequency);
				break;
			case 'year':
				$method = $action."Years";
				$reminderDate = $reminderDate->$method($reminderFrequency);
				break;
		}

		$task->reminder_date_time = $reminderDate->toDateTimeString();
		$task->save();

		return $task;
	}

	public function lockTasksCount($jobId, $stageCode)
	{
		$job = $this->jobRepo->getById($jobId);
		$lockedTaskCount = Task::where('job_id', $jobId)
			->where('stage_code', $stageCode)
			->where('locked', true)
			->whereNull('completed')
			->whereNull('deleted_at')
			->whereCompanyId(getScopeId())
			->count();

		return $lockedTaskCount;
	}

	public function markAsUnlock($locked)
	{
		if (!(SecurityCheck::hasPermission('mark_task_unlock'))) {
			throw new UnauthorizedException("You don't have the permission to lock / unlock this task.");
		}

		return $locked;
	}

	public function validStageCode($task, $stageCode)
	{
		$job = $task->job;
		$workflowStages = WorkflowStage::where('workflow_id', $job->workflow_id)
			->where('code', $stageCode)
			->first();

		return $workflowStages;
	}

	public function validateStages($task)
	{
		$job = $task->job;
		if($job) {
			$workflowHistoryStages = $job->jobWorkflowHistory->pluck('stage')->toArray();
			if(in_array($task->stage_code, $workflowHistoryStages)) {
				return true;
			}
			$workflow = $job->jobWorkflow;

			return (bool)$workflow->last_stage_completed_date;
		}
		return false;
	}

	private function getSettingUser($job, $types) {
		$users = [];
		foreach ((array)$types as $type) {
			switch ($type) {
				case 'customer_rep':
					$users = array_merge($users, (array)$job->customer->rep_id);
					break;
				case 'subs':
					$sub = $job->subContractors()->select('users.id')->pluck('id')->toArray();
					$users = array_merge($users, $sub);
					break;
				case 'estimators':
					$estimates = $job->estimators()->select('users.id')->pluck('id')->toArray();
					$users = array_merge($users, $estimates);
					break;
				case 'company_crew':
					$companycrew = $job->reps()->select('users.id')->pluck('id')->toArray();
					$users = array_merge($users, $companycrew);
					break;
			}
		}
		return $users;
	}

	private function getIncludes($input)
	{
		$with = [
			'createdBy',
			'participants.profile',
			'notifyUsers.profile',
		];

		if(!isset($input['includes'])) return $with;

		$includes = (array)$input['includes'];

		if(in_array('message', $includes)) {
			$with[] = 'message';
		}

		if(in_array('attachments', $includes)) {
			$with[] = 'attachments';
		}

		return $with;
	}
}