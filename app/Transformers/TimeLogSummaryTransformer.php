<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class TimeLogSummaryTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['entries'];

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
            'job_id' => $timeLog->job_id,
            'user_id' => $timeLog->user_id,
            'customer_name' => $timeLog->customer_name,
            'user_name' => $timeLog->user_name,
            'number' => $timeLog->number,
            'alt_id' => $timeLog->alt_id,
            'total_entires' => (int)$timeLog->total_entries,
            'duration' => durationFromSeconds($timeLog->duration),
            'profile_pic' => ($timeLog->profile_pic) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $timeLog->profile_pic) : null,
        ];
    }

    public function includeEntries($timeLog)
    {
        $entries = $timeLog->sub_entries;

        return $this->Collection($entries, function ($entry) {

            return [
                'entry_id' => $entry->id,
                'job_id' => $entry->job_id,
                'user_id' => $entry->user_id,
                'total_entires' => (int)$entry->total_entires,
                'customer_name' => $entry->customer_name,
                'user_name' => $entry->user_name,
                'job_number' => $entry->number,
                'alt_id' => $entry->alt_id,
                'start_date_time' => $entry->start_date_time,
                'end_date_time' => $entry->end_date_time,
                'profile_pic' => ($entry->profile_pic) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $entry->profile_pic) : null,
                'duration' => durationFromSeconds($entry->duration),
            ];
        });
    }
}
