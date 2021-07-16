<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class TimeLogJobListingTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['user_entries'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($timeLog)
    {

        return [
            'id' => $timeLog->id,
            'first_name' => $timeLog->first_name,
            'last_name' => $timeLog->last_name,
            'multl_job' => $timeLog->multi_job,
            'parent_id' => $timeLog->parent_id,
            'is_commercial' => $timeLog->is_commercial,
            'job_id' => $timeLog->job_id,
            'full_name' => $timeLog->first_name . ' ' . $timeLog->last_name,
            'full_name_mobile' => $timeLog->first_name . ' ' . $timeLog->last_name,
            'job_number' => $timeLog->number,
            'alt_id' => $timeLog->alt_id,
            'duration' => durationFromSeconds($timeLog->duration),
        ];
    }

    public function includeUserEntries($timeLog)
    {
        $userEntries = $timeLog->userEntries;

        return $this->Collection($userEntries, function ($userEntry) {

            return [
                'entry_id' => $userEntry->id,
                'user_id' => $userEntry->user_id,
                'first_name' => $userEntry->first_name,
                'last_name' => $userEntry->last_name,
                'job_id' => $userEntry->job_id,
                'start_date_time' => $userEntry->start_date_time,
                'end_date_time' => $userEntry->end_date_time,
                'duration' => durationFromSeconds($userEntry->duration),
                'total_entries' => (int)$userEntry->total_entry_count,
                'profile_pic' => (!$userEntry->profile_pic) ? null : FlySystem::publicUrl(config('jp.BASE_PATH') . $userEntry->profile_pic),
            ];
        });
    }
}
