<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Transformers\AttachmentsTransformer;
use App\Transformers\MessagesTransformer;
use App\Transformers\MessageThreadTransformer;

class TasksTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'participants',
        'notify_users'
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'participants',
        'created_by',
        'job',
        'stage',
        'customer',
        'completed_by',
        'notify_users',
        'attachments',
        'message',
        'attachments_count'
    ];

    public function transform($task)
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'notes' => $task->notes,
            'due_date' => $task->due_date,
            'job_id' => $task->job_id,
            'stage_code' => $task->stage_code,
            'completed' => $task->completed,
            'is_high_priority_task'  => (int)$task->is_high_priority_task,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
            'notify_user_setting' => (array)$task->notify_user_setting,
            'assign_to_setting' => (array)$task->assign_to_setting,
            'locked'     => (int)$task->locked,
            'reminder_type'			=> $task->reminder_type,
            'reminder_frequency'	=> $task->reminder_frequency,
            'is_due_date_reminder'  => (int)$task->is_due_date_reminder,
            'is_wf_task'            => (int)(bool)$task->wf_task_id,
        ];
    }

    /**
     * Include user
     *
     * @return League\Fractal\ItemResource
     */
    public function includeParticipants($task)
    {
        $users = $task->participants;
        if ($users) {
            return $this->collection($users, new UsersTransformerOptimized);
        }
    }

    /**
     * Include user
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCompletedBy($task)
    {
        $user = $task->completedBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($task)
    {
        $user = $task->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($task)
    {
        $job = $task->job;
        if ($job) {
            return $this->item($job, new JobsTransformerOptimized);
        }
    }

    /**
     * Include Stage
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStage($task)
    {
        if($task->wf_stage_id) {
            return $this->item($task, function($task) {
               return [
                   'id'                => $task->wf_stage_id,
                   'code'              => $task->wf_stage_code,
                   'workflow_id'       => $task->wf_stage_workflow_id,
                   'name'              => $task->wf_stage_name,
                   'locked'            => $task->wf_stage_locked,
                   'position'          => $task->wf_stage_position,
                   'color'             => $task->wf_stage_color,
               ];
           });
       }
    }

    /**
     * Include customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includecustomer($task)
    {
        $job = $task->job;
        $customer = $task->customer;
        if (!empty($customer) && !empty($job)) {
            return $this->item($customer, new CustomersTransformer);
        }
    }

    /**
     * Include notify users
     *
     * @return League\Fractal\ItemResource
     */
    public function includeNotifyUsers($task)
    {
        $notifyUsers = $task->notifyUsers;

        if ($notifyUsers) {
            return $this->collection($notifyUsers, new UsersTransformerOptimized);
        }
    }

    /**
     * Include Attachments
     * @param  Instance $schedule Schedule
     * @return Attachments
     */
    public function includeAttachments($task)
    {
        $attachments = $task->attachments;
        if($attachments) {
            return $this->collection($attachments, new AttachmentsTransformer);
        }
    }

    public function includeMessage($task)
    {
        $message = $task->message;

        if($message) {
            return $this->item($message, new MessagesTransformer);
        }
    }

    /**
     * Include Attachments count
     * @param  Instance $task Task
     * @return Response
     */
    public function includeAttachmentsCount($task)
    {
        $count = $task->attachments()->count();

        return $this->item($count, function($count){

            return [
                'count' => $count
            ];
        });
    }
}
