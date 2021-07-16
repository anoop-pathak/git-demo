<?php

namespace App\Repositories;

use App\Events\NewMessageEvent;
use App\Models\Message;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\DB;

class MessageRepository extends ScopedRepository
{
    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    protected $jobRep;

    function __construct(Message $model, Context $scope, JobRepository $jobRep)
    {

        $this->model = $model;
        $this->scope = $scope;
        $this->jobRep = $jobRep;
    }

    /**
     * Get filtered message
     * @param  array $filters filters
     * @return queryBuilder
     */
    public function getFilteredMessage($threadId, $filters = [])
    {
        $with = $this->includeData($filters);
        $messages = $this->make($with);

        $userId = Auth::id();

        //detach deleted message.
        $messages->leftJoin(DB::raw("(SELECT * FROM message_status where user_id = {$userId}) AS message_status"), 'message_status.message_id', '=', 'messages.id');
        $messages->where(function ($query) {
            $query->where('message_status.status', '!=', Message::DELETED);
            $query->orWhereNull('message_status.status'); //sender has status always null
        });

        $messages->where('messages.thread_id', $threadId);

        $messages->select('messages.*');

        $this->applyFilters($messages, $filters);

        $messages->orderBy('messages.created_at', 'DESC');

        return $messages;
    }

    /**
     * send messsage
     * @param  int $senderId user id
     * @param  array $participants participants
     * @param  string $subject subject
     * @param  string $content message body
     * @param  string $threadId thread id
     * @param  array $otherData meta info
     * @return message
     */
    public function sendMessage($senderId, $participants, $subject, $content, $threadId, $otherData = [])
    {

        $message = new Message;
        $message->sender_id = $senderId;
        $message->subject = $subject;
        $message->content = $content;
        $message->company_id = $this->scope->id();
        $message->thread_id = $threadId;
        $message->job_id = isset($otherData['job_id']) ? $otherData['job_id'] : null;
        $message->customer_id = isset($otherData['customer_id']) ? $otherData['customer_id'] : null;
        $message->save();

        $message->status()->attach($participants, [
            'status' => Message::UNREAD,
            'thread_id' => $threadId
        ]);

        //event for new message..
        Event::fire('JobProgress.Messages.Events.NewMessageEvent', new NewMessageEvent($message, $participants));

        return $message;
    }

    /**
     * Get unread message count
     * @param  Int $userId User id
     * @return Unread message count
     */
    public function getUnreadMessagesCount($userId, $filters = [])
    {
        $message = $this->make()
            ->division()
            ->join(DB::raw("(SELECT * FROM message_status where user_id = {$userId})AS message_status"), 'message_status.message_id', '=', 'messages.id')
            ->where('message_status.status', Message::UNREAD)
            ->select(DB::raw('COUNT(messages.id) as unread_message_count'));

        if(ine($filters, 'message_type')) {
            $message->join('message_threads as mt', 'mt.id', '=', 'messages.thread_id');
            $message->where('mt.type', '=', $filters['message_type']);
        }

        if (ine($filters, 'last_read_message_id')) {
            $message->where('messages.id', '>', $filters['last_read_message_id']);
        }

        if (ine($filters, 'thread_id')) {
            $message->where('messages.thread_id', $filters['thread_id']);
        }

        $message = $message->first();

        return (int)$message->unread_message_count;
    }

    public function getRecentActivity($filters=[])
    {
        $with = $this->includeData($filters);
        $thread = $this->make($with);

        $thread->leftJoin('message_threads', 'messages.thread_id', '=', 'message_threads.id');
        $thread->orderBy('messages.id', 'desc');
        $thread->select('messages.*', 'message_threads.job_id');

        $filters['with_archived']     = true;
        $filters['include_lost_jobs'] = true;
        $filters['include_projects']  = true;

        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobs = generateQueryWithBindings($jobs);

        $thread->join(DB::raw("($jobs) as jobs"), 'message_threads.job_id', '=', 'jobs.id');

        return $thread;
    }

    /*****************Private Methods *******************/

    private function applyFilters($query, $filters = [])
    {
    }

    private function includeData($input)
    {
        $with = [];
        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('sender', $includes)) {
            $with[] = 'sender.profile';
        }

        if (in_array('participants', $includes)) {
            $with[] = 'thread.participants.profile';
        }

        if (in_array('thread.job', $includes)) {
            $with[] = 'thread.job';
        }

        if (in_array('thread.participants', $includes)) {
            $with[] = 'thread.participants';
        }

        if(in_array('job', $includes)) {
            $with[] = 'job';
        }

        if(in_array('customer', $includes)) {
            $with[] = 'job.customer';
        }

        if(in_array('task', $includes)) {
            $with[] = 'task';
        }

        if(in_array('media', $includes)) {
            $with[] = 'media';
        }

        return $with;
    }
}
