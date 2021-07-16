<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class JobCustomStep extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'job_custom_step';

    protected $fillable = ['job_id', 'step_id', 'data', 'created_at', 'updated_at'];

    /******************************* Relationships **********************************/

    public function step()
    {
        return $this->belongsTo(\App\Models\WorkflowStep::class);
    }

    public function job()
    {
        return $this->belongsTo(\App\Job::class);
    }

    /********************************** Functions ***********************************/

    public static function save_custom($data)
    {
        $data['data'] = (!empty($data['controls'])) ? json_encode($data['controls']) : null;
        unset($data['controls']);
        $custom_step = self::create($data);
        return $custom_step;
    }
}
