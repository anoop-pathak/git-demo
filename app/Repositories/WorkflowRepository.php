<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Resource;
use Firebase;
use App\Services\Resources\ResourceServices;
use App\Models\Workflow;
use App\Models\WorkflowStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\ResourcesRepository;
use Exception;

class WorkflowRepository extends AbstractRepository
{
    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $resourceService;

    function __construct(Workflow $model, ResourceServices $resourceService, ResourcesRepository $resourceRepo)
    {
        $this->model = $model;
        $this->resourceService = $resourceService;
        $this->resourceRepo = $resourceRepo;
    }


    /**
     * Setup Default Workflow
     * @var $company_id Id of the company for which to setup the workflow
     * @var $created_by id of the logged in user (Who created the subscriber)
     * @var $product_by id of the subscription product
     */

    public function setupDefault($company_id, $created_by, $product_id)
    {
        $workflow = new Workflow(compact($company_id, $created_by));
        $workflow->company_id = $company_id;
        $workflow->created_by = $created_by;
        $workflow->title = $company_id . "_workflow";
        $workflow->last_modified_by = $created_by;
        $workflow->save();


        if ($product_id == Product::PRODUCT_JOBPROGRESS) {
            $defaultStages = WorkflowStage::defaultStagesForBasic();
        } else {
            $defaultStages = WorkflowStage::defaultStages();
        }
        foreach ($defaultStages as $defaultStage) {
            $defaultStage['code'] = mt_rand();
            $stage = new WorkflowStage($defaultStage);
            $workflow->stages()->save($stage);
        }

        $this->createResources($workflow, $company_id);
        return $workflow;
    }

    public function getActiveWorkflow($company_id = null)
    {
        if (!$company_id) {
            $company_id = config('company_scope_id');
        }

        $workflow = $this->model->where(['company_id' => $company_id])
            ->orderBy('updated_at', 'desc')
            ->with([
                'stages' => function ($query) {
                    $query->orderBy('position', 'asc')
                        ->with('steps');
                }
            ])
            ->first();

        return $workflow;
    }

    public function getStage($stageCode, $companyId = null)
    {
        if (!$companyId) {
            $company_id = config('company_scope_id');
        }

        $workflow = $this->model->where(['company_id' => $company_id])->first();
        $stage =  WorkflowStage::where('code', $stageCode)
            ->where('workflow_id', $workflow->id)
            ->first();

        return $stage;
    }

    /**
     * Get Active workflow stages
     * @param  int $company_id | Compnay id (default is current company id)
     * @return Query Builder
     */
    public function getActiveWorkflowStages($company_id = null)
    {
        if (!$company_id) {
            $company_id = config('company_scope_id');
        }

        $workflow = $this->model->where(['company_id' => $company_id])
            ->orderBy('updated_at', 'desc')
            ->first();

        return $workflow->stages();
    }

    public function create($attributes, $companyId)
    {
        DB::beginTransaction();
        try{

        	$title = $companyId . '_workflow';
        	$companyRoot = Resource::companyRoot($companyId);
        	$dir = $this->resourceRepo->getDirWithName($title, $companyRoot->id);
        	if(!$dir) {
				$dir = $this->resourceService->createDir($title,$companyRoot->id);
        	}
        	$resourceId = $dir->id;
        	$oldWorkFlow = Workflow::where('company_id', $companyId)->orderBy('id', 'desc')->first();
        	$oldWorkFlowId = ($oldWorkFlow) ? $oldWorkFlow->id : null;
            $workflow = Workflow::addWorkflow($companyId, $title, $resourceId);

            if(isset($attributes['stages'])){
            	foreach ($attributes['stages'] as $key => $value) {
            		if(!isset($value['code']) || empty($value['code'])) {
            			$value['code'] = Carbon::now()->timestamp.mt_rand(); //stage code uid
            		}
            		$steps = isset($value['steps']) ? $value['steps'] : null;
					$value['resource_id'] = null;
					if($oldWorkFlowId) {
						$workflowStage = WorkflowStage::where('code', $value['code'])
							->where('workflow_id', $oldWorkFlowId)
							->first();
						$value['resource_id'] = ($workflowStage) ? $workflowStage->resource_id : null;
					}

            		$workflowStage = WorkflowStage::addStage($value,$workflow->id,$steps);
				}
            }
            $this->updateStageResouce($workflow);
            // $this->deleteResourcesForDeletedStages($workflow,$companyId);
            Firebase::updateWorkflow();
		}catch(Exception $e){
            DB::rollback();
            throw $e->getMessage();
        }
        DB::commit();
    }

    /****************** Private section ********************/

    private function createResources(Workflow $workflow, $companyId)
    {

        $rootId = null;

        if (!empty($workflow->resource_id)) {
            $rootId = $workflow->resource_id;
        }

        if (empty($rootId)) {
            $companyRoot = Resource::companyRoot($companyId);
            if (!$companyRoot) {
                return false;
            }
            $workflowResource = $this->resourceService->createDir($workflow->title, $companyRoot->id);
            $workflow->resource_id = $workflowResource->id;
            $workflow->save();
            $rootId = $workflowResource->id;
        }

        foreach ($workflow->stages as $stage) {
            if (empty($stage->resource_id)) {
                $resourceMeta = [
                    'key' => 'stage_code',
                    'value' => $stage->code
                ];
                $stageResource = $this->resourceService->createDir($stage->code, $rootId, true, $stage->name, $resourceMeta);
                $stage->resource_id = $stageResource->id;
                $stage->save();
            }
        }
    }

    private function deleteResourcesForDeletedStages(Workflow $currentWorkFlow, $companyId)
    {
        $prevResourcesList = [];
        $currentResourcesList = $currentWorkFlow->stages()->pluck('resource_id')->toArray();
        $prevWorkFlow = Workflow::where('company_id', $companyId)
            ->where('id', '<', $currentWorkFlow->id)
            ->orderBy('id', 'desc')
            ->first();
        if ($prevWorkFlow) {
            $prevResourcesList = $prevWorkFlow->stages()->pluck('resource_id')->toArray();
        }

        $deletedResourceIds = array_filter(array_diff($prevResourcesList, $currentResourcesList));
        foreach ($deletedResourceIds as $resourceId) {
            $this->resourceService->removeDir($resourceId, true, false);
        }
    }

    private function updateStageResouce(Workflow $workflow) {
		foreach ($workflow->stages as $stage) {
			if($stage->resource_id) continue;

			$resourceMeta = [
				'key'	=> 	'stage_code',
				'value'	=> 	$stage->code,
			];

			$stageResource = $this->resourceService->createDir($stage->code, $workflow->resource_id, true, $stage->name, $resourceMeta);
			$stage->resource_id = $stageResource->id;
			$stage->save();
		}
	}

    /**
     * Get workflow by id
     * @param  [int] $id [description]
     * @return workflow
     */
    public function getWorkFlow($id)
    {
        $workflow = $this->model->whereId($id)
            ->orderBy('updated_at', 'desc')
            ->with([
                'stages' => function ($query) {
                    $query->orderBy('position', 'asc')
                        ->with('steps');
                }
            ])
            ->first();

        return $workflow;
    }
}
