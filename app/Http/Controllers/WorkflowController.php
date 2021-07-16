<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\LibraryStep;
use App\Repositories\WorkflowRepository;
use App\Services\Contexts\Context;
use App\Models\WorkflowStage;
use App\Transformers\WorkflowTypicalTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class WorkflowController extends ApiController
{

    

    protected $transformer;

    /**
     * App\Repositories\WorkflowRepository;
     */
    protected $repo;
    protected $scope;

    public function __construct(WorkflowTypicalTransformer $transformer, WorkflowRepository $repo, Context $scope)
    {
        $this->transformer = $transformer;
        $this->repo = $repo;
        $this->scope = $scope;
        parent::__construct();

        $this->middleware('company_scope.ensure', ['only' => ['show']]);
    }

    /**
     * Display all active library steps.
     * GET /library_steps
     *
     * @return Response
     */
    public function getLibrarySteps()
    {

        $library_steps = LibraryStep::getActiveLibrarySteps();

        if (!empty($library_steps)) {
            return ApiResponse::success([
                'library_steps' => $this->transformer->LibraryStepTransform($library_steps)
            ]);
        }
        return ApiResponse::errorNotFound(\Lang::get('response.error.not_found', ['attribute' => 'Library Steps']));
    }

    /**
     * get workflow.
     * GET
     *
     * @return Response
     */
    public function show()
    {

        $company_id = $this->scope->id();
        $workflow = $this->repo->getActiveWorkflow($company_id);

        if (!empty($workflow)) {
            return ApiResponse::success([
                'workflow' => $this->transformer->transform($workflow)
            ]);
        }
        return ApiResponse::errorNotFound(\Lang::get('response.error.invalid', ['attribute' => 'Workflow']));
    }

    /**
     * Update Workflow
     * POST /update
     *
     * @return Response
     */
    public function update()
    {
        $input = Request::all();
        if (!isset($input['workflow']) || empty($input['workflow'])) {
            return ApiResponse::errorNotFound(\Lang::get('response.error.invalid', ['attribute' => 'workflow']));
        }

        $workflow = $this->executeCommand('\App\Commands\WorkflowCommand', $input['workflow']);

        return ApiResponse::json([
            'messages' => Lang::get('response.success.updated', ['attribute' => 'Workflow']),
            'status_code' => 500
        ]);
    }

    /**
     * Display all custom controls.
     * GET /custom_controls
     *
     * @return Response
     */
    public function getCustomControls()
    {

        $controls = \config('custom-controls');

        return ApiResponse::success([
            'controls' => $controls
        ]);
    }

    /**
     * Sale Automation settings update (for Workflow stages)
     * GET /workflow/sale_automation
     *
     * @return [type] [description]
     */
    public function saleAutomationSettings()
    {
        $input = Request::all();

        $validator = Validator::make($input, [
            'stage_code' => 'required',
            'send_customer_email' => 'boolean',
            'send_push_notification' => 'boolean',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $stage = WorkflowStage::whereCode($input['stage_code'])->firstOrFail();

        try {
            unset($input['stage_code']);

            // update for all stages with this stage code..
            WorkflowStage::whereCode($stage->code)->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Workflow stage settings']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
