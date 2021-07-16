<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CreateJobMessageThread extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_job_message_thread';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create job message thread.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exclude = [];

        $totalJobMessage = Message::whereNotNull('job_id')
            ->where('thread_id', '')
            ->groupBy('job_id')->get(['id'])->count();

        $excludeJobs = [];

        $this->info('Total jobs without thread:' . $totalJobMessage);
        $this->info('------------ Start --------------------');

        createMessage:{

        //get message jobs
        if (File::exists('message_jobs.txt')) {
            $exclude = explode(',', rtrim(File::get('message_jobs.txt'), ','));
        }

        $this->createJobMessage($exclude);
        }

        $count = Message::whereNotIn('job_id', $exclude)
            ->whereNotNull('job_id')
            ->where('thread_id', '')
            ->groupBy('job_id')
            ->get(['id'])
            ->count();

        if ($count) {
            goto createMessage;
        }
        $this->info('------------ End ----------------');
        $this->info('Pending job thread: ' . $count);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function createJobMessage($excludeJobs)
    {
        try {
            $jobs = Message::whereNotIn('job_id', $excludeJobs)
                ->whereNotNull('job_id')
                ->where('thread_id', '')
                ->get()
                ->groupBy('job_id')
                ->toArray();
            $companyId = null;
            foreach ($jobs as $jobId => $messages) {
                $users = Message::where('job_id', $jobId)
                    ->leftJoin('message_recipient', 'message_recipient.message_id', '=', 'messages.id')
                    ->get(['message_recipient.user_id', 'messages.sender_id']);

                $jobParticipants = array_merge($users->pluck('user_id')->toArray(), $users->pluck('sender_id')->toArray());
                $companyId = $messages[0]->company_id;
                $jobParticipants = array_unique($jobParticipants);

                $thread = $this->createThread($jobId, $companyId, $jobParticipants);

                foreach ($messages as $message) {
                    $participants = [];
                    $messageStatus = [];
                    $companyId = $message->company_id;

                    $recipients = DB::table('message_recipient')
                        ->where('message_id', $message->id)
                        ->where('user_id', '!=', $message->sender_id)
                        ->select('user_id', 'message_id', 'status')
                        ->get();
                    $recipients = json_decode(json_encode($recipients), true);
                    $participants = array_column($recipients, 'user_id');
                    $participants[] = $message->sender_id;

                    $participants = array_diff($jobParticipants, $participants);
                    $participants = arry_fu($participants);

                    if (!empty($participants)) {
                        $message->status()->attach($participants, [
                            'status' => Message::READ,
                            'thread_id' => $thread->id
                        ]);
                    }

                    $this->saveMessageStatus($thread, $recipients);
                }

                DB::table('messages')->where('job_id', $jobId)
                    ->whereNull('deleted_at')
                    ->update(['thread_id' => $thread->id]);

                File::append('message_jobs.txt', $jobId . ',');

                $this->info('Created thread of job id :' . $jobId);
            }
        } catch (\Exception $e) {
            $this->error(getErrorDetail($e));
        }
    }

    public function createThread($jobId, $companyId, $participants)
    {
        $participants = arry_fu($participants);
        sort($participants);

        $data = [
            'job_id' => $jobId,
        ];
        $threadId = generateUniqueToken();
        $thread = \App\Models\MessageThread::firstOrNew($data);
        $thread->participant = implode('_', $participants);
        $thread->id = $threadId;
        $thread->company_id = $companyId;
        $thread->save();

        $thread->participants()->attach($participants);

        return $thread;
    }

    public function saveMessageStatus($thread, $messageStatus)
    {
        if (empty($messageStatus)) {
            return true;
        }

        foreach ($messageStatus as $key => $status) {
            $messageStatus[$key]['thread_id'] = $thread->id;
        }
        DB::table('message_status')->insert($messageStatus);
    }
}
