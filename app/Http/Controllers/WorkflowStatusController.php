<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Repositories\WorkflowRepository;
use App\Services\Contexts\Context;
use App\Models\WorkflowStage;
use Request;
use App\Exceptions\InvalidDivisionException;

class WorkflowStatusController extends ApiController
{

    protected $workflowRepo;

    public function __construct(WorkflowRepository $repo, Context $scope)
    {
        $this->scope = $scope;
        $this->workflowRepo = $repo;
        parent::__construct();

        // change db connection for report
        switchDBConnection('mysql2');
    }

    public function get_stages()
    {

        $filters = Request::all();
        try{
            $userId = ine($filters, 'user_id') ? $filters['user_id'] : null;

			$stages = WorkflowStage::getStagesWithJobCountAndAmount($userId, $filters);

            return ApiResponse::success(['data' => $stages]);

        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
