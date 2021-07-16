<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobRepHistory;

class JobRepTrack
{

    /**
     * Track Job Rep in each stage
     * @param  Job $job | Job Instance
     * @param  string $type | Estimator or rep
     * @param  string $stageCode | Stage Code
     * @return void
     */
    static function track(Job $job, $type = Job::REP, $stageCode = null)
    {

        $jobId = $job->id;

        if (empty($stageCode)) {
            $jobWorkflow = $job->jobWorkflow;
            $stageCode = $jobWorkflow->current_stage;
        }

        if ($type == Job::ESTIMATOR) {
            $reps = $job->estimators()->pluck('rep_id')->toArray();
        } else {
            $reps = $job->reps()->pluck('rep_id')->toArray();
        }
        if (empty($reps)) {
            return;
        }
        $trackedReps = JobRepHistory::job($jobId)->stage($stageCode)->whereType($type)->pluck('rep_id')->toArray();
        $newReps = array_diff($reps, $trackedReps);
        if (empty($newReps)) {
            return;
        }

        $data = [];
        foreach ($newReps as $key => $repId) {
            $data[$key] = [
                'job_id' => $jobId,
                'stage_code' => $stageCode,
                'rep_id' => $repId,
                'type' => $type
            ];
        }
        JobRepHistory::insert($data);
    }
}
