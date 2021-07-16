<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Models\MessageThread;
use App\Transformers\TasksTransformer;

class MessageThreadTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
        'sender',
        'participants',
        'job',
        'created_by',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($message)
    {
        $count = 0;

        if($messageCount = $message->unreadMessageCount->first()) {
            $count = $messageCount->count;
        }
        $data = [
            'thread_id' => $message->thread_id,
            'subject' => $message->subject,
            'content' => $message->content,
            'type'      => $message->type,
            'sms_status'=> $message->sms_status,
            'unread_message_count' => (int)$count,
            'created_at' => $message->created_at,
            'phone_number' => $message->phone_number
        ];

        return $data;
    }

    /**
     * Include Sender
     *
     * @return League\Fractal\ItemResource
     */
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
                    'group_id'    => $sender->group_id,
                    'color' => $sender->color
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
        if ($message->type == MessageThread::TYPE_SMS) {
            $userParticipants = $message->userParticipants;
            $customerParticipants = $message->customerParticipants;

            if($userParticipants){
                if ($customerParticipants) {
                    $participants = $userParticipants->merge($customerParticipants);
                }

                return  $this->collection($participants, function($participant){
                    if (get_class($participant) == 'Customer') {
                        return [
                            'id'               => $participant->id,
                            'first_name'       => $participant->first_name,
                            'last_name'        => $participant->last_name,
                            'full_name'        => $participant->full_name,
                            'full_name_mobile' => $participant->full_name_mobile,
                            'type'             => MessageThread::CUSTOMER_PARTICIPANTS
                        ];
                    }

                    else {
                        return [
                            'id'         => $participant->id,
                            'first_name' => $participant->first_name,
                            'last_name'  => $participant->last_name,
                            'full_name'  => $participant->full_name,
                            'full_name_mobile'  => $participant->full_name_mobile,
                            'profile_pic'=> $participant->getUserProfilePic(),
                            'group_id'   => $participant->group_id,
                            'type'       =>  MessageThread::USER_PARTICIPANTS,
                            'color'      => $participant->color
                        ];
                    }
                });
            }
        }
        else {
            $participants = $message->participants;
            if($participants){

                return $this->collection($participants, function($participant){
                    return [
                        'id'         => $participant->id,
                        'first_name' => $participant->first_name,
                        'last_name'  => $participant->last_name,
                        'full_name'  => $participant->full_name,
                        'full_name_mobile' => $participant->full_name_mobile,
                        'profile_pic' => $participant->getUserProfilePic(),
                        'color' => $participant->color
                    ];
                });
            }
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($message)
    {
        $job = $message->job;
        if ($job) {
            $jobTrans = new JobsTransformerOptimized;
            $jobTrans->setDefaultIncludes(['customer']);
            $job->hideProjectCount = true;
            return $this->item($job, $jobTrans);
        }
    }

    public function includeCreatedBy($message)
    {
        $createdBy = $message->createdBy;
        if($createdBy) {
            return $this->item($createdBy, function($createdBy){

                return [
                    'id'         => $createdBy->id,
                    'first_name' => $createdBy->first_name,
                    'last_name'  => $createdBy->last_name,
                    'full_name'  => $createdBy->full_name,
                    'full_name_mobile' => $createdBy->full_name_mobile,
                    'profile_pic' => $createdBy->getUserProfilePic(),
                    'group_id' => $createdBy->group_id,
                    'color' => $createdBy->color
                ];
            });
        }
    }
}
