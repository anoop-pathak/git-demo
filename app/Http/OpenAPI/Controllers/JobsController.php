<?php

namespace App\Http\OpenAPI\Controllers;

use App\Http\Controllers\ApiController;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\JobNote;
use App\Http\OpenAPI\Transformers\JobsTransformer;
use App\Http\OpenAPI\Transformers\JobNotesTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\DB;
use Request;
use Queue;
use QBDesktopQueue;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use App\Exceptions\InvalidDivisionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Jobs\JobProjectService;
use App\Repositories\JobNotesRepository;
use App\Models\JobFinancialCalculation;
use App\Http\OpenAPI\Transformers\JobFinancialCalculationTransformer;

class JobsController extends ApiController
{

    protected $response;
    protected $repo;
    protected $jobProjectService;
    protected $jobNotesRepo;

    public function __construct(Larasponse $response, JobRepository $repo, JobProjectService $jobProjectService, JobNotesRepository $jobNotesRepo)
    {
        parent::__construct();

        $this->response = $response;
        $this->repo = $repo;
        $this->jobProjectService = $jobProjectService;
        $this->jobNotesRepo = $jobNotesRepo;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function index() {

		$input = Input::all();
		try{
			$jobs = $this->repo->getFilteredJobs($input);

            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

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

        $input['contact_same_as_customer'] = array_get($input, 'contact_same_as_customer', 1);

        $input['multi_job'] = array_get($input, 'multi_job', 0);

        if($input['multi_job'] == 1 || ine($input, 'parent_id')) {
            return ApiResponse::errorGeneral('Multi job can not be added.');
        }

        $scope = ['customerId'];

        if (ine($input, 'contact')) {
            $scope[] = 'contact';
        }

        if (ine($input, 'trades') && is_array($input['trades'])) {

            $input['trades']=array_filter($input['trades']);
        }

        if (!$input['same_as_customer_address']) {
            $scope[] = 'address';
        }

        $scope[] = 'open-api';

        if (ine($input, 'projects')) {
            $scope[] = 'projects';
            $scope['projects_count'] = count($input['projects']);
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

            DB::commit();
            QBDesktopQueue::addCustomerJobs($job->customer_id);

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => $attribute]),
                'job' => $this->response->item($job, new JobsTransformer)
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
     * Display the specified resource.
     * GET /jobs/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $input = Request::all();

        try{
            $job = $this->repo->getById($id);
            //Archived job
            if ($job->isArchived()) {
                return ApiResponse::errorGeneral(trans('response.error.archived_job_not_open'));
            }

            if (isset($input['track_job'])) {
                JobViewTrack::track($id);
            }

            if ($job->wp_job == true && $job->wp_job_seen == false) {
                $job->update(['wp_job_seen' => true]);
            }

            return ApiResponse::success([
                'data' => $this->response->item($job, new JobsTransformer)
            ]);
        } catch(InvalidDivisionException $e){
            DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(ModelNotFoundException $e) {
            DB::rollback();
            return ApiResponse::errorGeneral('No records found!');
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

        $scope = ['customerId'];
        $job = $this->repo->getById($id);

        $input = Request::all();
        $input['id'] = $id;

        if (ine($input, 'trades') && is_array($input['trades'])) {

            $input['trades'] = array_filter($input['trades']);
        }

        if (ine($input, 'contact')) {
            $scope[] = 'contact';
        }

        $scope[] = 'open-api';
        $validator = Validator::make($input, Job::openApiUpdateJobRules($scope));

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            
            $job = $this->jobProjectService->saveJobAndProjects($input);
            $attribute = 'Job';

            Queue::push('\App\Handlers\Events\JobQueueHandler@createHoverJob', ['job_id' => $job->id,
                'company_id' => $job->company_id
            ]);

            Queue::push('\App\Handlers\Events\JobQueueHandler@jobIndexSolr', ['job_id' => $job->id]);

            QBDesktopQueue::addJob($job->id);

            DB::commit();

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => $attribute]),
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
        Add Job Note
    */

    public function addNote($JobId)
    {
        $input = Request::all();

        $validator = Validator::make($input, ['note' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
            $job = $this->repo->findById($JobId);
            $createdBy = \Auth::id();
            $input['stage_code'] = isset($input['stage_code']) ? $input['stage_code'] : null;
        try{
            $note = $this->jobNotesRepo->saveNote($JobId, $input['note'], $input['stage_code'], $createdBy);

            return ApiResponse::success([
                'message' => trans('response.success.added', ['attribute' => 'Note']),
                'data' => $this->response->item($note, new JobNotesTransformer)
            ]);
        }catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
    }

    public function getJobNotes($jobId)
    {
        $input = Request::all();
        $job = $this->repo->findById($jobId);
        try{
            $input['job_id'] = $jobId;
            $notes = $this->jobNotesRepo->getFiltredNotes($input);
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
            $notes = $notes->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($notes, new JobNotesTransformer));

        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }

    }

    /**
        Edit Job Note
    */

    public function editNote($noteId)
    {
        $jobNote = $this->jobNotesRepo->getById($noteId);
        $input = Request::all();
        $validator = Validator::make($input, ['note' => 'required']);

        if ($validator->fails()) {

            return ApiResponse::validation($validator);
        }

        try {
            $note = $this->jobNotesRepo->updateNote($input['note'], \Auth::user()->id, $jobNote);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Note']),
                'data' => $this->response->item($note, new JobNotesTransformer)
            ]);
        } catch (\Exception $e) {

            return ApiResponse::errorInternal();
        }
    }

    /**
     * GET jobs/{id}/financial_details
     * @return Response json finacial calculations
     */
    public function getJobFinancials($id)
    {
        $job = $this->repo->getById($id);

        /* check job is awarded */
        if (!SecurityCheck::isJobAwarded($job)) {
            return ApiResponse::errorGeneral(trans('response.error.job_not_awarded'));
        }

        $financials = JobFinancialCalculation::where('job_id', $id)
            ->get();

        return ApiResponse::success($this->response->collection($financials, new JobFinancialCalculationTransformer));
    }

    /**
     * get jobs
     * GET - /jobs
     * 
     * @return Response
     */
    public function listing()
    {
        $input = Input::all();
        try{

            $jobs = $this->repo->getJobsForOpenAPI($input);

            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            $jobs = $jobs->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($jobs, new JobsTransformer));

        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());

        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
    }

}
