<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobRepHistory extends Model
{

    protected $table = 'job_rep_history';

    protected $fillable = ['job_id', 'stage_code', 'rep_id', 'type'];

    public $timestamps = false;

    public function scopeJob($query, $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeStage($query, $stageCode)
    {
        return $query->where('stage_code', $stageCode);
    }
}
