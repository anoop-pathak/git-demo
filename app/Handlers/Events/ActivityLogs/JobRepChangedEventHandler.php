<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\User;
use ActivityLogs;
use FlySystem;

class JobRepChangedEventHandler
{

    public function handle($event)
    {
        $job = $event->job;
        $newReps = $event->newReps;
        $oldReps = $event->oldReps;
        if (empty($newReps) && empty($oldReps)) {
            return false;
        }

        //job's customer
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $newRepsAssigned = array_diff($newReps, $oldReps);
        $repsRemoved = array_diff($oldReps, $newReps);

        if (!empty($newRepsAssigned)) {
            foreach ($newRepsAssigned as $repId) {
                $metaData = ['rep_id' => $repId];
                $displayData = $this->setDisplayData($repId);
                //maintain log for job rep assigned event..
                ActivityLogs::maintain(
                    ActivityLog::FOR_USERS,
                    ActivityLog::JOB_REP_ASSIGNED,
                    $displayData,
                    $metaData,
                    $customer->id,
                    $job->id,
                    $stageCode
                );
            }
        }

        if (!empty($repsRemoved)) {
            foreach ($repsRemoved as $repId) {
                $metaData = ['rep_id' => $repId];
                $displayData = $this->setDisplayData($repId);
                //maintain log for job rep removed event..
                ActivityLogs::maintain(
                    ActivityLog::FOR_USERS,
                    ActivityLog::JOB_REP_REMOVED,
                    $displayData,
                    $metaData,
                    $customer->id,
                    $job->id,
                    $stageCode
                );
            }
        }
    }

    private function setDisplayData($repId)
    {
        $displayData = [];
        try {
            $rep = User::find($repId);
            $displayData['rep']['id'] = $rep->id;
            $displayData['rep']['first_name'] = $rep->first_name;
            $displayData['rep']['last_name'] = $rep->last_name;
            $displayData['rep']['profile_pic'] = !empty($rep->profile->profile_pic) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $rep->profile->profile_pic) : null;
            return $displayData;
        } catch (\Exception $e) {
            return $displayData;
        }
    }
}
