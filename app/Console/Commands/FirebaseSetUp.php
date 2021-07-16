<?php

namespace App\Console\Commands;

use App\Helpers\SecurityCheck;
use App\Models\Company;
use App\Models\Email;
use App\Models\Message;
use App\Models\Task;
use Firebase\FirebaseLib;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class FirebaseSetUp extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:firebase_setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'firebase setup';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->firebase = new FirebaseLib(config('firebase.url'), config('firebase.database_secret'));
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $compaies = Company::with('users')->get();
        foreach ($compaies as $company) {
            Config::set('company_scope_id', $company->id);
            $context = App::make(\App\Services\Contexts\Context::class);
            $context->set($company);
            $this->companyDatabaseSetup();
            $users = $company->allUsers()->loggable()->get();
            foreach ($users as $user) {
                $this->user = $user;
                $this->userDatabaseSetup();
            }
        }
    }

    /**
     * User Database Set Up
     * @return Void
     */
    private function userDatabaseSetup()
    {
        $countData = [
            'pending_tasks_count' => $this->getPandingTaskCount(),
            'unread_messages_count' => $this->getUnreadMessageCount(),
            'unread_emails_count' => $this->getUnreadEmail(),
            'unread_notifications_count' => $this->getUnreadNotificatonCount(),
        ];

        $settingData['is_restricted'] = $this->checkRestricatedWorkflow();

        $userMeta = [
            'today_appointment_updated' => uniqueTimestamp(),
            'today_task_updated' => uniqueTimestamp(),
            'permissions_updated' => uniqueTimestamp(),
            'upcoming_tasks_updated' => uniqueTimestamp(),
            'upcoming_appointments_updated' => uniqueTimestamp(),
        ];

        //set url
        $userUrl = 'company/' . config('company_scope_id') . '/users/' . $this->user->id;
        $userCountUrl = $userUrl . '/count';
        $userSettingUrl = $userUrl . '/settings';

        //send firebase request
        $this->update($userCountUrl, $countData);
        $this->update($userSettingUrl, $settingData);
        $this->update($userUrl, $userMeta);
    }

    /**
     * Company database setup
     * @return Void
     */
    private function companyDatabaseSetup()
    {
        $companyUrl = 'company/' . config('company_scope_id');

        $companyData = [
            'workflow_updated' => uniqueTimestamp(),
            'activity_feed_updated' => uniqueTimestamp()
        ];

        $this->update($companyUrl, $companyData);
    }

    private function update($key, $data)
    {
        $this->firebase->update($key, $data);
    }


    /**
     * Get Panding Task Count
     * @return int value of panding task count
     */
    private function getPandingTaskCount()
    {
        $pandingTaskCount = Task::pending()->assignedTo($this->user->id)->count();

        return $pandingTaskCount;
    }

    /**
     * Get Unread Message count
     * @return Int value Unread message count
     */
    private function getUnreadMessageCount()
    {

        return Message::unread($this->user->id)->count();
    }

    /**
     * Get Unread Nofification count
     * @return Int value of unread
     */
    private function getUnreadNotificatonCount()
    {

        return $this->user->notifications()->count();
    }

    /**
     * Get Unread Email count
     * @return int value of unread email
     */
    private function getUnreadEmail()
    {
        $repo = App::make(\App\Repositories\EmailsRepository::class);

        return $repo->getEmails([
            'type' => Email::UNREAD,
            'with_reply' => true,
            'users' => $this->user->id,
            'not_moved' => true,
        ])->get()->count();
    }

    /**
     * Check Restricated workflow
     * @return boolean
     */
    private function checkRestricatedWorkflow()
    {

        return SecurityCheck::RestrictedWorkflow($this->user);
    }
}
