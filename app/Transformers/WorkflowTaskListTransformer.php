<?php

namespace App\Transformers;

use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class WorkflowTaskListTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['stage', 'notify_users'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['tasks', 'participants'];

    public function transform($task)
    {
        return [
    		'id'                   => $task->id,
    		'title'                => $task->title,
    		'notes'                => $task->notes,
            'is_high_priority_task'=> (int)$task->is_high_priority_task,
    		'created_at'           => $task->created_at,
            'updated_at'           => $task->updated_at,
            'assign_to_setting'    => (array)$task->assign_to_setting,
            'notify_user_setting'  => (array)$task->notify_user_setting,
            'reminder_type'        => $task->reminder_type,
            'reminder_frequency'   => $task->reminder_frequency,
            'is_due_date_reminder' => (int)$task->is_due_date_reminder,
            'locked'     => (int)$task->locked,
            'message_notification' => (int)$task->message_notification,
            'email_notification' => (int)$task->email_notification,
    	];
    }

    /**
     * Include user
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTasks($taskList)
    {
        $tasks = $taskList->tasks;
        if ($tasks) {
            return $this->collection($tasks, new TasksTransformer);
        }
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStage($emailTemplate)
    {
        $stage = $emailTemplate->stage;
        if ($stage) {
            return $this->item($stage, function ($stage) {
                return $stage->toArray();
            });
        }
    }

    /**
     * Include user
     *
     * @return League\Fractal\ItemResource
     */
    public function includeParticipants($taskList)
    {
        $users = $taskList->participants;
        if ($users) {
            return $this->collection($users, new UsersTransformerOptimized);
        }
    }

    /**
     * Include notify users
     *
     * @return League\Fractal\ItemResource
     */
    public function includeNotifyUsers($taskList)
    {
        $notifyUsers = $taskList->notifyUsers;

        if ($notifyUsers) {
            return $this->collection($notifyUsers, new UsersTransformerOptimized);
        }
    }
}
