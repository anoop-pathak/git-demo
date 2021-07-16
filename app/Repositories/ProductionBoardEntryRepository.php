<?php

namespace App\Repositories;

use App\Models\ProductionBoardEntry;
use App\Services\Contexts\Context;

class ProductionBoardEntryRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(ProductionBoardEntry $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * update or add a new production board entery
     * @param  int $jobId job id
     * @param  int $pbColumnId production board column id
     * @param  string $data data
     * @return production board column object
     */
    public function updateOrNew($jobId, $pbColumnId, $data = null, $boardId, $taskId = null, $input = [])
    {
        $pbentry = $this->model->firstOrNew([
            'job_id' => $jobId,
            'column_id' => $pbColumnId,
            'board_id' => $boardId
        ]);
        $pbentry->company_id = $this->scope->id();

        if(isset($input['data'])) {
			$pbentry->task_id = $taskId;
		}
		if(isset($input['data'])) {
			$pbentry->data = $data ?: null;
		}
		if(isset($input['color'])) {
			$pbentry->color = $input['color'];
        }

        $pbentry->save();

        return $pbentry;
    }
}
