<?php

namespace App\Http\Middleware;

use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\WorkflowStage;
use App\Models\JobWorkflowHistory;
use Closure;
use SecurityCheck;

class ManageFullJobWorkflow
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $input = $request->all();

        if(!(ine($input, 'job_id') && ine($input, 'stage'))
            || (!$request->user()->isStandardUser())  ) {
                return null;
        }

        if(in_array('manage_full_job_workflow', $request->user()->allowedPermissions())) {
            return null;
        }

        if(!config('awarded_stage') || SecurityCheck::hasPermission('manage_full_job_workflow')) {
            return null;
        }

        $stageCode = $input['stage'];
        $awardStageCode = config('awarded_stage');

        $job = Job::find($input['job_id']);

        if(!$job) {
            return null;
        }

        $jobWorkflowId = $job->workflow_id;

        $newStage = WorkflowStage::where('workflow_id', $jobWorkflowId)
            ->where('code', $stageCode)->select('position')->first();

        $awardedStage = WorkflowStage::where('workflow_id', $jobWorkflowId)
            ->where('code', $awardStageCode)->select('position')->first();

        if ($newStage->position >= $awardedStage->position) {

            return ApiResponse::errorForbidden();
        }

        $isAwardedStage = JobWorkflowHistory::where('job_id', $input['job_id'])->where('stage', $awardStageCode)->exists();

        if($isAwardedStage) {

            return ApiResponse::errorForbidden();
        }

        return $next($request);
    }
}