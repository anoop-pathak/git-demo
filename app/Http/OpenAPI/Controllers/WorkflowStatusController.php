<?php

namespace App\Http\OpenAPI\Controllers;

use App\Http\Controllers\ApiController;
use App\Models\ApiResponse;
use App\Repositories\WorkflowRepository;
use App\Services\Contexts\Context;
use App\Models\WorkflowStage;
use Request;
use App\Exceptions\InvalidDivisionException;
use App\Http\OpenAPI\Transformers\WorkFlowStageTransformer;
use Sorskod\Larasponse\Larasponse;

class WorkflowStatusController extends ApiController
{

    protected $workflowRepo;
    
    protected $response;

    public function __construct(WorkflowRepository $repo, Context $scope, Larasponse $response)
    {
        $this->scope = $scope;
        $this->workflowRepo = $repo;
        $this->response = $response;

        parent::__construct();

        // change db connection for report
        switchDBConnection('mysql2');
    }

    public function get_stages()
    {

        $filters = Request::onlyLegacy('stage_code', 'user_id', 'division_ids');

        try {

            $stages = WorkflowStage::getStagesWithJobCountAndAmount($filters['user_id'] ?? '', $filters);

            return ApiResponse::success($this->response->collection($stages, new WorkFlowStageTransformer));

            return ApiResponse::success(['data' => $stages]);

        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());

        } catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
            
        }
    }
}
