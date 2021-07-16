<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\JobAwardedStage;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;

class JobAwardedStageController extends ApiController
{
    protected $scope;
    protected $model;

    public function __construct(Context $scope, JobAwardedStage $model)
    {
        $this->scope = $scope;
        $this->model = $model;
        parent::__construct();
    }

    /**
     * get current job awarded stage of company
     * GET /job_awarded_stage
     *
     * @return Response
     */
    public function get()
    {
        /* get active stage */
        $activeStage = $this->getJobAwardedStage();

        return ApiResponse::success([
            'data' => $activeStage,
        ]);
    }

    /**
     * set active job awarded stage of company
     * POST /job_awarded_stage
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('stage');

        $validator = Validator::make($input, ['stage' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        DB::beginTransaction();
        try {
            /* inactive current stage */
            $this->inActiveCurrentStage();

            $newStage = $this->model->create([
                'company_id' => $this->scope->id(),
                'stage' => $input['stage'],
                'active' => 1,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', [
                'attribute' => 'Job Awarded Stage'
            ]),
            'data' => $newStage,
        ]);
    }

    /**
     * get all previous job awarded stages of company
     * GET /previous_job_awarded_stages
     *
     * @return Response
     */
    public function getPreviousStages()
    {
        /* get active stage */
        $stages = $this->model->whereCompanyId($this->scope->id())
            ->whereActive(0)
            ->get();

        return ApiResponse::success([
            'data' => $stages,
        ]);
    }

    /**** Private Functions ****/
    /**
     * inactivate current job awarded stage of company
     *
     * @return
     */
    private function inActiveCurrentStage()
    {
        $activeStage = $this->getJobAwardedStage();

        if ($activeStage) {
            $activeStage->active = 0;
            $activeStage->save();
        }
    }

    /**
     * get current job awarded stage of company
     *
     * @return
     */
    private function getJobAwardedStage()
    {
        return $this->model->whereCompanyId($this->scope->id())
            ->whereActive(1)
            ->first();
    }
}
