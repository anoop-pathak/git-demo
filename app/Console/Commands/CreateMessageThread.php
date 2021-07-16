<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CreateMessageThread extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_message_thread';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create message threads.';

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
        try {
            $this->createMessageThread();
        } catch (\Exception $e) {
            $this->error(getErrorDetail($e));
        }
    }

    public function createMessageThread()
    {
        createMessages: {

        $exclude = [];

        //get message ids
        if (File::exists('messages2.txt')) {
            $exclude = explode(',', rtrim(File::get('messages2.txt'), ','));
        }

        $messages = Message::whereNotIn('id', $exclude)
            ->where('thread_id', '')
            ->chunk(500, function ($messages) {
                foreach ($messages as $message) {
                    $participants = [];
                    $stausData = [];
                    $recipients = DB::table('message_recipient')->where('message_id', $message->id)
                        ->select('status', 'user_id', 'message_id')
                        ->get();
                    $recipients = json_decode(json_encode($recipients), true);

                    // one to one user message thread
                    if (count($recipients) <= 1) {
                        $participants[] = $message->sender_id;
                        if (!empty($recipients)) {
                            $firstRecipient = reset($recipients);
                            $participants[] = $firstRecipient['user_id'];
                        }

                        $this->createThread($message, $participants, $recipients);
                        continue;
                    }

                    //one to many user message thread
                    $firstRecipient = reset($recipients);
                    $participants[] = $firstRecipient['user_id'];
                    $participants[] = $message->sender_id;
                    $stausData[] = $firstRecipient;
                    $this->createThread($message, $participants, $stausData);
                    unset($recipients[0]);

                    foreach ($recipients as $recipient) {
                        $messageStatusData = [];
                        $participants = [];
                        $newMessage = $message->replicate();
                        $newMessage->created_at = $message->created_at;
                        $newMessage->updated_at = $message->updated_at;
                        $newMessage->clone_id = $message->id;
                        $newMessage->save();
                        $participants[] = $recipient['user_id'];
                        $participants[] = $message->sender_id;
                        $recipient['message_id'] = $newMessage->id;
                        $messageStatusData[] = $recipient;

                        $this->createThread($newMessage, $participants, $messageStatusData);
                        continue;
                    }
                }
            });
        }
        //get message ids
        if (File::exists('messages2.txt')) {
            $exclude = explode(',', rtrim(File::get('messages2.txt'), ','));
        }

        $count = Message::whereNotIn('id', $exclude)
            ->where('thread_id', '')
            ->count();

        if ($count) {
            goto createMessages;
        }
    }

    public function createThread($message, $participants, $messageStatus = [])
    {
        $participants = arry_fu((array)$participants);

        sort($participants);

        $participant = $threadId = implode('_', $participants);

        if ($message->job_id) {
            $thread = MessageThread::firstOrNew([
                'job_id' => $message->job_id,
                'company_id' => $message->company_id,
                'participant' => $participant
            ]);

            if (!$thread->id) {
                $thread->id = generateUniqueToken();
                $thread->save();
                $thread->participants()->sync($participants);
            }
        } else {
            $thread = MessageThread::firstOrNew([
                'id' => $threadId,
                'company_id' => $message->company_id,
            ]);

            if (!$thread->participant) {
                $thread->participant = $participant;
                $thread->save();
                $thread->participants()->sync($participants);
            }
        }


        DB::table('messages')->where('id', $message->id)
            ->update([
                'thread_id' => $thread->id
            ]);

        if (!empty($messageStatus)) {
            foreach ($messageStatus as $key => $status) {
                if ($status['user_id'] == $message->sender_id) {
                    unset($messageStatus[$key]);
                    continue;
                }
                $messageStatus[$key]['thread_id'] = $thread->id;
            }
        }

        if (!empty($messageStatus)) {
            DB::table('message_status')->insert($messageStatus);
        }

        File::append('messages2.txt', $message->id . ',');

        $this->info('Create thread of message id :' . $message->id);
    }
}
