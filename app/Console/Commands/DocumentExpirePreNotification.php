<?php

namespace App\Console\Commands;

use App\Models\DocumentExpirationDate;
use App\Models\Job;
use App\Models\JobMeta;
use App\Models\Notification;
use App\Models\User;
use FlySystem;
use MobileNotification;
use Carbon\Carbon;
use Firebase\FirebaseLib;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DocumentExpirePreNotification extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:document_expire_pre_notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notify to admin that document has been expired after 7 days.';

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
        $date = Carbon::now()->addDays(7)->toDateString();

        $documents = DocumentExpirationDate::whereExpireDate($date)
            ->with('company.authority')
            ->get();
        if (!$documents->count()) {
            return;
        }

        foreach ($documents as $document) {
            $this->preNotification($document);
        }
    }

    /**
     * notification 7 days before
     * @return [type] [description]
     */
    private function preNotification($document)
    {
        try {
            $this->sendNotification($document);
        } catch (\Exception $e) {
            $messageString = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info('Document Expire post notification: ' . $messageString);
        }

        return true;
    }

    /**
     * send notificaiotn
     * @param  [object] $documents [description]
     * @return [type]            [description]
     */
    private function sendNotification($document)
    {

        $company = $document->company;
        if (!$company) {
            return;
        }
        $data = [];

        $jobId = null;
        $object = $document->getObject();
        if (!$object) {
            return false;
        }
        if (in_array($document->object_type, ['estimation', 'proposal'])) {
            $jobId = $object->job_id;
            $fileName = $object->title;
            $filePath = \config('jp.BASE_PATH') . $object->file_path;
        } else {
            $jobMeta = JobMeta::whereMetaKey('resource_id')
                ->whereMetaValue($document->object_id)
                ->first();

            if ($jobMeta) {
                $jobId = $jobMeta->job_id;
            }

            $fileName = $object->name;
            $filePath = \config('resources.BASE_PATH') . $object->path;
        }
        $expireDate = Carbon::parse($document->expire_date)->format('Y-m-d');

        // notification body
        $body = [
            'object_type' => $document->object_type,
            'object_id' => $document->object_id,
            'job_id' => $jobId,
        ];
        $this->companyId = $company->id;
        // get only acitve authority user
        $users = $company->authority()->active()->get()->toArray();

        $recipients = array_column($users, 'id');
        // add customer rep in recipients..
        $job = Job::with('customer')->find($jobId);
        if ($job) {
            $customer = $job->customer;
            if ($customer->rep && ($rep = $customer->rep()->active()->first())) {
                $users[] = $rep->toArray();
                $recipients[] = $customer->rep_id;
                $users = uniqueMultidimArray($users);
            }

            $url = config('jp.site_job_url') . $customer->id . '/job/' . $job->id . '/overview';
            $jobRedirect = '<a href=' . $url . '>' . $customer->full_name . ' / ' . $job->number . '</a>';
            $emailContent = trans('response.events.email.document_expire_content.with_job.weekly', [
                'file_name' => $fileName,
                'job_redirect' => $jobRedirect,
                'date' => $expireDate
            ]);
        } else {
            $emailContent = trans('response.events.email.document_expire_content.without_job.weekly', [
                'file_name' => $fileName,
                'date' => $expireDate
            ]);
        }

        $messageString = trans('response.events.notifications.before_document_expired', [
            'file_name' => $fileName,
            'date' => $expireDate
        ]);

        $body['company_id'] = $company->id;

        // send notification..
        $this->webNoticaction($messageString, $document->object_id, $recipients, $body);

        $title = 'Document is about to expire';

        foreach ($users as $key => $user) {
            $this->sendEmail($title, $filePath, $fileName, $emailContent, $user);
        }

        MobileNotification::send($recipients, $title, 'document_expired', $messageString, $body);
    }

    /**
     * Web notification
     * @param  [string] $messageString [description]
     * @param  [int] $objectId      [description]
     * @param  [array] $recipients    [description]
     * @param  [array] $body          [description]
     * @return [response]                [description]
     */
    private function webNoticaction($messageString, $objectId, $recipients, $body)
    {
        try {
            // send notification..
            $notification = new Notification;
            $notification->subject($messageString);
            $notification->object_type = 'document_expired';
            $notification->object_id = $objectId;
            $notification->body = $body;
            $notification->deliver();
            $notification->recipients()->attach($recipients);
            foreach ($recipients as $recipient) {
                $url = 'company/' . $this->companyId . '/users/' . $recipient . '/count';
                $user = User::find($recipient);
                $data['unread_notifications_count'] = $user->notifications()->count();
                $this->firebase->update($url, $data);
            }
        } catch (\Exception $e) {
            $messageString = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info('Document Expire post web notification: ' . $messageString);
        }
        return true;
    }

    /**
     * Send Email
     * @param  [string] $title        [description]
     * @param  [url] $filePath        [description]
     * @param  [content] $emailContent[description]
     * @param  [object] $user         [description]
     * @return [response]             [description]
     */
    private function sendEmail($title, $filePath, $fileName, $emailContent, $user)
    {
        try {
            $data = [
                'first_name' => $user['first_name'],
                'content' => $emailContent,
            ];

            Mail::send('emails.users.document_expire', $data, function ($message) use ($title, $user, $filePath, $fileName) {
                $message->to($user['email'])->subject($title);
                $message->attachData(FlySystem::read($filePath), $fileName);
            });
        } catch (\Exception $e) {
            $messageString = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info('Document Expire post email notification: ' . $messageString);
        }

        return true;
    }
}
