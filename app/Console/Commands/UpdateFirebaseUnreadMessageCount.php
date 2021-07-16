<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Firebase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateFirebaseUnreadMessageCount extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update_firebase_unread_message_count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update firebase unread messge count.';

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
        $firebase = new \Firebase\FirebaseLib(config('firebase.url'), config('firebase.database_secret'));
        $companies = json_decode($firebase->get('company'), true);
        foreach ($companies as $companyId => $users) {
            if(!ine($users, 'users')) continue;
            foreach ($users['users'] as $userId => $user) {
                $unreadMessageCount = 0;
                if(isset($user['count']['unread_messages_count'])) {
                    $unreadMessageCount = $user['count']['unread_messages_count'];
                }
                $message = \App\Models\Message::where('company_id', $companyId)
                    ->join(\DB::raw("(SELECT * FROM message_status where user_id = {$userId})AS message_status"), 'message_status.message_id', '=', 'messages.id')
                    ->where('message_status.status', \App\Models\Message::UNREAD)
                    ->select(\DB::raw('COUNT(messages.id) as unread_message_count'))
                    ->first();
                
                if($message->unread_message_count == $unreadMessageCount) continue;
                \Log::info("Unread message count update for user:{$userId} from C:{$unreadMessageCount} to C:{$message->unread_message_count}");
                $firebase->set("company/{$companyId}/users/{$userId}/count/unread_messages_count", $message->unread_message_count);
            }
        }
    }
}
