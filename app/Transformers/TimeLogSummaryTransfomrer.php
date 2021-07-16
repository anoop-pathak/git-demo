<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Carbon\Carbon;
use App\Transformers\DivisionTransformer;

class TimeLogSummaryTransfomrer extends TransformerAbstract
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
    protected $availableIncludes = [
        'division',
        'trades',
    ];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($timeLog) {
       return [
            'job_id'           => $timeLog->job_id,
            'user_id'          => $timeLog->user_id,
            'customer_name'    => $timeLog->customer_name,
            'customer_id'      => $timeLog->customer_id,
            'user_name'        => $timeLog->user_name,
            'multi_job'        => $timeLog->multi_job,
            'number'           => $timeLog->number,
            'alt_id'           => $timeLog->alt_id,
            'parent_id'        => $timeLog->parent_id,
            'total_entires'    => (int)$timeLog->total_entries,
            'date'             => Carbon::parse($timeLog->start_date_time)->toDateString(),
            'duration'         => durationFromSeconds($timeLog->duration),
            'profile_pic'      => ($timeLog->profile_pic) ? \FlySystem::publicUrl(config('jp.BASE_PATH').$timeLog->profile_pic) : null,
            'user_email' => $timeLog->email,
            'user_phone' => json_decode($timeLog->additional_phone),
		];
	}

    public function includeEntries($timeLog)
    {
        $entries = (array)$timeLog->sub_entries;
        return $this->collection($entries, function($entry) {
            return [
                'entry_id'        => $entry->id,
                'job_id'          => $entry->job_id,
                'user_id'         => $entry->user_id,
                'total_entires'   => (int)$entry->total_entires,
                'customer_name'   => $entry->customer_name,
                'user_name'       => $entry->user_name,
                'job_number'      => $entry->number,
                'alt_id'          => $entry->alt_id,
                'start_date_time' => $entry->start_date_time,
                'end_date_time'   => $entry->end_date_time,
                'profile_pic'     => ($entry->profile_pic) ? \FlySystem::publicUrl(config('jp.BASE_PATH').$entry->profile_pic) : null,
                'duration'        => durationFromSeconds($entry->duration),
            ];
        });
    }

    public function includeDivision($timeLog)
    {
        $job = $timeLog->job;
        if($job) {
            $division = $job->division;
            if($division){
                return $this->item($division, new DivisionTransformer);
            }
        }
    }

    public function includeTrades($timeLog)
    {
        $job = $timeLog->job;

        if($job) {
            $trades = $job->trades;
            if($trades) {
                return $this->collection($trades, new TradesTransformer);
            }
        }
    }
}