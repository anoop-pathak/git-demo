<?php

namespace App\Services\Wdfbdgforkflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Workflow extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'workflow';

    protected $fillable = ['company_id', 'title', 'last_modified_by', 'created_by', 'resource_id'];

    protected static $createRules = [
        'company_id' => 'required',
        'title' => 'required',
        'created_by' => 'required',
        'last_modified_by' => 'required',
    ];

    public static function getCreateRules()
    {
        return self::$createRules;
    }

    /****************************** Workflow Relationship **********************************/

    public function stages()
    {
        return $this->hasMany(\App\Models\WorkflowStage::class)->orderBy('position', 'asc');
    }

    /**************************** Functions *******************************/


    public static function addWorkflow($company_id, $name, $resourceId = null)
    {

        // get current user
        $currentUser = \Auth::user();

        $workflowData = [
            'company_id' => $company_id,
            'title' => $name,
            'created_by' => isset($currentUser->id) ? $currentUser->id : 0,
            'last_modified_by' => isset($currentUser->id) ? $currentUser->id : 0,
            'resource_id' => $resourceId
        ];

        try {
            $workflow = self::create($workflowData);
        } catch (\Exception $e) {
            throw $e;
        }

        return $workflow;
    }
}
