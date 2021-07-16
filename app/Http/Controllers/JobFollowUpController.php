<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\JobFollowUp;
use App\Models\Task;
use App\Repositories\JobFollowUpRepository;
use App\Transformers\JobFollowUpTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Solr;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class JobFollowUpController extends ApiController
{

    protected $repo;
    protected $transformer;

    public function __construct(JobFollowUpRepository $repo, Larasponse $response)
    {

        $this->repo = $repo;
        $this->response = $response;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /jobfollowup
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $followUps = $this->repo->getFilteredFollowUps($input);
        $limit = isset($input['limit']) ? $input['limit'] : null;
        if (!$limit) {
            $followUps = $followUps->get();
            return ApiResponse::success($this->response->collection($followUps, new JobFollowUpTransformer));
        }
        $followUps = $followUps->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($followUps, new JobFollowUpTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /jobfollowup
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('customer_id', 'job_id', 'stage_code', 'note', 'mark', 'date_time', 'task_assign_to', 'task_due_date');
        $validator = Validator::make($input, JobFollowUp::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $restate = false;
        // check if already marked as lost job..
        $job = Job::findOrFail($input['job_id']);
        if ($currentFollowUp = $job->currentFollowUpStatus->first()) {
            if (($currentFollowUp->mark === 'lost_job') && $input['mark'] === 'lost_job') {
                return ApiResponse::errorGeneral(trans('response.error.already_marked_lost_job'));
            }
        }

        DB::beginTransaction();
        try {
            $input['task_id'] = $this->createTask($input);
            $followUp = $this->repo->saveFollowUp(
                $input['customer_id'],
                $input['job_id'],
                $input['stage_code'],
                $input['note'],
                $input['mark'],
                $input
            );

            if ($followUp->mark === 'lost_job') {
                Solr::jobDelete($followUp->job_id, $followUp->customer_id);
            }

            if ($restate) {
                Solr::jobIndex($followUp->job_id);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();


        if ($followUp->mark === 'lost_job') {
            $message = trans('response.success.marked_as_lost_job');
        } else {
            $message = trans('response.success.saved', ['attribute' => 'Follow up']);
        }

        return ApiResponse::success([
            'message' => $message,
            'follow_up' => $this->response->item($followUp, new JobFollowUpTransformer)
        ]);
    }

    public function store_multiple_follow_up()
    {
        $input = Request::all();
        $validator = Validator::make($input, JobFollowUp::getMultipleFollowUpRules($input));
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $followUps = [];

        DB::beginTransaction();
        try {
            foreach ($input['follow_up'] as $key => $value) {
                $followup = $this->saveFollowUp($value);

                if ($followup) {
                    $followUps[] = $followup;
                }
            }
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Follow up']),
            'follow_ups' => $this->response->collection($followUps, new JobFollowUpTransformer)['data']
        ]);
    }

    /**
     * job follow up completed.
     * POST /job/followup/completed
     *
     * @return Response
     */

    public function completed()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        DB::beginTransaction();
        try {
            $job = Job::find($input['job_id']);
            if (!$job) {
                return ApiResponse::errorGeneral(Lang::get('response.error.invalid', ['attribute' => 'Job id']));
            }
            $latestFollowUp = $job->currentFollowUpStatus->first();
            if (!$latestFollowUp) {
                return ApiResponse::errorGeneral(Lang::get('response.error.not_found', ['attribute' => 'Job follow up']));
            }
            $latestFollowUp->update(['active' => false]);

            JobFollowUp::create([
                'mark' => 'completed',
                'task_id' => null,
                'active' => true,
                'job_id' => $latestFollowUp->job_id,
                'note' => $latestFollowUp->note,
                'order' => $latestFollowUp->order,
                'created_by' => \Auth::id(),
                'date_time' => $latestFollowUp->date_time,
                'company_id' => $latestFollowUp->company_id,
                'customer_id' => $latestFollowUp->customer_id,
            ]);
            DB::commit();
            return ApiResponse::success([
                'message' => Lang::get('response.success.follow_up_completed')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * job follow up reopen.
     * POST /job/followup/reopen
     * @param   $[job_id]
     * @return Response
     */
    public function re_open()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        DB::beginTransaction();
        try {
            $job = Job::find($input['job_id']);
            if (!$job) {
                return ApiResponse::errorGeneral(Lang::get('response.error.invalid', ['attribute' => 'Job id']));
            }
            $jobfollowup = JobFollowUp::completed($input['job_id'])->first();
            if (!$jobfollowup) {
                return ApiResponse::errorGeneral(Lang::get('response.error.follow_up_not_completed'));
            }
            $jobfollowup->delete();
            $followUp = JobFollowUp::latestFollowUp($input['job_id'])->first();
            if ($followUp) {
                $followUp->update(['active' => true]);
            }
            DB::commit();
            return ApiResponse::success([
                'message' => Lang::get('response.success.follow_up_reopen')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * job follow up remove remainder.
     * POST job/followup/remove_remainder
     * @param   $[job_id]
     * @return Response
     */

    public function remove_remainder()
    {
        $input = Request::onlyLegacy('task_id');
        $validator = Validator::make($input, ['task_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $jobFollowUp = JobFollowUp::whereTaskId($input['task_id'])->firstOrFail();
        try {
            $jobFollowUp->update(['task_id' => null]);
            $task = Task::find($input['task_id']);
            if ($task) {
                $task->delete();
            }
            return ApiResponse::success(['message' => Lang::get('response.success.follow_up_remainder_remove')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function destroy($id)
    {
        $followUp = $this->repo->getById($id);
        try {
            $this->repo->delete($followUp);

            if ($followUp->mark === 'lost_job') {
                $message = trans('response.success.lost_job_restated');
            } else {
                $message = trans('response.success.deleted', ['attribute' => 'Follow up']);
            }

            return ApiResponse::success([
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return ApiResponse::trans(Lang::get('response.error.internal'), $e);
        }
    }

    /********************* Private function *************************/

    private function createTask($input)
    {

        if (!ine($input, 'task_assign_to') || !ine($input, 'task_due_date')) {
            return null;
        }

        try {
            $taskRepo = App::make(\App\Repositories\TasksRepository::class);
            $job = Job::findOrFail($input['job_id']);

            $title = $this->getTaskTitle($job);
            $users = (array)$input['task_assign_to'];
            $createdBy = \Auth::id();
            $meta = [];
            $meta['due_date'] = $input['task_due_date'];
            $meta['notes'] = $input['note'];
            $meta['job_id'] = $job->id;
            $meta['customer_id'] = $job->customer_id;

            $task = $taskRepo->createTask($createdBy, $users, $title, $meta);

            return $task->id;
        } catch (\Exception $e) {
            Log::error('FollowUp Error while creating Task. Detail: ' . getErrorDetail($e));
        }

        return null;
    }

    private function getTaskTitle($job)
    {
        $customer = $job->customer;
        $customerName = $customer->first_name . ' ' . $customer->last_name;
        $taskTitle = "Followup Reminder ".$customerName." / ".$job->present()->jobIdReplace;
        $taskTitle = (strlen($taskTitle) > 255) ? substr($taskTitle,0,252).'...' : $taskTitle;

		return $taskTitle;
    }

    private function saveFollowUp($input)
    {
        $restate = false;
        $job = Job::findOrFail($input['job_id']);
        if ($currentFollowUp = $job->currentFollowUpStatus->first()) {
            if (($currentFollowUp->mark === 'lost_job') && $input['mark'] === 'lost_job') {
                return null;
            }
        }
        $input['task_id'] = $this->createTask($input);
        $stageCode = ine($input, 'stage_code') ? $input['stage_code'] : null;

        $followUp = $this->repo->saveFollowUp(
            $input['customer_id'],
            $input['job_id'],
            $stageCode,
            $input['note'],
            $input['mark'],
            $input
        );

        Solr::jobIndex($followUp->job_id);

        return $followUp;
    }
}
