<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalideAttachment;
use App\Exceptions\JobCreatedDateInvalid;
use App\Exceptions\JobStageCompletedDateInvalid;
use App\Exceptions\ProjectStageChangeNotAllowed;
use App\Exceptions\QuickBookException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\Customer;
use App\Models\CustomerFeedback;
use App\Models\Job;
use App\Models\JobFollowUp;
use App\Models\JobNote;
use App\Models\JobRepHistory;
use App\Models\Resource;
use App\Models\User;
use App\Repositories\JobNotesRepository;
use App\Repositories\JobRepository;
use App\Repositories\JobsListingRepository;
use App\Services\Jobs\JobProjectService;
use App\Services\Jobs\JobService;
// use App\Services\JobViewTrack\JobViewTrack;
use JobViewTrack;
use QBDesktopQueue;
use App\Services\QuickBooks\QuickBookService;
use Solr;
use App\Transformers\JobNotesTransformer;
use App\Transformers\JobsSelectedListTransformer;
use App\Transformers\JobsTransformer;
use App\Transformers\RecentJobsTransformer;
use App\Transformers\UsersTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Queue;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\HoverRepository;
use App\Repositories\HoverJobRepository;
use App\Transformers\AppointmentsTransformer;
use App\Transformers\JobScheduleTransformer;
use App\Exceptions\InvalidDivisionException;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Exceptions\LockedStageException;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\TasksRepository;
use App\Events\JobCreated;
use App\Transformers\WorkflowStagesTransformer;
use App\Exceptions\PrimaryAttributeCannotBeMultipleException;
use App\Exceptions\InvalidContactIdsException;
use App\Exceptions\EmptyFormSubmitException;
use App\Exceptions\InvalidJobContactData;
use App\Exceptions\WorkflowHistoryDuplicateException;
use App\Events\CloseDripCampaignOfLastStage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\JobFoldersAlreadyLinkedException;
use Exception;

class JobsController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /jobs
     *
     * @return Response
     */
    protected $transformer;
    protected $repo;
    protected $jobProjectService;
    protected $jobNotesRepo;
    protected $scope;
    protected $jobsListingRepo;
    protected $quickService;

    public function __construct(Larasponse $response, JobProjectService $jobProjectService, JobRepository $repo, JobNotesRepository $jobNotesRepo, JobsListingRepository $jobsListingRepo, QuickBookService $quickService, JobService $service, HoverRepository $hoverRepo, HoverJobRepository $hoverJobRepo,, TasksRepository $taskRepo)
    {

        $this->response = $response;
        $this->repo = $repo;
        $this->jobNotesRepo = $jobNotesRepo;
        $this->jobProjectService = $jobProjectService;
        $this->jobsListingRepo = $jobsListingRepo;
        $this->quickService = $quickService;
        $this->service = $service;
        $this->hoverRepo = $hoverRepo;
        $this->hoverJobRepo = $hoverJobRepo;
        $this->taskRepo = $taskRepo;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    public function index()
    {
        $input = Request::all();
        try{
            $jobs = $this->repo->getFilteredJobs($input);
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $jobs = $jobs->get();
                return ApiResponse::success($this->response->collection($jobs, new JobsTransformer));
            }
            $jobs = $jobs->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($jobs, new JobsTransformer));
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
        } catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /jobs
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $input['same_as_customer_address'] = array_get($input, 'same_as_customer_address', 1);
        $input['multi_job'] = array_get($input, 'multi_job', 0);

        $scope = ['customer_id'];
        if (ine($input, 'contacts')) {
            $scope[] = 'contacts';
        }

        if (ine($input, 'projects')) {
            $scope[] = 'projects';
            $scope['projects_count'] = count($input['projects']);
        }

        if(ine($input, 'hover_capture_request')){
			$scope = array_merge($scope, ['hoverCaptureRequest']);
		}

        $validator = Validator::make($input, Job::validationRules($scope));

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();

        try {
            $job = $this->jobProjectService->saveJobAndProjects($input);

            /* Set Response Attribute */
            DB::commit();
            $attribute = 'Job';
            if (ine($input, 'parent_id')) {
                $attribute = 'Project';
            } else {
                Queue::push('\App\Handlers\Events\JobQueueHandler@jobIndexSolr', ['job_id' => $job->id]);
            }
            $projectIds = [];
            if($job->isMultiJob()) {
                $projectIds = array_merge($projectIds, $job->projects()->where('sync_on_companycam', true)->pluck('id')->toArray());
            }
            if($job->sync_on_companycam){
                array_push($projectIds, $job->id);
            }
            foreach(arry_fu($projectIds) as $projectId) {
                Queue::push('App\Handlers\Events\JobQueueHandler@createCompanyCamProject', [
                    'company_id' => getScopeId(),
                    'job_id'     => $projectId
                ]);
            }

            Event::fire('JobProgress.Jobs.Events.Saved', new JobCreated($job));
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => $attribute]),
                'job' => $this->response->item($job, new JobsTransformer)
            ]);

        } catch (PrimaryAttributeCannotBeMultipleException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (InvalidContactIdsException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(EmptyFormSubmitException $e){
			//Do Nothing
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidJobContactData $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e){
			//DO Nothing
			// DB::rollback();
			// return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){
			//Do Nothing
			// DB::rollback();
			// return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidDivisionException $e){
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /jobs/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $input = Request::all();
        $job = Job::findOrFail($id);
        $job = $this->repo->getJobById($id);
        try{
                //Archived job
                if ($job->isArchived()) {
                    return ApiResponse::errorGeneral(trans('response.error.archived_job_not_open'));
                }

                if (isset($input['track_job'])) {
                    JobViewTrack::track($id);
                }

                if ($job->wp_job == true && $job->wp_job_seen == false) {
                    $job = Job::find($job->id);
                    $job->update(['wp_job_seen' => true]);
                }

                return ApiResponse::success([
                    'data' => $this->response->item($job, new JobsTransformer)
                ]);
        } catch(InvalidDivisionException $e){
            DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /jobs/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        if (!SecurityCheck::maxCustomerJobEditLimit()) {
            return SecurityCheck::$error;
        }
        $scope = ['customer_id'];
        $job = Job::findOrFail($id);

        $input = Request::all();
        $input['id'] = $id;
        $input['same_as_customer_address'] = array_get($input, 'same_as_customer_address', 1);
        if(ine($input, 'contacts' )) $scope = ['contacts'];
		if(ine($input, 'hover_capture_request')) $scope[] = 'hover_capture_request';

        $validator = Validator::make($input, Job::validationRules($scope));

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        DB::beginTransaction();
        try {
            $job = $this->jobProjectService->saveJobAndProjects($input);
            $attribute = 'Job';
            DB::commit();
            Queue::push('\App\Handlers\Events\JobQueueHandler@createHoverJob', ['job_id' => $job->id,
                'company_id' => $job->company_id
            ]);
            if ($job->isProject()) {
                $attribute = 'Project';
            } else {
                Queue::push('\App\Handlers\Events\JobQueueHandler@jobIndexSolr', ['job_id' => $job->id]);
            }

            $projectIds = [];
            if($job->isMultiJob()) {
                $projectIds = array_merge($projectIds, $job->projects()->where('sync_on_companycam', true)->pluck('id')->toArray());
            }

            if(isTrue($job->sync_on_companycam)){
                array_push($projectIds, $job->id);
            }
            foreach(arry_fu($projectIds) as $projectId) {
                Queue::push('App\Handlers\Events\JobQueueHandler@createCompanyCamProject', [
                    'company_id' => getScopeId(),
                    'job_id'     => $projectId
                ]);
            }

            Event::fire('JobProgress.Jobs.Events.Saved', new JobCreated($job));

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => $attribute]),
                'job' => $this->response->item($job, new JobsTransformer)
            ]);
        } catch (PrimaryAttributeCannotBeMultipleException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (InvalidContactIdsException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(EmptyFormSubmitException $e){
			//Do Nothing
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidJobContactData $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidDivisionException $e){
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update Job Current Stage (Manage JobWorkflow)
     * Post /jobs/stage
     *
     * @return Response
     */
    public function update_stage()
    {
        $input = Request::onlyLegacy('job_id', 'stage');

        $validator = Validator::make($input, Job::getUpdateStageRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->repo->getById($input['job_id']);

        if(!empty($data['locked_stages'] = $this->repo->getTaskLockedStageMoveCount($job, $input['stage']))) {

			return ApiResponse::errorGeneral(trans('Please complete the pending task in the current workflow stage to proceed.'), [], $data);
		}

        try {
            $jobWorkflow = $this->jobProjectService->manageWorkFlow($job, $input['stage']);
        } catch(UnauthorizedException $e){
			//Do Nothing
			// DB::rollback();
			// return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e){
			//DO Nothing
			// DB::rollback();
			// return ApiResponse::errorGeneral($e->getMessage());
        } catch (ProjectStageChangeNotAllowed $e) {

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(WorkflowHistoryDuplicateException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Stage']),
            'job_workflow' => $jobWorkflow
        ]);
    }

    public function moveMultipleStageJobsToNewStage()
	{
		set_time_limit(0);
		$input = Request::all();

		$validator = Validator::make($input, Job::moveMultipleStageJobsRules());

		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		if(in_array($input['new_stage'], $input['stage_codes'])) {

			return ApiResponse::errorGeneral('Please remove new stage code from job stages array.');
		}

		try {
			$stats = $this->jobProjectService->moveMultipleStageJobsToNewStage($input['new_stage'], arry_fu($input['stage_codes']));

		} catch(UnauthorizedException $e){

		} catch(QuickBookException $e){

		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound($e->getMessage());
		} catch(ProjectStageChangeNotAllowed $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(WorkflowHistoryDuplicateException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		$msg = $stats['jobs_moved']." out of ".$stats['total_jobs']." job(s) moved into ". $stats['new_stage_name']." stage.";

		return ApiResponse::success([
			'message' => $msg
		]);
	}

    public function add_note()
    {
        $input = Request::all();

        $validator = Validator::make($input, JobNote::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $createdBy = \Auth::id();
        $input['stage_code'] = isset($input['stage_code']) ? $input['stage_code'] : null;

        $input['attachments'] = isset($input['attachments']) ? $input['attachments'] : [];

		$note = $this->jobNotesRepo->saveNote($input['job_id'],$input['note'],$input['stage_code'],$createdBy, null, $input['attachments']);
        if ($note) {
            return ApiResponse::success([
                'message' => trans('response.success.added', ['attribute' => 'Note']),
                'data' => $this->response->item($note, new JobNotesTransformer)
            ]);
        }
        return ApiResponse::errorInternal();
    }

    public function get_notes()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $notes = $this->jobNotesRepo->getFiltredNotes($input);

        $limit = isset($input['limit']) ? $input['limit'] : null;
        if (!$limit) {
            $notes = $notes->get();
            return ApiResponse::success($this->response->collection($notes, new JobNotesTransformer));
        }
        $notes = $notes->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($notes, new JobNotesTransformer));
    }

    public function delete_note($noteId)
    {
        if (!\Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }
        $note = $this->jobNotesRepo->getById($noteId);
        $note->deleteAllAttachments($note, JobNote::JOB_NOTE);
        if ($note->delete()) {
            $note->job->updateJobUpdatedAt();
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Job note']),
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /jobs/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        if (!\Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        $input = Request::onlyLegacy('password', 'note');

        $validator = Validator::make($input, Job::getDeleteRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $job = $this->repo->getById($id);

        if ($job->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Job']),
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Restore soft deleted job
     * Put /customers/{id}/restore
     *
     * @param  int $id
     * @return Response
     */
    public function restore($id)
    {
        $input = Request::onlyLegacy('project_id', 'all_project');

        if (!Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $job = $this->repo->getDeletedById($id);

        DB::beginTransaction();
        try {
            $this->service->restoreJob($job, $input['all_project'], $input['project_id']);
            $attribute = 'Job';

            if($job->isProject()) {
				$attribute = 'Project';
			}
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.restored', ['attribute' => $attribute]),
        ]);
    }

    public function recent_viewed_jobs()
    {
        $input = Request::all();
        $jobs = JobViewTrack::getJobs();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (ine($input, 'less_data')) {
            $jobs->with([
                'customer' => function ($query) {
                    $query->select('id', 'first_name', 'last_name');
                }
            ]);
            $transformer = new RecentJobsTransformer;
        } else {
            $transformer = new JobsTransformer;
        }

        if (!$limit) {
            $jobs = $jobs->get();
            return ApiResponse::success($this->response->collection($jobs, $transformer));
        }
        $jobs = $jobs->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($jobs, $transformer));
    }

    /*
	 * Save multiple jobs
	 */
    public function save_jobs()
    {
        $input = Request::onlyLegacy('customer_id', 'jobs');
        $validator = Validator::make($input, Job::getSaveMultipleJobsRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!$customer = Customer::find($input['customer_id'])) {
            return ApiResponse::errorNotFound(trans('response.error.invalid', ['attribute' => 'Customer Id']));
        }


        if ($validator = $this->validateJobs($input['jobs'])) {
            return ApiResponse::validation($validator);
        }

        $meta = [
            'job_ids' => [],
            'first_stage_code' => null,
        ];

        $token = null;

        DB::beginTransaction();
        try {
            $projectIds = [];
            foreach ($input['jobs'] as $key => $job) {
                $job['customer_id'] = $input['customer_id'];
                $job = $this->jobProjectService->saveJobAndProjects($job);

                if($job->isMultiJob()) {
                    $projectIds = array_merge($projectIds, $job->projects()
                        ->where('sync_on_companycam', true)
                        ->pluck('id')
                        ->toArray()
                    );
                }

                if($job->sync_on_companycam){
                    array_push($projectIds, $job->id);
                }

                $meta['job_ids'][$key] = $job->id;

                //solr index skip project
                if (!$job->isProject()) {
                    Solr::jobIndex($job->id);
                }
            }

            $meta['first_stage_code'] = $job->jobWorkflow->current_stage;
            $token = QuickBooks::getToken();

		} catch (PrimaryAttributeCannotBeMultipleException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (InvalidContactIdsException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(EmptyFormSubmitException $e){
			//Do Nothing
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidJobContactData $e){
			DB::rollback();


			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){
			//Do Nothing
			// DB::rollback();
			// return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e){
			//Do Nothing
			// DB::rollback();
			// return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidDivisionException $e){
            DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
            DB::rollBack();
            Solr::customerIndex($input['customer_id']);

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        foreach(arry_fu($projectIds) as $projectId) {
            Queue::push('App\Handlers\Events\JobQueueHandler@createCompanyCamProject', [
                'company_id' => getScopeId(),
                'job_id'     => $projectId
            ]);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Jobs']),
            'job_ids' => $meta['job_ids'],
            'first_stage_code' => $meta['first_stage_code'],
        ]);
    }

    /**
     * Save or update job amount or tax_rate
     * PUT /jobs/{id}
     *
     * @param  int $id | Job Id
     * @return Response
     */
    public function job_description($id)
    {

        if (!SecurityCheck::maxCustomerJobEditLimit()) {
            return SecurityCheck::$error;
        }

        $job = $this->repo->getById($id);
        $input = Request::onlyLegacy('description');
        $validator = Validator::make($input, ['description' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        if ($job->update($input)) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Job description']),
            ]);
        }
        return ApiResponse::errorInternal();
    }

    /**
     * @ Change job labours
     */
    public function changeLabours($jobId)
    {
        $job = $this->repo->getById($jobId);
        $input = Request::onlyLegacy('labour_ids');
        try {
            $this->repo->assignLabours($job, (array)$input['labour_ids']);

            return ApiResponse::success([
                'message' => trans('response.success.changed', ['attribute' => 'Labors']),
            ]);
        } catch(InvalidDivisionException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * @ Change job sub_contractors
     */
    public function changeSubContractors($jobId)
    {
        $job = $this->repo->getById($jobId);
        $input = Request::onlyLegacy('sub_contractor_ids');
        try {
            $this->repo->assignSubContractors($job, (array)$input['sub_contractor_ids']);

            return ApiResponse::success([
                'message' => trans('response.success.changed', ['attribute' => 'Sub Contractors']),
            ]);
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * @ Change job division
     */
    public function changeDivision($jobId)
    {
        $job = $this->repo->getById($jobId);
        $input = Request::onlyLegacy('division_id');

        $validator = Validator::make($input, ['division_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $job->division_id = $input['division_id'];
            $job->save();

            return ApiResponse::success([
                'message' => trans('response.success.changed', ['attribute' => 'Job division']),
            ]);
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function change_representatives($id)
    {
        $job = $this->repo->getById($id);
        $input = Request::onlyLegacy('rep_ids');
        try {
            $oldReps = $job->reps()->pluck('rep_id')->toArray();
            $this->repo->assignReps(
                $job,
                \Auth::user(),
                null,
                null,
                null,
                null,
                (array)$input['rep_ids'],
                $oldReps
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.changed', ['attribute' => 'Representative']),
            ]);
        } catch(InvalidDivisionException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function job_rep_history()
    {
        $input = Request::onlyLegacy('job_id', 'stage_code');

        // get rep ids from job activity_logs table for rep assign event..
        $repIds = JobRepHistory::job($input['job_id'])->stage($input['stage_code'])->whereType(Job::REP)->pluck('rep_id')->toArray();
        $estimatorIds = JobRepHistory::job($input['job_id'])->stage($input['stage_code'])->whereType(Job::ESTIMATOR)->pluck('rep_id')->toArray();
        $reps = User::whereIn('id', $repIds)->get();
        $estimators = User::whereIn('id', $estimatorIds)->get();
        return ApiResponse::success([
            'reps' => $this->response->collection($reps, new UsersTransformer)['data'],
            'estimators' => $this->response->collection($estimators, new UsersTransformer)['data'],
        ]);
    }

    /**
     * Get Deleted jobs
     * Get /jobs/deleted
     *
     * @return Response
     */
    public function deleted_jobs()
    {
        $input = Request::all();
        $jobs = $this->repo->getFilteredJobs($input)->onlyTrashed();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $jobs = $jobs->get();
            return ApiResponse::success($this->response->collection($jobs, new JobsTransformer));
        }
        $jobs = $jobs->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($jobs, new JobsTransformer));
    }

    /**
     * Customer communication for call or appointment required
     * Put /job/{id}/communication
     *
     * @return Response
     */
    public function customer_communication($id)
    {
        $input = Request::onlyLegacy('type', 'status');
        $validator = Validator::make($input, Job::getCustomerCommunicationRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $job = Job::findOrFail($id);
            if ($input['type'] == 'call') {
                $job->call_required = $input['status'];
            } else {
                $job->appointment_required = $input['status'];
            }
            $job->update();
            return ApiResponse::success(['message' => Lang::get('response.success.updated', ['attribute' => 'Job'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function job_follow_up_filters_list()
    {
        $data = [];

        $jobs = $this->jobsListingRepo->getJobsQueryBuilder(['include_lost_jobs' => true]);

        $companyId = getScopeId();
        $followUpsJoinQuery = generateQueryWithBindings(JobFollowUp::whereActive(true)
            ->whereCompanyId($companyId));

        $data = $jobs->leftJoin(
            DB::raw("($followUpsJoinQuery) as job_follow_up"),
            'job_follow_up.job_id',
            '=',
            'jobs.id'
        )->selectRaw("
			CASE
			WHEN (job_follow_up.mark = 'call' and `order` = 1) THEN 'call1'
			WHEN (job_follow_up.mark = 'call' and `order` = 2) THEN 'call2'
			WHEN (job_follow_up.mark = 'call' and `order` >= 3) THEN 'call3_or_more'
			WHEN (job_follow_up.mark is null) THEN 'no_follow_up'
			ELSE job_follow_up.mark
			END as 'key',
			COUNT(jobs.id) as jobs_count")
            ->groupBy('key')
            ->where(function ($query) {
                $query->where('job_follow_up.mark', '!=', 'completed')
                    ->orWhereNull('job_follow_up.mark');
            })->get();

        // get reminders count..
        $reminderCount = $this->jobsListingRepo->getJobsQueryBuilder()
            ->leftJoin(
                DB::raw("($followUpsJoinQuery) as job_follow_up"),
                'job_follow_up.job_id',
                '=',
                'jobs.id'
            )->where('job_follow_up.mark', '!=', 'lost_job')
            ->whereNotNUll('job_follow_up.mark')
            ->whereNotNUll('task_id')
            ->count();

        $data[] = [
            'key' => 'reminder',
            'jobs_count' => $reminderCount,
        ];

        return ApiResponse::success(['data' => $data]);
    }

    public function share_resource()
    {
        $input = Request::onlyLegacy('subject', 'content', 'email', 'resource_id', 'job_id');
        $validator = Validator::make($input, Job::getSendMailRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->repo->getById($input['job_id']);
        $attachment[0] = [
            'type' => 'resource',
            'value' => $input['resource_id']
        ];
        try {
            // set meta
            $meta['job_id'] = (array)$job->id;
            $meta['customer_id'] = $job->customer_id;

            App::make(\App\Services\Emails\EmailServices::class)->sendEmail(
                $input['subject'],
                $input['content'],
                (array)$input['email'],
                [],
                [],
                $attachment,
                \Auth::id(),
                $meta
            );
            return ApiResponse::success(['message' => Lang::get('response.success.email_sent')]);
        } catch (InvalideAttachment $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * Save base 64 encoded image in job resource
     * Post /jobs/{id}/save_image
     *
     * @param  int $id
     * @return Response
     */
    public function save_base64_image()
    {

        $input = Request::onlyLegacy('base64_string', 'job_id', 'rotation_angle');
        $validator = Validator::make($input, Job::getSaveImageRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->repo->getById($input['job_id']);
        try {
            //get job resource id
            $meta = $job->jobMeta->pluck('meta_value', 'meta_key')->toArray();
            $resourceId = $meta['resource_id'];
            //find root directory..
            $root = Resource::where('parent_id', $resourceId)->locked()->first();
            $resourceService = App::make(\App\Resources\ResourceServices::class);

            if (!$root) {
                $root = $resourceService->createDir('Photos', $resourceId, true);
            }
            //use app service..

            $file = $resourceService->uploadFile($root->id, $input['base64_string'], $base64 = true, $input['job_id'], $input);

            return ApiResponse::success([
                'message' => Lang::get('response.success.file_uploaded'),
                'file' => $file
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * ****
     * @param  [type] $noteId [note id]
     * @return [json]         [notes]
     */
    public function edit_note($noteId)
    {
        $input = Request::onlyLegacy('note', 'attachments', 'delete_attachments');
        $validator = Validator::make($input, ['note' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $jobNote = JobNote::findOrFail($noteId);
        try {
            $note = $this->jobNotesRepo->updateNote($input['note'], Auth::user()->id, $jobNote, $input['attachments'], $input['delete_attachments']);
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Note']),
                'data' => $this->response->item($note, new JobNotesTransformer)
            ]);
        } catch(InvalideAttachment $e){
			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * Mark to be scheduled
     * Put /jobs/{id}/to_be_scheduled
     *
     * @param  int $id
     * @return Response
     */
    public function markToBeScheduled($id)
    {
        $job = $this->repo->getById($id);
        try {
            if (Request::get('remove')) {
                $job->to_be_scheduled = null;
            } else {
                $job->to_be_scheduled = Carbon::now()->toDateTimeString();
            }
            $job->save();

            $attribute = 'Job';
            if ($job->isProject()) {
                $attribute = 'Project';
            }

            if (Request::get('remove')) {
                return ApiResponse::success([
                    'message' => trans('response.success.job_removed_from_schedule', ['attribute' => $attribute])
                ]);
            }

            return ApiResponse::success([
                'message' => trans('response.success.job_added_for_schedule', ['attribute' => $attribute])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * Update Completion Date
     * Put /jobs/{id}/completion_date
     *
     * @param  int $id
     * @return Response
     */
    public function updateCompletionDate($id)
    {
        $job = $this->repo->getById($id);

        $input = Request::onlyLegacy('date');
        $validator = Validator::make($input, ['date' => 'date']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $date = Request::get('date');
            $job->completion_date = !empty($date) ? $date : null;

            $job->save();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Job completion date'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * User Assign
     * Put jobs/{id}/user_assigns
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function assignUsers($id)
    {
        $input = Request::onlyLegacy('rep_ids', 'labour_ids', 'sub_contractor_ids');
        $validator = Validator::make($input, Job::getUserAssignRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->repo->getById($id);
        try {
            $this->assignReps($job, $input['rep_ids']);
            $this->assignLabours($job, $input['labour_ids'], $input['sub_contractor_ids']);

            return ApiResponse::success([
                'job' => $this->response->item($job, new JobsTransformer)
            ]);
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update Job Duration
     * Put jobs/{id}/duration
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function updateJobDuration($id)
    {
        $job = $this->repo->getById($id);
        $job->duration = Request::get('duration');
        if ($job->save()) {
            return ApiResponse::success(['message' => 'Job duration updated.']);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * jobs/{id}/work_crew_notes
     * [updateWorkCrewNotes description]
     * @param  [int] $id [job id]
     * @return [resposne]
     */
    public function updateWorkCrewNotes($id)
    {
        $input = Request::onlyLegacy('work_crew_notes');
        $job = $this->repo->getById($id);
        try {
            $job->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Work crew notes'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Put- project/{id}/status_update
     * @param  [int] $id [Project Id]
     * @return [type]     [description]
     */
    public function statusUpdate($id)
    {
        $project = $this->repo->getById($id);
        $input = Request::onlyLegacy('status', 'text_notification', 'email_notification', 'notify_users');
        $data['old_status_id'] = $project->status;
        if ($project->update($input)) {
            //send text notification or email notfication
            if ((ine($input, 'text_notification') || ine($input, 'email_notification'))
                && ((int)$data['old_status_id'] != (int)$project->status)
                && !empty(arry_fu((array)$input['notify_users']))) {
                $data = array_merge($data, $input);
                $data['sender_id'] = \Auth::id();
                $data['project_id'] = $id;
                Queue::push(\App\Services\Queue\ProjectStatusChangedNotification::class, $data);
            }


            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Project status'])
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Put- project/{id}/awarded
     * @param  [int] $id [Project Id]
     * @return [type]     [description]
     */
    public function projectAwarded($id)
    {
        $project = $this->repo->getProjectById($id);

        $input = Request::onlyLegacy('awarded');
        $validator = Validator::make($input, ['awarded' => 'required|boolean']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $project = $this->repo->getProjectById($id);

        // if already in same awarded status..
        if ($project->awarded == $input['awarded']) {
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Project'])
            ]);
        }

        DB::beginTransaction();
        try {
            $project->awarded = $input['awarded'];
            $project->update();
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal($e, trans('response.error.internal'));
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Project'])
        ]);
    }

    /**
     * job moved to production board
     * Get jobs/{id}/add_to_production_board
     * @param jobId $jobId Job Id
     */
    public function add_to_pb($jobId)
    {
        $job = $this->repo->getById($jobId);
        try {
            $job->movedToPB();

            return ApiResponse::success([
                'message' => trans('response.success.job_add_to_pb')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get Customer feedbacks
     * Get jobs_complaints/{job_id}
     * @param  int $jobId job id
     * @return Response
     */
    public function getCustomerFeedbacks($jobId)
    {
        $input = Request::onlyLegacy('type');
        $job = $this->repo->getById($jobId);
        $validator = Validator::make($input, ['type' => 'required|in:testimonial,complaint']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $feedback = CustomerFeedback::whereJobId($jobId)
                ->whereType($input['type'])
                ->get();

            return ApiResponse::success(['data' => $feedback]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * job remove from production board
     * Get jobs/{id}/remove_from_production_board
     * @param jobId $jobId Job Id
     */
    public function remove_from_pb($jobId)
    {
        $job = $this->repo->getById($jobId);
        try {
            $job->productionBoardEntries()->delete();
            $job->update(['moved_to_pb' => null]);

            return ApiResponse::success([
                'message' => trans('response.success.job_remove_from_pb')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update insurance fields
     * Put jobs/{id}/insurance
     * @param  int $id | Job Id
     *
     * @return response
     */
    public function jobInsurance($id)
    {
        $job = $this->repo->getById($id);

        $input = Request::onlyLegacy(
            'insurance',
            'insurance_company',
            'insurance_number',
            'phone',
            'fax',
            'email',
            'adjuster_name',
            'adjuster_phone',
            'adjuster_email',
            'rcv',
            'deductable_amount',
            'policy_number',
            'contingency_contract_signed_date',
            'date_of_loss',
            'acv',
            'total',
            'adjuster_phone_ext',
            'depreciation',
            'supplement',
            'net_claim'
        );

        $validator = Validator::make($input, ['insurance' => 'boolean']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job->insurance = $input['insurance'];
        $job->save();

        unset($input['insurance']);

        $job = $this->repo->saveOrUpdateInsuranceDetails($job, $input);

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Job']),
            'job' => $this->response->item($job, new JobsTransformer),
        ]);
    }

    /**
     * Put jobs/id/archive
     * @param  Int $id [description]
     * @return [type]     [description]
     */
    public function archive($id)
    {
        $input = Request::onlyLegacy('archive', 'archive_cwp');
        $job = $this->repo->getById($id);
        DB::beginTransaction();
        try {
            $jobLabel = 'Job';
            if ($job->isProject()) {
                $jobLabel = 'Project';
            }

            $archivedCWP = $archived = null;
            $msg = trans('response.success.restored', ['attribute' => $jobLabel]);
            $currentDateTime = \Carbon\Carbon::now();

            if (ine($input, 'archive')) {
                $archived = $currentDateTime;
                $msg = trans('response.success.archived', ['attribute' => $jobLabel]);
            }

            if (ine($input, 'archive_cwp')) {
                $archivedCWP = $currentDateTime;
            }
            $job->archived_cwp = $archivedCWP;
            $job->archived = $archived;
            $job->update();

            if ($job->isMultiJob()) {
                Job::where('parent_id', $job->id)
                    ->where('company_id', getScopeId())
                    ->update([
                        'archived' => $archived,
                        'archived_cwp' => $archivedCWP
                    ]);
            }

            //if job is archived then remove otherwise reindex
            Solr::jobIndex($job->id);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => $msg
        ]);
    }

    /*
	 * Update Job Priority
	 * Put jobs/{id}/priority
	 * @param Int $jobId Job Id
	 * @return  Response
	 */
    public function updatePriority($jobId)
    {
        $input = Request::onlyLegacy('priority');
        $validator = Validator::make($input, ['priority' => 'required|in:low,medium,high']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->repo->getById($jobId);
        try {
            $job->priority = $input['priority'];
            $job->update();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Job priority'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update Job Note
     * jobs/{id}/note
     * @param  Int $jobId Job Id
     * @return Response
     */
    public function updateNote($jobId)
    {
        $input = Request::onlyLegacy('note', 'note_date');
        $job = $this->repo->getById($jobId);
        try {
            $job->note = $input['note'];
            $job->note_date = $input['note_date'];
            $job->update();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Job note'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Multi Jobs Filtered Count
     * Get /jobs/multi_job_filter_count
     * @return Count
     */
    public function multiJobFilterCount()
    {
        try {
			$data = [
				'single_jobs_count'				=> 0,
				'multi_jobs_count'				=> 0,
				'single_jobs_insurance_count'	=> 0,
				'multi_jobs_insurance_count'	=> 0
			];

			$job = $this->jobsListingRepo->getJobsQueryBuilder()
				->selectRaw('
					COUNT(DISTINCT(CASE WHEN jobs.multi_job=1 THEN jobs.id END)) as multi_jobs_count,
					COUNT(DISTINCT(CASE WHEN jobs.multi_job=0 THEN jobs.id END)) as single_jobs_count,
					COUNT(DISTINCT(CASE WHEN jobs.multi_job=1 AND jobs.insurance=1 THEN jobs.id END)) as multi_jobs_insurance_count,
					COUNT(DISTINCT(CASE WHEN jobs.multi_job=0 AND jobs.insurance=1 THEN jobs.id END)) as single_jobs_insurance_count
					')
				->first();

			if($job) {
				$data = $job->toArray();
			}

			return ApiResponse::success(['data' => $data]);
		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
     * Change completed date of stage
     * @return Response
     */
    public function changeStageComletedDate()
    {
        $input = Request::onlyLegacy('job_id', 'stage_code', 'completed_date');
        $validator = Validator::make($input, Job::getStageCompletedDateRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->repo->getById($input['job_id']);
        DB::beginTransaction();
        try {
            $completedDate = $this->jobProjectService->changeStageCompletedDate($job, $input['stage_code'], $input['completed_date']);
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return ApiResponse::errorNotFound($e->getMessage());
        } catch (ProjectStageChangeNotAllowed $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (JobStageCompletedDateInvalid $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.changed', ['attribute' => 'Completed date']),
            'data' => [
                'completed_date' => $completedDate
            ]
        ]);
    }

    /**
     * Update Contract signed date
     * Put /jobs/contract_signed_date
     * @return Response
     */
    public function updateContractSignedDate()
    {
        $input = Request::onlyLegacy('job_id', 'date');

        $validator = Validator::make($input, [
            'job_id' => 'required',
            'date' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->repo->getById($input['job_id']);
        $job->cs_date = ine($input, 'date') ? $input['date'] : null;
        $job->save();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Contract signed date']),
        ]);
    }

    /**
     * Upadte Created Date
     * Put /jobs/7010/created_date
     * @param  int $id JobId
     * @return response
     */
    public function updateCreatedDate($id)
    {
        $job = $this->repo->getById($id);
        $input = Request::onlyLegacy('created_date');
        $validator = Validator::make($input, Job::getCreatedDateRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $job = $this->jobProjectService->updateCreatedDate($job, $input['created_date']);
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Created date']),
                'data' => [
                    'created_date' => $job->created_date
                ],
            ]);
        } catch (JobCreatedDateInvalid $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Save job on quickbook
     * Put /jobs/{id}/save_on_quickbook
     * @return Response
     */
    public function saveOnQuickbook($id)
    {
        $input = Request::onlyLegacy('type');

        $job = $this->repo->getById($id);

        $token =  QuickBooks::getToken();

        if (!$token) {
            return ApiResponse::errorGeneral(
                trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
            );
        }

        try {
            $attr = 'Job';
            switch ($input['type']) {
                case 'financials':
                    $attr = 'Job payments and credits';
                    break;
                case 'invoices_with_financials':
                    $attr = 'Job invoices';
                    break;
            }

            if(!$job->qb_display_name && $job->canBlockFinacials()){
				$job->qb_display_name = Job::QBDISPLAYNAME;
				$job->save();
			}

			// $this->quickService->createOrUpdateJob($token, $job, $input);

			QBCustomer::syncJobToQuickBooks($job->id, [
				'action' => 'manual',
				'quickbook_id' => $job->quickbook_id,
				'attr' => $attr,
				'input' => $input
			]);

            return ApiResponse::success([
                'message' => trans('response.success.quickbook_synced', ['attribute' => $attr])
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * mark job workflow last stage completed
     *
     * PUT - /jobs/mark_last_stage_completed
     *
     * @return response
     */
    public function markLastStageCompleted()
    {
        $input = Request::onlyLegacy('job_id', 'date');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->repo->getById($input['job_id']);
        if ($job->parent_id) {
            return ApiResponse::errorGeneral('Project job not allowed.');
        }
        if (!$job->inLastStage()) {
            return ApiResponse::errorGeneral(trans('response.success.move_job_to_last_stage'));
        }
        try {
            $message = trans('response.success.marked_as_completed', ['attribute' => 'Last stage']);
            if (!isset($input['date'])) {
                $date = Carbon::now();
            } elseif(ine($input, 'date')) {
                $date = utcConvert($input['date']);
            } else {
                $date    = null;
                $message = trans('response.success.marked_as_in_completed');
            }
            $jobWorkflow = $job->jobWorkflow;
            $jobWorkflow->last_stage_completed_date = $date;
            $jobWorkflow->save();

            Event::fire('JobProgress.Jobs.Events.CloseDripCampaignOfLastStage', new CloseDripCampaignOfLastStage($job));

            return ApiResponse::success(['message' => $message]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Check Sync Status of Job
     * Get /jobs/qb_sync_status
     * @return Response
     */
    public function qbSyncStatus()
    {
        $input = Request::onlyLegacy('job_id');

        $validator = Validator::make($input, ['job_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $token = QuickBooks::getToken();

            if (!$token) {
                return ApiResponse::errorGeneral(
                    trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
                );
            }

            $job = $this->repo->getById($input['job_id']);

            $response = $this->jobProjectService->qbSyncStatus($job);

            return ApiResponse::success([
                'data' => $response
            ]);
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Add job resource ids
     * Get jobs/select_list
     * @param  int $jobId Job Id
     * @return response
     */
    public function resourceIds($jobId)
    {
        $job = $this->repo->getById($jobId);

        return ApiResponse::success([
            'data' => [
                'workflow_resource_id' => $job->workflow->resource_id
            ]
        ]);
    }

    /**
     * get jobs with selected includes
     *
     * GET - /job_select_list
     *
     * @return response
     */
    public function jobSelectedList()
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $jobs = $this->service->getSelectedJobQueryBuilder($input);

        if (!$limit) {
            return ApiResponse::success(
                $this->response->collection($jobs->get(), new JobsSelectedListTransformer)
            );
        }

        $jobs = $jobs->paginate($limit);

        return ApiResponse::success(
            $this->response->paginatedCollection($jobs, new JobsSelectedListTransformer)
        );
    }

    /**
     * update job (selected fields)
     *
     * PUT - /jobs/{id}/update
     *
     * @param  $id
     * @return response
     */
    public function updateJob($id)
    {
        $input = Request::all();
        $job = $this->repo->getById($id);

        $validator = Validator::make($input, [
			'division_code' => 'AlphaNum|max:3',
            'purchase_order_number' => 'max:20',
			'insurance_details.upgrade' => 'regex:/^[+-]?\d+(\.\d+)?$/'
        ]);

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        try {
            $job = $this->service->updateSelectiveJobFields($job, $input);

            if(isset($input['rep_ids'])){
				$this->assignReps($job, $input['rep_ids']);
			}

            if(isset($input['estimator_ids'])){
				$this->assignEstimators($job, $input['estimator_ids']);
			}

            if(isset($input['sub_contractor_ids'])){
				$this->repo->assignSubContractors($job, (array)$input['sub_contractor_ids']);
			}

            Event::fire('JobProgress.Jobs.Events.Saved', new JobCreated($job));

        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Job']),
            'data' => $this->response->item($job, new JobsTransformer)
        ]);
    }

    /**
     * Print Single job Note
     * Get /job_notes/{note_id}/pdf_print
     * @return Response
     */
    public function jobNotePdfprint($noteId)
    {
        $input = Request::all();
        $note = $this->jobNotesRepo->getById($noteId);
        try{
            return $this->service->printSingleNote($note, $input);
        } catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
    }

    /**
     * get upcoming appointment and job schedule of a job
     *
     * GET - /jobs/{id}/upcoming_appointment_schedule
     *
     * @param  integer $id Id of a job
     * @return response
     */
    public function upcomingAppointmentAndSchedule($id)
    {
        $input = Request::all();
        $job = $this->repo->getById($id);
        $data = [];
        if(ine($input, 'upcoming_appointment')
            && ($appointment = $job->upcomingAppointments()->first())) {
            $transformer = new AppointmentsTransformer;
            $transformer->setDefaultIncludes([]);
            $data['upcoming_appointment'] = $this->response->item($appointment, $transformer);
        }
        if(ine($input, 'upcoming_schedule')
            && ($schedule = $job->upcomingSchedules()->first())) {
            $transformer = new JobScheduleTransformer;
            $transformer->setDefaultIncludes([]);
            $data['upcoming_schedule'] = $this->response->item($schedule, $transformer);
        }

        return ApiResponse::success(['data' => $data]);
    }

    /**
	 * Print Multiple Work Crew Note
	 * Get /work_crew_notes/pdf_print
	 * @return Response
	 */
	public function printMultipleNotes()
	{
		$input = Request::all();
		$validator = Validator::make($input, ['job_id' => 'required' ]);
		if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		$job = $this->repo->getById($input['job_id']);
		try {
			return $this->service->printMultipleNotes($job, $input);
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    public function getDivision($id)
	{
 		$data = null;
 		$job = $this->repo->getById($id);
		try{
			$division = $job->division;
 			if($division){
				$data = $this->response->item($division, new DivisionsTransformerOptimized);
			}

            return ApiResponse::success([
				'data' => $data
			]);
		} catch(\Exception $e){

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
		}
 	}

    /**
	 * Set Project Order
	 * Put jobs/{id}/set_order
	 * @param Json Response
	 */
	public function setProjectOrder($id)
	{
		$input = Request::all();

		$validator = Validator::make($input, [
			'display_order'	=> 'required|min:1|integer',
		]);

		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		try {
			$project = $this->repo->getProjectById($id);
			$this->service->setProjectOrder($project, $input['display_order']);

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Project order'])
			]);
		} catch(ModelNotFoundException $e) {

			return ApiResponse::errorNotFound('Project Not Found');
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

	}

	public function getJobWorkflowStages($id)
	{
		$job = $this->repo->getById($id);

		$workflowId = $job->workflow_id;

		$workflowStages = WorkflowStage::where('workflow_id', $workflowId)->get();

		return ApiResponse::success($this->response->collection($workflowStages, new WorkflowStagesTransformer));
	}

	public function getJobFinancialData($jobId)
	{
		$job = $this->repo->getById($jobId);

		if($job->isMultiJob()) {
			$jobId = $job->projects->pluck('id')->toArray();
			$job = Job::whereIn('jobs.id', $jobId);
		} else {
			$job = Job::where('jobs.id', $jobId);
		}

		$jobsFinancials = $job->selectRaw('
			sum(jobs.amount) as amount,
			sum((jobs.amount * jobs.tax_rate) / 100) as job_price_tax_amount
			')->first();

		$changeOrder = $job->leftjoin('change_orders', 'jobs.id', '=', 'change_orders.job_id');

		$changeOrderFinancials = $changeOrder->selectRaw(
			'sum(change_orders.total_amount) as change_order_amount,
			sum((change_orders.total_amount * change_orders.tax_rate) / 100) as change_order_tax_Amount
			')->whereNull('change_orders.canceled')
			->whereNull('change_orders.deleted_at')->first();

		return ApiResponse::success([
			'data' => [
				'job_price' 				=> (float)$jobsFinancials->amount,
				'job_price_tax_amount'		=> (float)$jobsFinancials->job_price_tax_amount,
				'change_order_amount'		=> (float)$changeOrderFinancials->change_order_amount,
				'change_order_tax_amount'	=> (float)$changeOrderFinancials->change_order_tax_Amount,
			]
		]);
	}

    public function linkJobFoldersWithSettingFolders($id)
	{
		$job = $this->repo->findById($id);

		DB::beginTransaction();
		try {
			$this->service->linkJobFoldersWithSettingFolders($job);
			DB::commit();
			$job = $this->repo->getJobById($job->id);

			return ApiResponse::success([
				'message' => "Job folders linked with new setting folders.",
				'data' => $this->response->item($job, new JobsTransformer)
			]);
		} catch (JobFoldersAlreadyLinkedException $e) {
			DB::rollBack();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			DB::rollBack();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /****************** Private Function *****************/

    private function validateJobs($jobs)
    {
        foreach ($jobs as $job) {
            $scope = [];
            $job['same_as_customer_address'] = array_get($job, 'same_as_customer_address', 1);
            if (ine($job, 'contact')) {
                $scope[] = 'contact';
            }

            $validator = Validator::make($jobs, Job::validationRules($scope));

            if ($validator->fails()) {
                return $validator;
            }
        }
        return false;
    }

    /**
     * Assign Reps
     * @param  [type] $job          [Job object]
     * @param  [array] $estimatorIds[estimator ids]
     * @param  [array] $repIds      [Reps ids]
     * @return [boolean]            [description]
     */
    private function assignReps($job, $repIds)
    {
        $assignedBy = \Auth::user();
        $oldReps = $job->reps()->pluck('rep_id')->toArray();
        $this->repo->assignReps(
            $job,
            $assignedBy,
            null,
            null,
            null,
            null,
            $repIds,
            $oldReps
        );
    }

    /**
     * Assign Labours
     * @param  [type] $job              [job]
     * @param  array $labourIds [ids of labours]
     * @param  array $subContractorIds [ids of subcontractors]
     * @return [type]                   [description]
     */
    private function assignLabours($job, $labourIds = [], $subContractorIds = [])
    {
        if ($subContractorIds) {
            $this->repo->assignSubContractors($job, $subContractorIds);
        }
    }

    /**
	 * Assign Labours
	 * @param  [type] $job              [job]
	 * @param  array $estimatorIds		[estimator ids]
	 * @return [type]                   [description]
	 */
	private function assignEstimators($job, $estimatorIds = array())
	{
		$assignedBy = \Auth::user();
		$oldReps = $job->estimators()->pluck('rep_id')->toArray();
		$this->repo->assignReps(
			$job,
			$assignedBy,
			null,
			null,
			$estimatorIds,
			$oldReps,
			null,
			null
			);
	}
}
