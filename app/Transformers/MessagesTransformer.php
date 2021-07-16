<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;

class MessagesTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['sender', 'participants', 'thread', 'media', 'task', 'job', 'customer'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($message)
    {

        return [
            'id' => $message->id,
            'thread_id' => $message->thread_id,
            'subject' => $message->subject,
            'content' => $message->content,
            'created_at' => $message->created_at,
            'sms_status'     => $message->sms_status
        ];
    }

    public function includeSender($message)
    {
        $sender = $message->sender;

        if ($sender) {
            return $this->item($sender, function ($sender) {
                return [
                    'id' => $sender->id,
                    'first_name' => $sender->first_name,
                    'last_name' => $sender->last_name,
                    'full_name' => $sender->full_name,
                    'full_name_mobile' => $sender->full_name_mobile,
                    'profile_pic' => $sender->getUserProfilePic(),
                ];
            });
        }
    }

    /**
     * Include Recipients
     *
     * @return League\Fractal\ItemResource
     */
    public function includeParticipants($message)
    {
        $participants = $message->thread->participants;

        if ($participants) {
            return $this->collection($participants, function ($participant) {
                return [
                    'id' => $participant->id,
                    'first_name' => $participant->first_name,
                    'last_name' => $participant->last_name,
                    'full_name' => $participant->full_name,
                    'full_name_mobile' => $participant->full_name_mobile,
                    'profile_pic' => $participant->getUserProfilePic(),
                ];
            });
        }
    }

    /**
     * Include Job
     * @param  Instance $message Message
     * @return Thread
     */
    public function includeThread($message)
    {
        $thread = $message->thread;

        if ($thread) {
            $thread->thread_id = $message->thread_id;
            return $this->item($thread, new MessageThreadTransformer);
        }
    }

    public function includeCustomer($message) {
        $customer = $message->job->customer;

        if($customer){
            $transform = new CustomersTransformerOptimized;
            $transform->setDefaultIncludes([]);


            return $this->item($customer, $transform);
        }
    }

    public function includeJob($message){
        $job = $message->job;
        if($job) {
            return $this->item($job, function($job) {
                $data = [
                    'id'        => $job->id,
                    'number'    => $job->number,
                    'archived'  => $job->archived,
                    'parent_id' => $job->parent_id,
                    'multi_job' => $job->multi_job,
                    'alt_id'    => $job->alt_id,
                    'name'      => $job->name,
                ];

                return $data;
            });
        }
    }

    public function includeMedia($message)
    {
        $media = $message->media;
        if(!$media->isEmpty()) {
            return $this->collection($media, function($media){
                return [
                    'id'            => $media->id,
                    'message_sid'   => $media->sid,
                    'media_url'     => $media->media_url,
                    'short_url'     => $media->short_url,
                ];
            });
        }
    }

    public function includeTask($message)
    {
        $task = $message->task;

        $transformer = new TasksTransformer;
        $transformer->setDefaultIncludes([]);

        if($task) {
            return $this->item($task, $transformer);
        }
    }
}
