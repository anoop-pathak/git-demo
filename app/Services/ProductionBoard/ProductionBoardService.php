<?php

namespace App\Services\ProductionBoard;

use PDF;
use Excel;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\ApiResponse;
use App\Models\ProductionBoard;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Repositories\ProductionBoardEntryRepository;
use App\Repositories\ProductionBoardColumnRepository;

class ProductionBoardService
{

    function __construct(
        JobRepository $jobRepo,
        ProductionBoardEntryRepository $entryRepo,
        ProductionBoardColumnRepository $pbColumnRepo,
        Context $scope,
        Larasponse $response
    ) {
        $this->entryRepo = $entryRepo;
        $this->jobRepo = $jobRepo;
        $this->pbColumnRepo = $pbColumnRepo;
        $this->scope = $scope;
        $this->response = $response;
    }

    /**
     * Get Filtered production board
     * @param  Array $input Filters
     * @return Response
     */
    public function getFilteredPB($filters)
    {
        return ProductionBoard::whereCompanyId($this->scope->id())->sortable();
    }

    /**
     * Add new production board
     * @param String $name Production Board name
     * @param production board
     */
    public function addNewPB($name, $meta = [])
    {
        $pb = ProductionBoard::create([
            'name' => $name,
            'company_id' => $this->scope->id(),
            'created_by' => \Auth::id()
        ]);

        return $pb;
    }

    /**
     * Get Production board entity by id
     * @param  int $id id of productio board entity
     * @return Production board entity
     */
    public function getById($id)
    {
        return ProductionBoard::whereCompanyId($this->scope->id())
            ->whereId($id)
            ->firstOrFail();
    }

    /**
     * Add job to production job
     * @param Instance $job Job
     * @param Array $boardIds Board ids
     * @return Boolean
     */
    public function addJobToPB($job, $boardIds)
    {
        $boards = ProductionBoard::whereCompanyId($this->scope->id())
        ->whereIn('id', $boardIds)
        ->get();

        $now = Carbon::now()->toDateTimeString();
        if($boards->isEmpty()) return true;
        foreach ($boards as $board) {
            $isExists = DB::table('production_board_jobs')
                ->where('board_id', $board->id)
                ->where('job_id', $job->id)
                ->first();
            if($isExists) continue;
            $lastRec = DB::table('production_board_jobs')
                ->where('board_id', $board->id)
                ->orderBy('order', 'desc')
                ->first();
            $data[] = [
                'job_id'        => $job->id,
                'board_id'      => $board->id,
                'order'         => $lastRec ? ($lastRec->order + 1) : 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        if(empty($data)) return true;
        DB::table('production_board_jobs')->insert($data);

        return true;
    }

    /**
     * Remove job from production board
     * @param  Instance $job Job
     * @param  Int $boardId Board id
     * @return Boolean
     */
    public function removeJobFromPB($job, $boardId)
    {
        $job->productionBoardEntries()->where('board_id', $boardId)->delete();
        $job->productionBoards()->detach($boardId);

        return true;
    }

    /**
     * Save Column
     * @param  String $name Name
     * @param  Int $boardId Board Id
     * @return Column
     */
    public function saveColumn($name, $boardId, $meta = [])
    {
        $order = ($meta['sort_order']) ?: $this->getSortOrder($boardId);

        return $this->pbColumnRepo->save($name, $boardId, $order);
    }

    /**
     * Get valid production board ids
     * @param  array $boardIds Board ids
     * @return Boolean
     */
    public function getValidPBIds($boardIds)
    {
        return ProductionBoard::whereCompanyId($this->scope->id())
            ->whereIn('id', $boardIds)
            ->pluck('id')->toArray();
    }

    /**
     * Get production board jobs
     * @param  array $filters job filters
     * @return query builder
     */
    public function getPBJobs($boardId, $filters)
    {
        $filters['with_archived'] = true;
        $filters['include_projects'] = true;

        $joins = ['customers', 'address'];
        $query = $this->jobRepo->getJobsQueryBuilder($filters, $joins);
        $query->sortable();
        $query->select('jobs.*');

        // fetch only pb jobs..
        $query->pBJobs($boardId, $filters);

        $query->with([
            'productionBoardEntries' => function ($query) use ($boardId) {
                $query->whereBoardId($boardId);
            },
            'productionBoardEntries.productionBoardColumn',
            'customer.address.state',
            'customer.address.country',
            'customer.rep.profile',
            'trades',
            'workTypes',
            'address.state',
            'address.country',
            'jobWorkflow.job'
        ]);

        $query->groupBy('jobs.id');

        return $query;
    }

    /**
     * Production board pdf print
     * @param  Instance $board Board
     * @param  array $filters array
     * @return Pdf Format
     */
    public function pdfPrint($board, $filters = [])
    {
        $columns = $board->columns()->pluck('name', 'id')->toArray();

        //get production board jobs
        $query = $this->getPBJobs($board->id, $filters);

        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
        if (!$limit) {
            $pbJobs = $query->get();
        } else {
            $pbJobs = $query->paginate($limit);
        }

        $company = Company::find($this->scope->id());
        $contents = \view('jobs.production_board', [
            'columns' => $columns,
            'pbJobs' => $pbJobs,
            'company' => $company,
            'board' => $board
        ])->render();

        $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation('landscape');
        $pdf->setOption('dpi', 200);

        return $pdf->stream('jp_progress_board.pdf');
    }

    /**
     * Update or add new entity
     * @param  int $jobId Job Id
     * @param  int $pbColumnId Production board column id
     * @param  int $boardId Production board Id
     * @param  string $data data
     * @return Entry
     */
    public function updateOrNewEntry($jobId, $pbColumnId, $data = null, $boardId, $taskId = null, $input = [])
    {
        return  $this->entryRepo->updateOrNew($jobId, $pbColumnId, $data, $boardId, $taskId, $input);
    }

    /**
     * Get Production Board column id
     * @param  int $id columnn id
     * @return Response
     */
    public function getColumnById($id)
    {
        return $this->pbColumnRepo->getById($id);
    }

    /**
     * Get filtered columns
     * @param  Array $filters Filters
     * @return Response
     */
    public function getFilteredColumns($boardId, $filters = [])
    {
        return $this->pbColumnRepo->getFilteredColumns($boardId, $filters);
    }

    /**
     * Get entry by id
     * @param  int $id entry id
     * @return response
     */
    public function getEntryById($id)
    {
        return $this->entryRepo->getById($id);
    }

    /**
     * Is valid column ids
     * @param  array $columnIds Column ids
     * @return boolean
     */
    public function isValidColumnIds($columnIds)
    {
        return $this->pbColumnRepo->isValidIds($columnIds);
    }

    /**
     * Update sort order
     * @param  array $columnIds column ids
     * @return Boolean
     */
    public function updateColumnSortOrder($columnIds)
    {
        return $this->pbColumnRepo->updateSortOrders($columnIds);
    }

    /**
	 * Get Production Board Deleted Column
	 * @param  int $id  columnn id
	 * @return Response
	 */
	public function getDeletedColumnById($id)
	{
		return $this->pbColumnRepo->getDeletedColumn($id);
	}

    /**
     * Production Board CSV Export
     * @param  int $board board id
     * @param  array $filters Filters
     * @return CSV file
     */
    public function csvExport($board, $filters)
    {
        try {
            $data = [];
            $columns = $board->columns()->pluck('name', 'id')->toArray();
            //get production board jobs
            $jobs = $this->getPBJobs($board->id, $filters)->get();

            $productionBoard = $this->response->collection($jobs, function ($job) use ($columns) {
                $trades = implode(',', $job->trades->pluck('name')->toArray());
                $jobEntries = $job->productionBoardEntries()
                    ->with('task.participants')
                    ->get()
                    ->groupBy('column_id');

                $customerJobData = $job->customer->full_name . ' / ' . $job->present()->jobIdReplace;
                if ($trades) {
                    $customerJobData .= "\n" . $trades;
                }
                $customerJobData .= "\n" . $job->jobWorkflow->stage->name . ' Stage';
                $customerJobData .= ", Job #: " . $job->alt_id;
                $customerJobData .= "\n S/CR: " . $job->customer->present()->salesman;

                $data['Customer/Job'] = $customerJobData;

                foreach ($columns as $id => $column) {

                    $taskDetail = '';

                    if (isset($jobEntries[$id])) {
                        $entry = json_decode($jobEntries[$id]);

                        if($task = $jobEntries[$id][0]['task']) {
                            $participants = implode(',', $task->participants->pluck('full_name')->toArray());
                            $status       = $task->completed ? 'Completed' : null;
                            $taskDetail   = "\n\nTask Detail :-\nTitle : {$task->title}\nAssigned To : $participants";
                            if ($task->due_date && !$status) {
                                $taskDetail .= "\nDue Date : $task->due_date";
                            }elseif($status) {
                                $taskDetail .= "\nStatus : $status";
                            }
                        }
                        
                        $entry = json_decode($jobEntries[$id][0]['data']);
                        if (!isset($entry->value) || !isset($entry->type)) {
                            continue;
                        }
                        switch ($entry->type) {
                            case 'markAsDone':
                                $data[$column] = 'Done'.$taskDetail;
                                break;
                            case 'date':
                                $data[$column] = $entry->value.$taskDetail;
                                break;
                            case 'input_field':
                                $data[$column] = $entry->value.$taskDetail;
                                break;
                            case 'none':
                                $data[$column] = $taskDetail;
                                break;
                        }
                    } else {
                        $data[$column] = "";
                    }
                }

                return $data;
            });

            if ($jobs->isEmpty()) {
                $data[] = 'Customer/Job';
                foreach ($columns as $id => $column) {
                    $data[] = $column;
                }
            } else {
                $data = $productionBoard['data'];
            }

            Excel::create('ProgressBoard', function ($excel) use ($data) {
                $excel->sheet('sheet1', function ($sheet) use ($data) {
                    $sheet->fromArray($data);
                });
            })->export('csv');
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * set order of jobs
     * @param Object $board
     * @param Object $job
     * @param Int $order
     */
    public function setJobOrder($board, $job, $order)
    {
        $currentPos = DB::table('production_board_jobs')->where('board_id', $board->id)
            ->where('job_id', $job->id)
            ->first();
        $desiredPos = DB::table('production_board_jobs')->where('board_id', $board->id)
            ->where('order', $order)
            ->first();
        $currentOrder = $currentPos->order;
        $desiredOrder = $desiredPos ? $desiredPos->order : null;
        if($currentOrder == $order) return true;
        $move = ($currentOrder > $order) ? 'up' : 'down';
        if($move == 'up') {
            $records = DB::table('production_board_jobs')->where('board_id', $board->id)->where('order', '<', $currentOrder);
            if($desiredPos) {
                $records->where('order', '>=', $desiredOrder);
            }
            $records->increment('order');
        }else {
            $records = DB::table('production_board_jobs')->where('board_id', $board->id)->where('order', '>', $currentOrder);
            if($desiredPos) {
                $records->where('order', '<=', $desiredOrder);
            }
            $records->decrement('order');
        }
        DB::table('production_board_jobs')->where('id', $currentPos->id)->update(['order' => $order]);
        return true;
    }
    /*************** Private Section ***************/

    /**
     * Get sort order
     * @param int boardid
     * @return Order
     */
    private function getSortOrder($boardId)
    {
        $column = $this->pbColumnRepo->getFilteredColumns($boardId, [], false);

        $column = $column->orderBy('sort_order', 'desc')->first();

        $sortOrder = 1;
        if ($column) {
            $sortOrder = $column->sort_order + 1;
        }

        return $sortOrder;
    }
}
