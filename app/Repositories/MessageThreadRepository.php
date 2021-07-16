<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\MessageThread;
use App\Services\Contexts\Context;
use Firebase;
use App\Handlers\Events\MarkAsReadEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\UsersRequiredException;

class MessageThreadRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(MessageThread $model, Context $scope)
    {

        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save thread
     * @param  string $threadId thread id
     * @param  int $jobId job id
     * @param  array $participants participants
     * @return thread
     */
    public function save($threadId, $jobId, array $participants = [], $meta = array())
    {
        sort($participants);
        $participant_setting = [];
        $settingUsers = [];


        if(ine($meta, 'participant_setting') && ine($meta, 'job_id')) {
            $participant_setting = $meta['participant_setting'];
        }

        $thread = $this->model->create([
            'id' => $threadId,
            'job_id' => $jobId,
            'company_id' => $this->scope->id(),
            'participant' => implode('_', $participants),
            'participant_setting' => $participant_setting,
            'type'  => MessageThread::TYPE_SYSTEM_MESSAGE,
            'created_by' => Auth::id()
        ]);

        if ($jobId) {
            $settingUsers = $this->getSettingUser($thread->job, $thread->participant_setting);
        }

        if(!empty($settingUsers)) {
            $participants = arry_fu(array_merge($settingUsers, (array)$participants));
        }

        if(!empty($thread->participant_setting) && empty($settingUsers)) {
            throw new UsersRequiredException("Please assign user to Customer Rep / Estimator / Company Crew / Sub Contractor as chosen.");
        }

        $thread->participants()->attach($participants);

        if(ine($meta, 'tag_ids')) {
            $thread->tags()->attach((array)$meta['tag_ids']);
        }

        return $thread;
    }

    /**
     * get filtered thread
     * @param  array $filters filters
     * @return querybuilder
     */
    public function getFilteredThread($filters)
    {

        $with = $this->includeData($filters);
        $thread = $this->make($with);
        $companyId = getScopeId();

        // attach recent message..
        $thread->join(DB::raw("(SELECT MAX(messages.id) as max_id, thread_id FROM messages WHERE deleted_at IS NULL AND company_id = {$companyId} GROUP BY thread_id) AS max_message"), "message_threads.id", "=", "max_message.thread_id");
        $thread->leftJoin('messages', 'max_message.max_id', '=', 'messages.id');

        // $thread->groupBy('message_threads.id');
        $thread->orderBy('messages.id', 'desc');
        $thread->select('messages.*', 'message_threads.id', 'message_threads.job_id', 'message_threads.type', 'message_threads.phone_number', 'message_threads.created_by');

        if (ine($filters, 'current_user_id')) {
            $type = ine($filters, 'type') ? $filters['type'] : MessageThread::TYPE_SYSTEM_MESSAGE;
            $thread->attachUserMessages($filters['current_user_id'], $type);
            $thread->attachUserUnreadMessageCount($filters['current_user_id']);
        }

        $this->applyfilters($thread, $filters);

        return $thread;
    }

    /**
     * Thread messager mark as read
     * @param  instance $thread thread
     * @param  int $userId user id
     * @return boolean
     */
    public function markAsRead($thread, $userId)
    {
        $count = $thread->messageStatuses()->where('user_id', $userId)
            ->where('status', Message::UNREAD)
            ->count();

        if ($count) {
            $thread->messageStatuses()->where('user_id', $userId)
                ->where('status', Message::UNREAD)
                ->update(['status' => Message::READ]);
            // Firebase::updateUserMessageCount($userId);
            Event::fire('JobProgress.Messages.Events.MarkAsReadEvent', new MarkAsReadEvent($userId));
        }
        return true;
    }

    /**
     * Find thread by id
     * @param  int $threadId thread id
     * @param  array $with relationship method name
     * @return thread
     */
    public function find($threadId, array $with = [])
    {
        return $this->make($with)->find($threadId);
    }

    /**************** Private Method **************/

    private function applyfilters($query, $filters)
    {
        $query->division();
        $participants = [];

        if (ine($filters, 'participants')) {
            $participants = arry_fu((array)$filters['participants']);
        }

        //searching thread one to many @ToDo
        if (ine($filters, 'current_user_id')
            && !empty($participants)
            && !ine($filters, 'single_user_thread')) {
            $currentUserId = $filters['current_user_id'];

            $query->oneToManySearch($currentUserId, $participants);
        }

        //searching thread one to one
        if (ine($filters, 'single_user_thread')) {
            $query->where('message_threads.id', $filters['single_user_thread']);
        }

        if (ine($filters, 'job_id')) {
            $query->where('message_threads.job_id', $filters['job_id']);
        }

        if (ine($filters, 'thread_id')) {
            $query->where('messages.thread_id', $filters['thread_id']);
        }

        if(ine($filters, 'unread_thread')  && ine($filters, 'current_user_id')) {
            $query->unreadThread($filters['current_user_id'], $filters);
        }

        if (ine($filters, 'customer_id')) {
            if (isset($filters['type']) && ($filters['type'] == MessageThread::TYPE_SMS)) {
                $query->join('message_thread_participants as customerParticipants', 'customerParticipants.thread_id','=', 'message_threads.id');
                $query->where('customerParticipants.ref_type', MessageThread::CUSTOMER_PARTICIPANTS)
                    ->where('customerParticipants.ref_id', $filters['customer_id']);
            } else {
                $query->where('messages.customer_id', $filters['customer_id']);
            }
        }

        if(ine($filters, 'user_id') && isset($filters['type']) && ($filters['type'] == MessageThread::TYPE_SMS)){
            $query->join('message_thread_participants as userParticipants', 'userParticipants.thread_id','=', 'message_threads.id');
            $query->where('userParticipants.ref_type', MessageThread::USER_PARTICIPANTS)
                ->where('userParticipants.ref_id', $filters['user_id']);
        }


        $type = ine($filters, 'type') ? $filters['type'] : MessageThread::TYPE_SYSTEM_MESSAGE;
        $query->where('type', $type);
    }

    private function includeData($input)
    {
        $with = [];
        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('job', $includes)) {
            $with[] = 'job.customer.phones';
            $with[] = 'job.jobMeta';
            $with[] = 'job.jobWorkflow';
        }

        if (in_array('participants', $includes)) {
            $with[] = 'participants.profile';
            $with[] = 'customer';
            $with[] = 'userParticipants';
            $with[] = 'customerParticipants';
        }

        if (in_array('sender', $includes)) {
            $with[] = 'sender.profile';
        }

        if(in_array('createdBy', $includes)) {
            $with[] = 'createdBy.profile';
        }

        return $with;
    }

    private function getSettingUser($job, $types)
    {
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

    public function saveSms($threadId, array $participants = array(), $meta = array())
    {
        $customerParticipant = implode('_', $participants['customer']);
        $userParticipant = implode('_', $participants['user']);
        $allParticipant = $customerParticipant. '_' .$userParticipant;

        $thread = $this->model->create([
            'id'     => $threadId,
            'company_id' => $this->scope->id(),
            'participant' => $allParticipant,
            'type'  => MessageThread::TYPE_SMS,
            'phone_number' => $meta['phone_number'],
            'created_by'   => Auth::id()
        ]);

        $last = end($participants['user']);
        $userId = $last;
        foreach ($participants as $key => $values) {
            foreach ($values as $key2 => $value) {
                $thread->participants()->attach([$userId], ['ref_id' => $value, 'ref_type' => $key]);
            }
        }

        return $thread;
    }
}
