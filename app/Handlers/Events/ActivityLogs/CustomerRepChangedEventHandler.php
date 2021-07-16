<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\User;
use FlySystem;
use ActivityLogs;

class CustomerRepChangedEventHandler
{

    public function handle($event)
    {
        $customer = $event->customer;
        $newRep = $event->newRep;
        $oldRep = $event->oldRep;
        if ($newRep != $oldRep) {
            //set meta for activity log..
            $metaData = $this->setMetaData($newRep, $oldRep);
            $displayData = $this->setDisplayData($newRep, $oldRep);
            if (empty($oldRep)) {
                $event = ActivityLog::CUSTOMER_REP_ASSIGNED;
            } else {
                $event = ActivityLog::CUSTOMER_REP_CHANGED;
            }

            $this->maintainLog($event, $displayData, $metaData, $customer->id);
        }
    }

    private function setMetaData($newRep, $oldRep = null)
    {
        $metaData = ['new_rep' => $newRep];
        if (!empty($oldRep)) {
            $metaData['old_rep'] = $oldRep;
        }
        return $metaData;
    }

    private function setDisplayData($newRep, $oldRep = null)
    {
        $displayData = [];
        try {
            $newRep = User::find($newRep);
            $displayData['new_rep']['id'] = $newRep->id;
            $displayData['new_rep']['first_name'] = $newRep->first_name;
            $displayData['new_rep']['last_name'] = $newRep->last_name;
            $displayData['new_rep']['profile_pic'] = !empty($newRep->profile->profile_pic) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $newRep->profile->profile_pic) : null;

            if (empty($oldRep)) {
                return $displayData;
            }

            $newRep = User::find($oldRep);
            $displayData['old_rep']['id'] = $newRep->id;
            $displayData['old_rep']['first_name'] = $newRep->first_name;
            $displayData['old_rep']['last_name'] = $newRep->last_name;
            $displayData['old_rep']['profile_pic'] = !empty($newRep->profile->profile_pic) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $newRep->profile->profile_pic) : null;
            return $displayData;
        } catch (\Exception $e) {
            return $displayData;
        }
    }

    private function maintainLog($event, $displayData, $metaData, $customerId)
    {

        //maintain log for Customer Rep Changed event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            $event,
            $displayData,
            $metaData,
            $customerId
        );
    }
}
