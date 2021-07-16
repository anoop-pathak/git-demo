<?php

namespace App\Repositories;

use App\Models\WorkflowTaskList;
use App\Services\Contexts\Context;

class WorkflowTaskListRepository extends ScopedRepository
{

    protected $model;
    protected $googleTaskService;
    protected $scope;

    public function __construct(WorkflowTaskList $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function createWorkflowTask($title, $meta = [])
    {
        $workflowTask = $this->model;
        $workflowTask->company_id = $this->scope->id(); // current company id
        $workflowTask->stage_code = ine($meta, 'stage_code') ? $meta['stage_code'] : null;;
        $workflowTask->title = $title;
        $workflowTask->notes = ine($meta, 'notes') ? $meta['notes'] : null;
        $workflowTask->assign_to_setting = ine($meta, 'assign_to_setting') ? $meta['assign_to_setting'] : null;
		$workflowTask->notify_user_setting = ine($meta, 'notify_user_setting') ? $meta['notify_user_setting'] : null;
		$workflowTask->locked = ine($meta, 'locked');
		$workflowTask->reminder_type = isSetNotEmpty($meta, 'reminder_type') ?: null;
		$workflowTask->reminder_frequency = isSetNotEmpty($meta, 'reminder_frequency') ?: null;
		$workflowTask->is_due_date_reminder = ine($meta, 'is_due_date_reminder');
		$workflowTask->is_high_priority_task = ine($meta, 'is_high_priority_task');
        $workflowTask->message_notification = ine($meta, 'message_notification');
		$workflowTask->email_notification = ine($meta, 'email_notification');
        $workflowTask->save();

        if (ine($meta, 'participants')) {
            $this->saveParticipants($workflowTask, array_filter((array)$meta['participants']));
        }

        if (ine($meta, 'notify_users')) {
            $workflowTask->notifyUsers()->attach(arry_fu((array)$meta['notify_users']));
        }

        // enable stage create_task ..
        // WorkflowStage::whereCode($stageCode)->update(['create_tasks' => true]);

        return $workflowTask;
    }

    public function updateWorkflowTask($title, $meta = [], WorkflowTaskList $workflowTask)
    {
        $workflowTask->title = $title;
        $workflowTask->notes = ine($meta, 'notes') ? $meta['notes'] : null;
        $workflowTask->assign_to_setting = ine($meta, 'assign_to_setting') ? $meta['assign_to_setting'] : null;
		$workflowTask->notify_user_setting = ine($meta, 'notify_user_setting') ? $meta['notify_user_setting'] : null;
		$workflowTask->locked = ine($meta, 'locked');
		$workflowTask->stage_code = ine($meta, 'stage_code') ? $meta['stage_code'] : null;

		if(isset($meta['reminder_type'])){
			$workflowTask->reminder_type = $meta['reminder_type'] ?: null;
		}

		if(isset($meta['reminder_frequency'])){
			$workflowTask->reminder_frequency = $meta['reminder_frequency'] ?: null;
		}
		if(isset($meta['is_due_date_reminder'])){
			$workflowTask->is_due_date_reminder = $meta['is_due_date_reminder'] ?: false;
		}

		if(isset($meta['is_high_priority_task'])) {
			$workflowTask->is_high_priority_task = (bool)$meta['is_high_priority_task'];
		}

        if(isset($meta['message_notification'])) {
			$workflowTask->message_notification = (bool)$meta['message_notification'];
		}

		if(isset($meta['email_notification'])) {
			$workflowTask->email_notification = (bool)$meta['email_notification'];
		}

        $workflowTask->save();

        if (ine($meta, 'participants')) {
            $this->saveParticipants($workflowTask, array_filter((array)$meta['participants']));
        }

        if (isset($meta['notify_users'])) {
            $workflowTask->notifyUsers()->detach();

            if (ine($meta, 'notify_users') && arry_fu((array)$meta['notify_users'])) {
                $workflowTask->notifyUsers()->attach((array)$meta['notify_users']);
            }
        }

        return $workflowTask;
    }

    public function delete(WorkflowTaskList $workflowTask)
    {

        $stageCode = $workflowTask->stage_code;

        if ($workflowTask->notifyUsers) {
            $workflowTask->notifyUsers()->detach();
        }

        $workflowTask->delete();

        // count workflow tasks in same stage..
        // $count = $this->make()->whereStageCode($stageCode)->count();

        // if(!$count) {
        // 	// disable stage create_task ..
        //  WorkflowStage::whereCode($stageCode)->update(['create_tasks' => false]);
        // }

        return true;
    }

    public function getWorkflowTasks($filters)
    {
        $workflowTasks = $this->make(['stage']);

        if (ine($filters, 'job_id')) {
            $workflowTasks->with([
                'tasks' => function ($tasks) use ($filters) {
                    $tasks->whereJobId($filters['job_id']);
                }
            ]);
        }

        $this->applyFilters($workflowTasks, $filters);

        return $workflowTasks;
    }

    /************************ Private Section ***************************/

    private function applyFilters($query, $filters = [])
    {
        if (ine($filters, 'stage_code')) {
            $query->whereStageCode($filters['stage_code']);
        }

        if(ine($filters, 'title')) {
			$query->title($filters['title']);
		}

		if(ine($filters, 'only_high_priority_tasks')) {
			$query->onlyHighPriorityTask($filters['only_high_priority_tasks']);
		}
    }

    private function saveParticipants($workflowTask, $users)
    {
        $workflowTask->participants()->detach();
        $workflowTask->participants()->attach($users);
    }
}
