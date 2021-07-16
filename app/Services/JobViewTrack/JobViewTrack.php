<?php

namespace App\Services\JobViewTrack;

use App\Models\Job;
use App\Models\JobViewHistory;
use Illuminate\Support\Facades\Auth;

class JobViewTrack
{

    public function track($jobId)
    {
        try {
            if ($this->isSameTrackAsPreviouse($jobId)) {
                return false;
            }
            $track = new JobViewHistory([
                'user_id' => Auth::id(),
                'job_id' => $jobId
            ]);
            $track->save();
            if (!$this->isTrackLimitExceeded()) {
                return true;
            }
            $this->deleteFirst();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function getJobs()
    {

        $jobs = Job::with('jobWorkflow.job')
            ->own()
            ->division()
            ->join('job_view_history', function ($join) {
                $join->on('job_view_history.job_id', '=', 'jobs.id')
                    ->where('user_id', '=', Auth::id());
            })
            ->join('customers', 'customers.id', '=', 'jobs.customer_id')
            ->withoutArchived()
            ->selectRaw('jobs.*')
            ->groupBy('jobs.id')
            ->orderByRaw('MAX(job_view_history.id) desc');

        return $jobs;
    }

    /***************** Private Section *******************/
    private function isSameTrackAsPreviouse($jobId)
    {
        $track = JobViewHistory::where('user_id', Auth::id())->orderBy('id', 'desc')->first();
        if (!$track) {
            return false;
        }
        if ($track->job_id == $jobId) {
            return true;
        }
        return false;
    }

    private function isTrackLimitExceeded()
    {
        $trackCounts = JobViewHistory::where('user_id', Auth::id())->count();
        if ($trackCounts > 10) {
            return true;
        }
        return false;
    }

    private function deleteFirst()
    {
        $firstTack = JobViewHistory::where('user_id', Auth::id())
            ->orderBy('id', 'asc')
            ->first();
        $firstTack->delete();
    }
}
