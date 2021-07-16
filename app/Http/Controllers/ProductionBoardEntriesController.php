<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\ProductionBoardEntry;
use App\Services\ProductionBoard\ProductionBoardService;
use App\Transformers\ProductionBoardEntryTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\TasksRepository;

class ProductionBoardEntriesController extends Controller
{
    protected $taskRepo;

    function __construct(ProductionBoardService $service, Larasponse $response, TasksRepository $taskRepo)
    {
        $this->service = $service;
        $this->response = $response;
        $this->taskRepo = $taskRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Create or Update production board entry
     * Post /production_boards/entries
     * @return Response
     */
    public function addOrUpdate()
    {
        $input = Request::onlyLegacy('job_id', 'column_id', 'data', 'task_id', 'color');

        $validator = Validator::make($input, ProductionBoardEntry::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $column = $this->service->getColumnById($input['column_id']);

        $pbJob = $column->productionBoard->jobs()
            ->where('jobs.id', $input['job_id'])
            ->exists();

        if (!$pbJob) {
            return ApiResponse::errorGeneral(trans('response.error.pls_move_job_to_pb'));
        }

        $taskId = null;
        if (ine($input, 'task_id')) {
            $task = $this->taskRepo->getById($input['task_id']);
            $taskId = $task->id;
        }

        try {
            // update or create entry..
            $data = $this->service->updateOrNewEntry(
                $input['job_id'],
                $input['column_id'],
                $input['data'],
                $column->board_id,
                $taskId,
				$input
            );

            return ApiResponse::success([
                'message' => 'Progress board updated.',
                'data' => $this->response->item($data, new ProductionBoardEntryTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Remove the job production board entery.
     * Delete production_boards/entries/{id}
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $entry = $this->service->getEntryById($id);

        try {
            $entry->delete();

            return ApiResponse::success(['message' => 'Entry deleted.']);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
