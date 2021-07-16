<?php

namespace App\Console\Commands;

use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateConversationID extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:generate_conversation_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Convesation Id';

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
        $this->generateForThread();
        $this->generateForThread();
    }

    private function generateForThread()
    {
        $emails = Email::whereNull('reply_to')->where('conversation_id', '')
            ->withTrashed();
        $emails->chunk(200, function ($emails) {
            foreach ($emails as $key => $email) {
                $this->generateConversationID($email);
            }
        });
    }

    private function generateConversationID($email)
    {
        DB::beginTransaction();
        try {
            $conversationId = Carbon::now()->timestamp . rand();
            $ids = $email->getRecursiveThreadIds();
            DB::table('emails')->whereIn('id', $ids)
                ->update([
                    'conversation_id' => $conversationId,
                    'customer_id' => $email->customer_id,
                    'job_id' => $email->job_id,
                ]);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            DB::rollback();
        }
        DB::commit();
    }
}
