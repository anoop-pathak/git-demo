<?php

namespace App\Services\Grid;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait JobEventsTrackableTrait
{

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (isset($model->job_id)) {
                static::updateJob($model->job_id);
            }
        });

        static::deleting(function ($model) {
            if (isset($model->job_id)) {
                static::updateJob($model->job_id);
            }
        });
    }

    private static function updateJob($jobId)
    {
        $data = [
            'last_modified_by' => \Auth::id(),
            'updated_at' => Carbon::now(),
        ];
        Job::where('id', $jobId)->update($data);
    }
}
