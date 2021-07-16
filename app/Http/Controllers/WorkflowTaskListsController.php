<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\WorkflowTaskList;
use App\Repositories\WorkflowTaskListRepository;
use App\Models\WorkflowStage;
use App\Transformers\WorkflowTaskListTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class WorkflowTaskListsController extends ApiController
{

    public function __construct(Larasponse $response, WorkflowTaskListRepository $repo)
    {
        $this->response = $response;
        $this->repo = $repo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /workflow/task_list
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $workflowTasks = $this->repo->getWorkflowTasks($input);

        $transformer = new WorkflowTaskListTransformer;

        // include tasks if job_id is available
        if (ine($input, 'job_id')) {
            $transformer->setDefaultIncludes(['tasks', 'stage', 'notify_users']);
        }

        $limit = isset($input['limit']) ? $input['limit'] : \config('jp.pagination_limit');
        if (!$limit) {
            return ApiResponse::success($this->response->collection($workflowTasks->get(), $transformer));
        }

        $workflowTasks = $workflowTasks->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($workflowTasks, $transformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /workflow/task_list
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('stage_code', 'title', 'notes', 'participants', 'notify_users', 'assign_to_setting', 'notify_user_setting', 'reminder_type', 'reminder_frequency', 'is_due_date_reminder', 'locked', 'is_high_priority_task', 'message_notification', 'email_notification');

        $validator = Validator::make($input, WorkflowTaskList::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if(ine($input, 'is_due_date_reminder') && $input['reminder_type'] == 'hour') {

			return ApiResponse::errorGeneral('Selected duration is not valid for this type of reminder.');
		}

		if(ine($input, 'stage_code')) {
			$stage = WorkflowStage::whereCode($input['stage_code'])->firstOrFail();
		}

        try {
            $workflowTask = $this->repo->createWorkflowTask(
                $input['stage_code'],
                $input['title'],
                $input
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Workflow Task']),
                'data' => $this->response->item($workflowTask, new WorkflowTaskListTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update a created resource in storage.
     * PUT /workflow/task_list/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('stage_code', 'title', 'notes', 'participants','assign_to_setting', 'notify_user_setting', 'reminder_type', 'reminder_frequency', 'is_due_date_reminder', 'locked','is_high_priority_task', 'message_notification', 'email_notification');

        if (Request::has('notify_users')) {
            $input['notify_users'] = Request::get('notify_users');
        }

        $validator = Validator::make($input , WorkflowTaskList::updateRules());

		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

		$appVersion = Request::header('app-version');
		if(config('is_mobile') && version_compare($appVersion, '2.6.9', '<')){
			unset($input['is_high_priority_task']);
		}

		if(ine($input, 'is_due_date_reminder') && $input['reminder_type'] == 'hour') {

			return ApiResponse::errorGeneral('Selected duration is not valid for this type of reminder.');
		}

        $workflowTask = $this->repo->getById($id);

        try {
            $workflowTask = $this->repo->updateWorkflowTask(
                $input['title'],
                $input,
                $workflowTask
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Workflow Task']),
                'data' => $this->response->item($workflowTask, new WorkflowTaskListTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Delete the specified resource.
     * Delete /workflow/task_list/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $workflowTask = $this->repo->getById($id);

        DB::beginTransaction();
        try {
            $this->repo->delete($workflowTask);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Workflow Task'])
        ]);
    }

    /**
     * Task count workflow stage wise.
     * Get /workflow/task_stage_wise_count
     *
     * @return Response
     */
    public function stageWiseCount()
    {
        // get active workflow's stage query builder..
        $workflowRepo = App::make(\App\Repositories\WorkflowRepository::class);
        $stages = $workflowRepo->getActiveWorkflowStages();

        // get count data stage wise..
        $data = $stages->leftJoin(
            DB::raw('(select COUNT(id) as count, stage_code from workflow_task_lists GROUP BY stage_code) as workflow_task_lists'),
            'workflow_stages.code',
            '=',
            'workflow_task_lists.stage_code'
        )->select(
            'workflow_stages.code',
            'workflow_stages.name',
            'workflow_stages.color',
            DB::raw('IFNULL(workflow_task_lists.count, 0) as count')
        )->get();

        return ApiResponse::success(['data' => $data]);
    }
}
