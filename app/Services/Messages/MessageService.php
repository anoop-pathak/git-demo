<?php

namespace App\Services\Messages;

use App\Repositories\MessageRepository;
use App\Repositories\MessageThreadRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\MessageStatus;
use App\Models\Message;
use App\Models\MessageThread;
use Queue;
use Illuminate\Support\Facades\Mail;
use App\Services\Emails\EmailServices;

class MessageService
{

    public function __construct(
        MessageRepository $repo,
        MessageThreadRepository $threadRepo,
        EmailServices $emailService
    ) {

        $this->repo = $repo;
        $this->threadRepo = $threadRepo;
        $this->emailService = $emailService;
    }

    /**
     * Get thread list
     * @param  array $filters array
     * @return querybuilder
     */
    public function getThreadList($filters = [])
    {
        $thread = $this->threadRepo->getFilteredThread($filters);

        return $thread;
    }

    /**
     * Get messge by thread id
     * @param  Instance $thread thrad
     * @param  array $filters array
     * @return messages
     */
    public function getThreadMessages($thread, $filters = [])
    {
        $filters['thread_id'] = $thread->id;

        $userId = Auth::id();
        $this->threadRepo->markAsRead($thread, $userId);

        return $this->repo->getFilteredMessage($thread->id, $filters);
    }

    /**
     * Send message
     * @param  int $senderId user id
     * @param  array $participants array of ids
     * @param  string $subject subject
     * @param  string $content message body
     * @param  array $meta meta info
     * @return response
     */
    public function sendMessage($senderId, $participants, $subject, $content, $meta = [])
    {
        $threadId = ine($meta, 'thread_id') ? $meta['thread_id'] : null;

        if (!$threadId) {
            $participants[] = $senderId;
            $participants = arry_fu($participants);
            sort($participants);
            if (ine($meta, 'job_id')) {
                $thread = $this->createThread($participants, $meta['job_id'], null, $meta);
            } elseif (count($participants) <= 2) {
                $thread = $this->getUserThread($participants);
            } else {
                $thread = $this->createThread($participants, null, null, $meta);
            }
            $threadId = $thread->id;
        } else {
            $thread = $this->threadRepo->getById($threadId);
        }

        $participants = array_diff($thread->participants->pluck('id')->toArray(), (array)$senderId);
        $message = $this->repo->sendMessage(
            $senderId,
            $participants,
            $subject,
            $content,
            $threadId,
            $meta
        );

        if(ine($meta, 'send_as_email')) {
            $this->sendAsEmail($thread, $content, $meta);
        }

        return $message;
    }

    /**
     * Get user thread
     * @param  array $participants array participants
     * @return thread
     */
    public function getUserThread($participants)
    {
        $threadId = implode('_', $participants);
        $thread = $this->threadRepo->find($threadId);

        if ($thread) {
            return $thread;
        }

        $thread = $this->threadRepo->save($threadId, $jobId = null, $participants);

        return $thread;
    }

    /**
     * Create thread
     * @param  array $participants participants ids
     * @param  int $jobId job id
     * @param  string $threadId thread id
     * @return thread
     */
    public function createThread($participants, $jobId = null, $threadId = null, $meta = [])
    {
        $participant = implode('_', $participants);
		$thread = MessageThread::where('job_id', $jobId)->where('participant', $participant)->where('company_id', getScopeId())->first();

		if($thread) return $thread;

        $threadId = ($threadId) ? $threadId : generateUniqueToken();
        $thread = $this->threadRepo->save($threadId, $jobId, $participants, $meta);

        return $thread;
    }

    /**
	 * Create smsThread
	 * @param  array  $participants participants ids
	 * @param  int    $jobId        job id
	 * @param  string $threadId     thread id
	 * @return thread
	 */
	public function createSmsThread($participants, $threadId = null, $meta = array())
	{
		$threadId = ($threadId) ? $threadId : generateUniqueToken();
        $thread = $this->threadRepo->saveSms($threadId, $participants, $meta);

        return $thread;
    }

    /**
     * Get thread by user id
     * @param  int $threadId thread id
     * @return response
     */
    public function getThreadById($threadId)
    {
        return $this->threadRepo->getById($threadId);
    }

    /**
     * Get unread message count
     * @param  Int $userId user id
     * @return count
     */
    public function getUnreadMessagesCount($userId, $filters = [])
    {
        return $this->repo->getUnreadMessagesCount($userId, $filters);
    }

    /**
     * Get single user thread
     * @param  array $filters filters
     * @return single user thread
     */
    public function getSingleUserThread($filters)
    {
        if (!ine($filters, 'current_user_id')) {
            return null;
        }

        if (!ine($filters, 'participants')) {
            return null;
        }
        $participants = $filters['participants'];
        $participants[] = $filters['current_user_id'];
        $participants = arry_fu($participants);

        if (count($participants) > 2) {
            return null;
        }

        sort($participants);

        $threadId = implode('_', $participants);
        $filters['single_user_thread'] = $threadId;

        $thread = $this->threadRepo->getFilteredThread($filters);

        return $thread->first();
    }

    /**
     * Thread Mark As Unread
     *
     * @return response
     */
    public function threadMarkAsUnread($userId, $threadId)
    {
        $thread = MessageThread::join('message_thread_participants',
            'message_threads.id', '=', 'message_thread_participants.thread_id')
            ->where('user_id', $userId)
            ->where('message_threads.id', $threadId)
            ->firstOrFail();
        $message = Message::where('thread_id', $threadId)->orderBy('id', 'desc')->first();
        $messageStatus = MessageStatus::firstOrNew ([
            'thread_id'  => $threadId,
            'message_id' => $message->id,
            'user_id'    => $userId
        ]);
        if($messageStatus
            && ($messageStatus->status === Message::UNREAD)) return $message;
        //mark as unread
        $messageStatus->status = Message::UNREAD;
        $messageStatus->save();
        Queue::push('\App\Handlers\Events\MessagesQueueHandler@markAsRead', [
            'user_id' => $userId
        ]);
        return $message;
    }

	public function getTaskParticipantThread($participant, $jobId = null)
	{
		$thread = MessageThread::join('tasks', 'tasks.thread_id', '=', 'message_threads.id')
            ->where('message_threads.job_id', $jobId)
			->where('participant', $participant)
  			->where('message_threads.company_id', getScopeId())
  			->first();

		return $thread;
	}

    /*************** PRIVATE METHODS ***************/

    private function sendAsEmail($thread, $content, $meta)
    {
        if($job = $thread->job) {
			$meta['job_id'] = $job->id;
			$meta['customer_id'] = $job->customer_id;
        }

        $content = str_replace("\n", "<br>", $content);
        $subject = 'New Message';
        $to = $thread->participants->pluck('email')->toArray();
        # unset current user email
        if((count($to) > 1) && ($key = array_search(Auth::user()->email, $to)) !== false) {
            unset($to[$key]);
        }
        $this->emailService->sendEmail(
            $subject,
            $content,
            $to,
            $cc = array(),
            $bcc = array(),
            $attachments = array(),
            Auth::id(),
            $meta
        );
    }
}
