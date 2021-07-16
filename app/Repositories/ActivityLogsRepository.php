<?php

namespace App\Repositories;

use App\Helpers\SecurityCheck;
use App\Models\ActivityLog;
use ActivityLogs;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class ActivityLogsRepository extends ScopedRepository
{

    /**
     * The base eloquent ActivityLog
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(ActivityLog $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }


    public function addManualActivity($subject, $content, $data = [])
    {

        // set display data..
        $displayData = [
            'subject' => $subject,
            'content' => $content,
        ];

        //set customer id..
        $customerId = ine($data, 'customer_id') ? $data['customer_id'] : null;

        //set job id
        $jobId = ine($data, 'job_id') ? $data['job_id'] : null;

        //maintain log for Customer Rep Changed event..
        $activity = ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::MANUAL_ACTIVITY,
            $displayData,
            $meta = [],
            $customerId,
            $jobId
        );

        return $activity;
    }

    public function getActivityLogs($filters = [])
    {
        $user = \Auth::user();
        $activityLogs = $this->make([
            'customer',
            'meta',
            'user.profile',
            'job',
            'job.jobMeta',
            'job.jobWorkflow',
        ]);

        $activityLogs->public()// only public activities to display..
        ->orderBy('id', 'desc');

        if (!$this->scope->has() && $user->isSuperAdmin()) {
            $activityLogs->for(ActivityLog::FOR_SUPERADMIN);
        } else {
            $activityLogs->for(ActivityLog::FOR_USERS);
        }

        $this->applyFilters($activityLogs, $filters);
        return $activityLogs;
    }

    public function getRecentActivityCount($lastId, $filters = [])
    {
        $logs = $this->getActivityLogs($filters);
        return $logs->where('id', '>', $lastId)->count();
    }

    /************************** Private function ********************************/

    private function applyFilters($query, $filters = [])
    {
        $query->division();

        if (SecurityCheck::RestrictedWorkflow()) {
            $userId = \Auth::id();
            $query->where(function($query) use($userId){
                $query->whereHas('customer', function ($query) use ($userId) {
                    $query->own($userId);
                })->orWhereNull('customer_id');
            });

            $query->where(function ($query) use ($userId) {
                $query->whereHas('job', function ($query) use ($userId) {
                    $query->own($userId);
                })->orWhereNull('job_id');
            });

            $query->whereCreatedBy($userId);
        }

        if (ine($filters, 'job_ids')) {
            $query->whereIn('job_id', (array)$filters['job_ids']);
        }

        if (ine($filters, 'user_ids')) {
            $query->whereIn('created_by', (array)$filters['user_ids']);
        }

        if (ine($filters, 'customer_ids')) {
            $query->whereIn('customer_id', (array)$filters['customer_ids']);
        }

        if (ine($filters, 'stage_code')) {
            $query->where('stage_code', $filters['stage_code']);
        }

        if (ine($filters, 'event')) {
            $query->where('event', $filters['event']);
        }

        if (ine($filters, 'start_date_time') || ine($filters, 'end_date_time')) {
            $start = ine($filters, 'start_date_time') ? $filters['start_date_time'] : null;
            $end = ine($filters, 'end_date_time') ? $filters['end_date_time'] : null;
            $query->dateRange($start, $end);
        }
    }
}
