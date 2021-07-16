<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobFinancialCalculation;
use ActivityLogs;
use Illuminate\Support\Facades\Queue;
use Firebase;
use App\Services\Solr\Solr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Request;
use Illuminate\Support\Facades\Mail;
use App\Models\TimeLog;

class JobObserver
{

    // here is the listener
    public function subscribe($event)
    {
        // $event->listen('eloquent.saving: Job', 'App\Observers\JobObserver@saving');
        // $event->listen('eloquent.creating: Job', 'App\Observers\JobObserver@creating');
        // $event->listen('eloquent.saved: Job', 'App\Observers\JobObserver@saved');
        // $event->listen('eloquent.updating: Job', 'App\Observers\JobObserver@updating');
        // $event->listen('eloquent.updated: Job', 'App\Observers\JobObserver@updated');
        // $event->listen('eloquent.deleting: Job', 'App\Observers\JobObserver@deleting');
        // $event->listen('eloquent.deleted: Job', 'App\Observers\JobObserver@deleted');
        // $event->listen('eloquent.restored: Job', 'App\Observers\JobObserver@restored');
        // $event->listen('eloquent.created: Job', 'App\Observers\JobObserver@created');
    }

    // before save ..
    public function saving($job)
    {
        if ($job->duration) {
            $job->duration_in_seconds = dhmToSeconds($job->duration);
        } else {
            $job->duration = null;
            $job->duration_in_seconds = null;
        }
    }

    // before create ..
    public function creating($job)
    {
        $job->created_date = $job->created_at;
        $job->created_by   = Auth::id();
        $job->last_modified_by = Auth::id();
    }

    public function created($job)
    {
        $customer = $job->customer;
         //maintain activity log.
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_CREATED,
            $displayData = array(),
            $metaData = array(),
            $customer->id,
            $job->id
        );

        JobFinancialCalculation::addJobFinancials($job);
    }

    // saved
    public function saved($job)
    {
    }

    // before update ..
    public function updating($job)
    {
        $job->last_modified_by = Auth::id();
    }

    // after update
    public function updated($job)
    {
        //maintain activity log.
        $job->updateJobActivityLog();
    }

    // before delete
    public function deleting($job)
    {
        // Delete job workflow..
        $job->jobWorkflow()->delete();

        if ($job->isMultiJob()) {
            foreach ($job->projects as $project) {
                $project->delete();
            }
        }

        // save deleted by and delete note
        Job::where('id', $job->id)->update([
            'deleted_by' => Auth::user()->id,
            'delete_note' => Request::get('note')
        ]);
    }

    // after delete
    public function deleted($job)
    {
        $displayData = $job->toArray();

        //maintain activity log.
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_DELETED,
            $displayData,
            $metaData = [],
            $job->customer_id,
            $job->id
        );
        $data = [
            'current_user_id' => Auth::id(),
            'job_id'          => $job->id,
            'customer_id'     => $job->customer_id
        ];
        Queue::push('\App\Handlers\Events\JobQueueHandler@updateWorkflow', $data);

        if (!$job->isProject()) {
            Queue::push('\App\Handlers\Events\JobQueueHandler@jobDeleteSolr', $data);
        }

        //Calculation updated for multi job
        if ($job->isProject()) {
            $parentJob = $job->parentJob;
            JobFinancialCalculation::calculateSumForMultiJob($parentJob);
        }

        // send notification to owner
        Queue::push('\App\Handlers\Events\JobQueueHandler@sendMailToAdmin', $data);

        //CancelDripCampaign
        App::make('\App\Services\DripCampaigns\DripCampaignService')->cancelJobCampaign($job->id);

        if(!$job->isProject()) {
            $clockInEntiries = $timeLogs = TimeLog::where('job_id', $job->id)
                ->whereNull('end_date_time')
                ->count();

            if($clockInEntiries) {
                Queue::push('\App\Handlers\Events\DeletedJobsTimeLogQueueHandler', $data);
            }
        }
    }

    /**
     * Job Restore event
     * @TODO restore projects
     * @param  [type] $job [description]
     * @return [type]      [description]
     */
    public function restored($job)
    {
        // Restore job workflow
        // Restore job workflow
        $customer = $job->customer()->onlyTrashed()->first();

        if($customer){
           $customer->restore();
        }
        $job->jobWorkflow()->restore();

        $data = [
            'current_user_id' => Auth::id(),
            'job_id'          => $job->id,
        ];

        Queue::push('\App\Handlers\Events\JobQueueHandler@updateWorkflow', $data);

        if(!$job->isProject()) {
            Queue::push('\App\Handlers\Events\JobQueueHandler@jobIndexSolr', $data);
        }

        //multi job restore if project restore.
        if($job->isProject()) {
            $parent = $job->parentJob()->onlyTrashed()->first();

            if($parent){
                $parent->restore();
                $parent->jobWorkflow()->restore();
            }
        }
        $displayData['title'] = 'Job Restored';

        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_RESTORED,
            $displayData,
            $metaData = array(),
            $job->customer_id,
            $job->id
        );
    }

    private function sendMailToAdmin($job)
    {
        if ($job->isProject()) {
            $subject = 'Project Deleted.';
            $label = 'Project';
        } else {
            $subject = 'Job Deleted.';
            $label = 'Job';
        }

        $company = Company::findOrFail(getScopeId());
        $user = Auth::user();
        $customer = $job->customer;
        $trades = $job->trades->pluck('name')->toArray();
        $recipient = $company->subscriber;
        $workTypes = $job->work_types->pluck('name')->toArray();

        $body = [
            'job' => $job,
            'trades' => implode(',', $trades),
            'customer_full_name' => $customer->full_name,
            'full_name' => $recipient->full_name,
            'deleted_by' => $user->full_name,
            'job_label' => $label,
            'work_types' => implode(',', $workTypes),
        ];

        Mail::send('emails.job-delete-notification', $body, function ($message) use ($subject, $recipient) {
            $message->to($recipient->email)->subject($subject);
        });
    }
}
