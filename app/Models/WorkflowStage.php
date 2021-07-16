<?php

namespace App\Models;

use App\Helpers\SecurityCheck;
use App\Models\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\JobRepository;

class WorkflowStage extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'workflow_stages';

    protected $fillable = ['code', 'workflow_id', 'name', 'locked', 'position', 'color', 'resource_id', 'options'];

    protected $hidden = ['created_at', 'updated_at'];

    protected static $createRules = [
        'name' => 'required',
        'workflow_id' => 'required',
        'locked' => 'required',
        'position' => 'required',
        'color' => 'required',
    ];

    public static function getCreateRules()
    {
        return self::$createRules;
    }

    /**************** Workflow Stages Relationship **********************/

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('position', 'asc');
    }

    /**
     * Jobs Count in stage.
     * @param int| userId to find job counts of particular user..
     * @return int | counts
     */
    public function jobsCount($userId = null, $filters = [])
    {
        $jobWorkflow = $this->hasMany(JobWorkflow::class, 'current_stage', 'code');

        if (SecurityCheck::RestrictedWorkflow()) {
            $userId = Auth::id();
        }
        $jobWorkflow->whereHas('job', function ($query) use ($userId, $filters) {
            //job count for particular user..
            if ($userId) {
                $query->own($userId);
            }

            if (ine($filters, 'division_ids')) {
                $query->whereIn('division_id', (array)$filters['division_ids']);
            }

            $query->excludeProjects()
                ->excludeLostJobs()
                ->withoutArchived();
        });

        return $jobWorkflow->count();
    }

    /**
     * Get workflow stages with job count and amout
     * @param  int $userId user id
     * @param  array $filters array of filters
     * @return stages
     */
    protected function getStagesWithJobCountAndAmount($userId = null, $filters = [], $includeTotalJobAmount = true)
    {
        $companyId = getScopeId();
        if (empty($companyId)) {
            return [];
        }

        if (SecurityCheck::RestrictedWorkflow()) {
            $userId = Auth::id();
        }

        $joins = ['job_workflow'];
        $jobRepo = app(JobRepository::class);
        $jobQuery = $jobRepo->getJobsQueryBuilder($filters, $joins)->distinct();

        if ($userId) {
            $jobQuery->own();
            $jobQuery->users((array)$userId);
        }

        $jobQuery->select('jobs.id', 'job_workflow.current_stage');


        $wpJobQuery = clone $jobQuery;
        $wpJobs = $wpJobQuery->where('wp_job_seen', false)
            ->where('wp_job', true)
            ->groupBy('job_workflow.current_stage')
            ->selectRaw('job_workflow.current_stage, Count(*) as job_count')
            ->pluck('job_count', 'current_stage')->toArray();

        $jobQuery = generateQueryWithBindings($jobQuery);

        $stages = self::leftJoin(DB::raw("({$jobQuery}) AS jobs"), 'jobs.current_stage', '=', 'workflow_stages.code')
            ->whereRaw("workflow_stages.workflow_id = (select max(id) from workflow where company_id={$companyId})")
            ->selectRaw('workflow_stages.*, COUNT(jobs.id) as jobs_count')
            ->orderBy('workflow_stages.id', 'asc')
            ->groupBy('workflow_stages.code');

        if (ine($filters, 'stage_code')) {
            $stages->where('workflow_stages.code', $filters['stage_code']);
        }

        // check financial permissions
        if ($includeTotalJobAmount && SecurityCheck::hasPermission('manage_financial')) {
            $companyId = getScopeId();
            $stages->leftJoin(DB::raw("(SELECT job_financial_calculations.job_id, job_financial_calculations.total_amount FROM  job_financial_calculations WHERE multi_job = 0 AND company_id = {$companyId}) AS job_financial_calculations"), 'jobs.id', '=', 'job_financial_calculations.job_id');
            $stages->addSelect(DB::raw('IFNULL(SUM(job_financial_calculations.total_amount), 0) as total_job_amount'));
        }

        $stages = $stages->get();

        $user = Auth::user();

        if(!$user->isOpenAPIUser()) {
            // count wp unseen jobs
            foreach ($stages as $key => $stage) {
                $stages[$key]['wp_unseen_count'] = isset($wpJobs[$stage->code]) ? $wpJobs[$stage->code] : 0;
            }
        }

        return $stages;
    }

    public function getOptionsAttribute($value)
    {
        return json_decode($value);
    }

    public function setOptionsAttribute($value)
    {
        $value = (array)$value;
        $this->attributes['options'] = json_encode($value);
    }

    /********************************* Functions *********************************/

    /**
     * @TODO 1.)Create constants for locked and position
     * 2.) Move User Creation process to Models
     */
    public static function defaultStages()
    {
        $workflowStageData = [
            0 => [
                'name' => 'Lead',
                'locked' => 1,
                'position' => 1,
                'color' => 'cl-red'
            ],
            1 => [
                'name' => 'Estimate',
                'locked' => 0,
                'position' => 2,
                'color' => 'cl-orange'
            ],
            2 => [
                'name' => 'Proposal',
                'locked' => 0,
                'position' => 3,
                'color' => 'cl-yellow'
            ],
            3 => [
                'name' => 'Follow Up',
                'locked' => 0,
                'position' => 4,
                'color' => 'cl-lime'
            ],
            4 => [
                'name' => 'Contract',
                'locked' => 0,
                'position' => 5,
                'color' => 'cl-blue'
            ],
            5 => [
                'name' => 'Work',
                'locked' => 0,
                'position' => 6,
                'color' => 'cl-purple'
            ],
            6 => [
                'name' => 'Paid',
                'locked' => 1,
                'position' => 7,
                'color' => 'cl-skyblue'
            ]
        ];
        return $workflowStageData;
    }

    public static function defaultStagesForBasic()
    {
        $defaultStages = [
            0 => [
                'name' => 'Start',
                'locked' => 1,
                'position' => 1,
                'color' => 'cl-red'
            ],
            1 => [
                'name' => 'End',
                'locked' => 1,
                'position' => 2,
                'color' => 'cl-orange'
            ]
        ];
        return $defaultStages;
    }

    /**
     * get workflow stages
     *
     * @ret workflow stages
     */
    public static function getWorkflowStages($workflow_id)
    {

        $workflowStages = self::where(['workflow_id' => $workflow_id])->orderBy('position', 'asc')->get();
        return $workflowStages;
    }

    public static function addStage($data, $workflow_id, $steps)
    {
        $stage = new static();
        $stage->name = $data['name'];
        $stage->code = $data['code'];
        $stage->locked = $data['locked'];
        $stage->position = $data['position'];
        $stage->color = $data['color'];
        $stage->resource_id = isset($data['resource_id']) ? $data['resource_id'] : 0;
        $stage->options = isset($data['options']) ? $data['options'] : null;
        $stage->workflow_id = $workflow_id;
        $stage->send_customer_email = isset($data['send_customer_email']) ? $data['send_customer_email'] : false;
        $stage->send_push_notification = isset($data['send_push_notification']) ? $data['send_push_notification'] : false;
        $stage->create_tasks = isset($data['create_tasks']) ? $data['create_tasks'] : false;
        $stage->save();

        if (!empty($steps)) {
            foreach ($steps as $key => $value) {
                $step = new WorkflowStep($value);

                if (!empty($value['controls'])) {
                    foreach ($value['controls'] as $controlKey => $controlValue) {
                        $value['controls'][$controlKey]['name'] = $controlValue['code'] . '_' . rand();
                    }
                }
                $step['options'] = (!empty($value['controls'])) ? json_encode($value['controls']) : null;

                unset($step['controls']);
                $stage->steps()->save($step);
                $step->save();
            }
        }

        return $stage;
    }
}
