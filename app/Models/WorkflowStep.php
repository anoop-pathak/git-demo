<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'workflow_steps';

    protected $fillable = ['workflow_stage_id', 'name', 'code', 'required', 'action_step', 'options', 'position'];

    protected static $createRules = [
        'name' => 'required',
        'code' => 'required',
        'workflow_stage_id' => 'required',
        'position' => 'required',
    ];

    public static function getCreateRules()
    {
        return self::$createRules;
    }

    /********************************** Workflow Steps Relationship ***************************/

    public function workflow()
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    public function custom_step()
    {
        return $this->hasMany(JobCustomStep::class);
    }

    /********************************** Functions ***********************************************/

    public static function addStep($step, $stage_id)
    {
        foreach ($steps as $key => $value) {
            $step = null;

            $step = [
                'workflow_stage_id' => $stage_id,
                'name' => $value['name'],
                'code' => $value['code'],
                'required' => $value['required'],
                'action_step' => $value['action_step'],
                'options' => $value['options'],
                'position' => $value['position'],
            ];

            $workflowStep = self::create($step);
        }
    }
}
