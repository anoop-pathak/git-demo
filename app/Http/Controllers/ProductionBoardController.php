<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\ProductionBoard;
use App\Models\ProductionBoardColumn;
use App\Repositories\JobRepository;
use App\Services\ProductionBoard\ProductionBoardService;
use App\Transformers\ProductionBoardColumnTransformer;
use App\Transformers\ProductionBoardJobTransformer;
use App\Transformers\ProductionBoardTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class ProductionBoardController extends ApiController
{

    function __construct(ProductionBoardService $service, Larasponse $response, JobRepository $jobRepo)
    {
        $this->service = $service;
        $this->response = $response;
        $this->jobRepo = $jobRepo;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the producton boards.
     * Get /production_boards
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $pb = $this->service->getFilteredPB($input);

        if (!$limit) {
            $pb = $pb->get();

            return ApiResponse::success($this->response->collection($pb, new ProductionBoardTransformer));
        }
        $pb = $pb->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($pb, new ProductionBoardTransformer));
    }

    /**
     * Save new production board
     * Post /production_boards
     * @return Response
     */
    public function store()
    {
        try {
            $input = Request::onlyLegacy('name');
            $validator = Validator::make($input, ['name' => 'required']);
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            try {
                $pb = $this->service->addNewPB($input['name'], $input);

                return ApiResponse::success([
                    'message' => trans('response.success.saved', ['attribute' => 'Progress Board']),
                    'data' => $this->response->item($pb, new ProductionBoardTransformer)
                ]);
            } catch (\Exception $e) {
                return ApiResponse::errorInternal(trans('response.error.internal'), $e);
            }
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update production board
     * Put /production_boards/{id}
     * @param  Int $id Production board id
     * @return response
     */
    public function update($id)
    {
        $pb = $this->service->getById($id);

        $input = Request::onlyLegacy('name');
        $validator = Validator::make($input, ['name' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $pb->name = $input['name'];
            $pb->save();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Progress Board']),
                'data' => $this->response->item($pb, new ProductionBoardTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get single production board
     * Get    production_boards/{id}
     * @param  Int $id Production Board Id
     * @return Response
     */
    public function show($id)
    {
        $pb = $this->service->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($pb, new ProductionBoardTransformer)
        ]);
    }

    /**
     * Remove the job production board entery.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $pb = $this->service->getById($id);

        // if ($pb->jobs->count()) {
        //     return ApiResponse::errorGeneral("Progress Board can't be deleted. Please remove assigned archived / unarchived jobs from Progress Board");
        // }

        try {
            $pb->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Progress Board'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get listing of columns by board id
     * Get /production_boards/columns
     * @return Response
     */
    public function getColumns()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['board_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $pbcolumn = $this->service->getFilteredColumns($input['board_id'], $input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $pbcolumns = $pbcolumn->get();

            return ApiResponse::success(
                $this->response->collection($pbcolumns, new ProductionBoardColumnTransformer)
            );
        }
        $pbcolumns = $pbcolumn->paginate($limit);

        return ApiResponse::success(
            $this->response->paginatedCollection($pbcolumns, new ProductionBoardColumnTransformer)
        );
    }

    /**
     * Save Column
     * Post /production_boards/columns
     * @return Response
     */
    public function addColumn()
    {
        $input = Request::onlyLegacy('name', 'board_id', 'sort_order');

        $validator = Validator::make($input, ProductionBoardColumn::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $this->service->getById($input['board_id']);
        try {
            $column = $this->service->saveColumn($input['name'], $input['board_id'], $input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Column']),
                'column' => $this->response->item($column, new ProductionBoardColumnTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update Column
     * Put /production_boards/columns/{id}
     * @param  int $id
     * @return Response
     */
    public function updateColumn($id)
    {
        $input = Request::onlyLegacy('name');
        $pbcolumn = $this->service->getColumnById($id);
        $validator = Validator::make($input, ['name' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $pbcolumn->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Column']),
                'column' => $this->response->item($pbcolumn, new ProductionBoardColumnTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update sort orders
     * Put /production_boards/columns/update_order
     */
    public function updateColumnSortOrder()
    {
        $input = Request::onlyLegacy('column_ids');
        $validator = Validator::make($input, ['column_ids' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $columnIds = array_filter($input['column_ids']);

            // if not valid column ids
            if (!$this->service->isValidColumnIds($columnIds)) {
                return ApiResponse::errorGeneral('Invalid column ids');
            }

            $this->service->updateColumnSortOrder($columnIds);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Column order']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Delete column
     * Delete production_boards/columns/{id}
     * @param  int $id
     * @return Response
     */
    public function deleteColumn($id)
    {
        $pbColumn = $this->service->getColumnById($id);

        //check column entries exists or not exist
        $entiresExists = $pbColumn->productionBoardEntries()->has('job')
            ->where('data', '!=', '{"type":"none","value":"1"}')
            ->exists();

        // if ($entiresExists) {
        //     return ApiResponse::errorGeneral(trans('response.error.pb_column_not_able_to_delete'));
        // }

        DB::beginTransaction();
        try {
            $pbColumn->delete();
            // $pbColumn->productionBoardEntries()->delete();
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Column']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
	 * restore Column
	 * Post /production_boards/columns/id
	 * @return Response
	 */
	public function restoreColumn($id)
	{
		try {
			$this->service->getDeletedColumnById($id);

			return ApiResponse::success([
				'message' => trans('response.success.restored', ['attribute' => 'Column']),
			]);

		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /**
     * Get production board jobs
     * Get /production_boards/jobs
     * @return Response
     */
    public function getPBJobs()
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $validator = Validator::make($input, ['board_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $board = $this->service->getById($input['board_id']);
        $jobs = $this->service->getPBJobs($input['board_id'], $input);

        if (!$limit) {
            $jobs = $jobs->get();

            return ApiResponse::success($this->response->collection($jobs, new ProductionBoardJobTransformer));
        }

        $jobs = $jobs->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($jobs, new ProductionBoardJobTransformer));
    }

    /**
     * Add job to production board
     * Post /production_boards/add_job
     * Response
     */
    public function addJobToPB()
    {
        $input = Request::onlyLegacy('board_ids', 'job_id');

        $validator = Validator::make($input, ProductionBoard::getJobRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $boardIds = arry_fu($input['board_ids']);

        if (empty($boardIds)) {
            return ApiResponse::errorGeneral('Invalid progress board ids.');
        }

        $job = $this->jobRepo->getById($input['job_id']);
        try {
            $this->service->addJobToPB($job, $boardIds);

            $type = 'Job';
            if ($job->isProject()) {
                $type = 'Project';
            }

            return ApiResponse::success([
                'message' => trans('response.success.job_add_to_pb', ['attribute' => $type])
            ]);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Remove job from production board
     * Post /production_boards/remove_job
     * @return Response
     */
    public function removeJobFromPB()
    {
        $input = Request::onlyLegacy('job_id', 'board_id');
        $validator = Validator::make($input, ProductionBoard::getRemoveJobRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->jobRepo->getById($input['job_id']);

        DB::beginTransaction();
        try {
            $this->service->removeJobFromPB($job, $input['board_id']);
            DB::commit();

            $type = 'Job';
            if ($job->isProject()) {
                $type = 'Project';
            }

            return ApiResponse::success([
                'message' => trans('response.success.job_remove_from_pb', ['attribute' => $type])
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Archive job
     * Post production_boards/archive_job
     * @return Response
     */
    public function archiveJob()
    {
        $input = Request::onlyLegacy('job_id', 'board_id', 'archive');

        $validator = Validator::make($input, ProductionBoard::getArchiveRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $board = $this->service->getById($input['board_id']);
        $job = $board->jobs()->whereJobId($input['job_id'])->first();

        if (!$job) {
            return ApiResponse::errorNotFound('Job not found.');
        }

        try {
            $message = 'Job restored.';
            $archived = ine($input, 'archive') ? \Carbon\Carbon::now() : null;
            if ($archived) {
                $message = 'Job archived.';
            }

            $job->pivot->archived = $archived;
            $job->pivot->save();

            return ApiResponse::success([
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get Productin board pdf print
     * Get production_boards/pdf_print
     * @return Pdf File
     */
    public function pdfPrint()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['board_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $board = $this->service->getById($input['board_id']);
        try {
            return $this->service->pdfPrint($board, $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get production board by job id
     * @param  Int $jobId Job Id
     * @return Response
     */
    public function getPBByJobId($jobId)
    {
        $job = $this->jobRepo->getById($jobId);
        $boards = $job->productionBoards()->withPivot('archived')->get();

        return ApiResponse::success(
            $this->response->collection($boards, function ($board) {

                return [
                    'id' => $board->id,
                    'name' => $board->name,
                    'archived' => $board->pivot->archived,
                ];
            })
        );
    }

    /**
     * CSV export of Production Board
     *
     * @return Response
     */
    public function csvExport()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['board_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $board = $this->service->getById($input['board_id']);
        try {
            return $this->service->csvExport($board, $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * set order of jobs
     *
     * GET - /production_boards/jobs/set_order
     *
     * @return response
     */
    public function setJobOrder()
    {
        $input = Request::all();
        $validator = Validator::make($input, [
            'order'     => 'required',
            'job_id'    => 'required',
            'board_id'  => 'required',
        ]);
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job    = $this->jobRepo->getById($input['job_id']);
        $board  = $this->service->getById($input['board_id']);
        $response = $this->service->setJobOrder($board, $job, $input['order']);
        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Order'])
        ]);
    }
}
