<?php

namespace App\Http\Controllers;

use App\Exceptions\EmailAlreadyExistsExceptions;
use App\Exceptions\FileNotFoundExceptions;
use App\Exceptions\InvalidResourcePathException;
use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\JobNote;
use App\Models\JobSchedule;
use App\Models\SubContractorInvoice;
use App\Repositories\JobNotesRepository;
use App\Repositories\JobRepository;
use App\Repositories\JobSchedulesRepository;
use App\Repositories\SubContractorInvoiceRepository;
use App\Repositories\WorkCrewNoteRepository;
use App\Services\Contexts\Context;
use App\Services\Resources\ResourceServices;
use App\Services\SubContractors\SubContractorFilesService;
use App\Services\SubContractors\SubContractorService;
use App\Transformers\JobNotesTransformer;
use App\Transformers\Optimized\JobProjectsTransformer;
use App\Transformers\ResourcesTransformer;
use App\Transformers\SubContractorScheduleTransformer;
use App\Transformers\WorkCrewNotesTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\DB;

class SubContractorsController extends ApiController
{

    protected $jobScheduleRepository;
    protected $response;
    protected $resourceService;
    protected $service;
    protected $filesService;
    protected $invoiceRepo;
    protected $resourceRepo;
    protected $scope;
    protected $jobRepo;
    protected $jobNotesRepo;
    protected $wcNotesRepo;

    /**
     * Class Constructor
     * @param    $jobScheduleRepository
     * @param    $response
     * @param    $resourceService
     * @param    $service
     * @param    $invoiceRepo
     */
    public function __construct(
        Larasponse $response,
        SubContractorInvoiceRepository $invoiceRepo,
        JobSchedulesRepository $jobScheduleRepository,
        ResourceServices $resourceService,
        SubContractorFilesService $filesService,
        SubContractorService $service,
        Context $scope,
        JobRepository $jobRepo,
        JobNotesRepository $jobNotesRepo,
        WorkCrewNoteRepository $wcNotesRepo
    ) {

        $this->response = $response;
        $this->invoiceRepo = $invoiceRepo;
        $this->filesService = $filesService;
        $this->service = $service;
        $this->resourceService = $resourceService;
        $this->jobScheduleRepository = $jobScheduleRepository;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->jobNotesRepo = $jobNotesRepo;
        $this->wcNotesRepo = $wcNotesRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * GET /unschedule_jobs
     * get un_scheduled job list
     * @return response
     */
    public function listUnScheduledJobs()
    {
        $input = Request::all();
        $userId = \Auth::id();
        $limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');

        $input['without_schedules'] = true;
        $input['exclude_parent'] = true;

        unset($input['sub_ids']);
        $input['sub_ids'] = $userId;

        $jobList = $this->jobRepo->getFilteredJobs($input);

        $transFormer = new JobProjectsTransformer;

        $transFormer->setDefaultIncludes(['customer', 'trades', 'current_stage', 'work_types',]);

        if (!$limit) {
            $jobList = $jobList->get();
            return ApiResponse::success($this->response->collection($jobList, $transFormer));
        }

        $jobList = $jobList->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($jobList, $transFormer));
    }

    /**
     * GET /schedules
     * list job schedules
     * @return response
     */
    public function listJobScheduleds()
    {
        try {
            $input = Request::all();
            $userId = \Auth::id();

            $limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');

            unset($input['sub_ids']);
            $input['sub_ids'] = $userId;

            $scheduleList = $this->jobScheduleRepository->getSchedules($input);

            if (!$limit) {
                $scheduleList = $scheduleList->get();
                return ApiResponse::success($this->response->collection($scheduleList, new SubContractorScheduleTransformer));
            }

            $scheduleList = $scheduleList->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($scheduleList, new SubContractorScheduleTransformer));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * GET /schedules/{scheduleId}
     * get job_schedules by id
     * @return response
     */
    public function getJobScheduleById($scheduleId)
    {
        try {
            $jobSchedule = $this->jobScheduleRepository->getById($scheduleId);

            return ApiResponse::success($this->response->item($jobSchedule, new SubContractorScheduleTransformer));
        } catch (\Exception $e) {
        }
    }

    /**
     * POST /create_invoices
     * create invoice
     * @return response
     */
    public function createInvoice()
    {
        $input = Request::onlyLegacy('file', 'job_id', 'job_schedule_id');

        $validator = Validator::make($input, SubContractorInvoice::createInvoiceRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($input['job_id']);

        // find recurring job schedule
        $jobSchedule = JobSchedule::recurring()
            ->where('schedule_recurrings.id', $input['job_schedule_id'])
            ->firstOrFail();

        try {
            $invoice = $this->filesService->createInvoice($job, $jobSchedule, $input['file'], $input);

            return ApiResponse::success([
                'message' => trans('response.success.created', ['attribute' => 'Invoice']),
                'data' => $this->response->item($invoice, new ResourcesTransformer),
            ]);
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * GET /invoices_list
     * get invoice list
     * @todo  Create job and schedule filters
     * @return [type] [description]
     */
    public function invoiceList()
    {
        $input = Request::onlyLegacy('limit', 'job_id', 'schedule_id');
        $validator = Validator::make($input, ['job_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($input['job_id']);
        $limit = $input['limit'] ? $input['limit'] : config('jp.pagination_limit');

        try {
            $invoices = $this->filesService->getInvoices($job);

            if (!$invoices) {
                return ApiResponse::success(['data' => []]);
            }

            if (!$limit) {
                $invoices = $invoices->get();

                return ApiResponse::success(
                    $this->response->collection($invoices, new ResourcesTransformer)
                );
            }

            $invoices = $invoices->paginate($limit);

            return ApiResponse::success(
                $this->response->paginatedCollection($invoices, new ResourcesTransformer)
            );
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * DELETE /delete_invoice/{id}
     * delete invoice
     * @param  $id
     * @return response
     */
    public function deleteInvoice($id)
    {
        $invoice = $this->resourceService->getById($id);

        if ($invoice && ($invoice->created_by !== \Auth::id())) {
            return ApiResponse::errorGeneral(trans('response.error.not_found', ['attribute' => 'File']));
        }

        try {
            $invoice = $this->resourceService->removeFile($id);

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Invoice']),
            ]);
        } catch (FileNotFoundException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * POST /upload_file
     * upload file
     * @return response
     */
    public function uploadFile()
    {
        $input = Request::onlyLegacy('file', 'job_id');

        $validator = Validator::make($input, SubContractorInvoice::uploadFileRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($input['job_id']);

        try {
            $file = $this->filesService->uploadFile($job, $input['file']);

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded', ['attribute' => 'File']),
                'file' => $this->response->item($file, new ResourcesTransformer),
            ]);
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * GET /files_list
     * get files
     * @return response
     */
    public function getFiles()
    {
        $input = Request::onlyLegacy('limit', 'job_id');

        $validator = Validator::make($input, ['job_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($input['job_id']);


        try {
            $files = $this->filesService->getFiles($job);

            if (!$files) {
                return ApiResponse::success(['data' => []]);
            }

            $limit = $input['limit'] ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $files = $files->get();
                return ApiResponse::success($this->response->collection($files, new ResourcesTransformer));
            }

            $files = $files->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($files, new ResourcesTransformer));
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage(), $e);
        }
    }

    /**
     * DELETE delete_file/{id}
     * delete file
     * @todo  Subcontractor can delete files he uploaded
     * @param  $id
     * @return response
     */
    public function deleteFile($id)
    {
        $file = $this->resourceService->getById($id);

        if ($file && ($file->created_by !== \Auth::id())) {
            return ApiResponse::errorGeneral(trans('response.error.not_found', ['attribute' => 'File']));
        }

        try {
            $file = $this->resourceService->removeFile($id);

            return ApiResponse::success(['message' => trans('response.success.deleted', ['attribute' => 'File'])]);
        } catch (FileNotFoundException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * POST share_files
     * @todo  Share file according to sub_id and job_id
     * @return [type] [description]
     */
    public function shareFilesWithSubContrator()
    {
        $input = Request::onlyLegacy('file_ids', 'sub_id', 'job_id');

        $rules = [
            'file_ids' => 'array',
            'sub_id' => 'required_with:file_ids',
            'job_id' => 'required_with:file_ids'
        ];

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($input['job_id']);

        try {
            $file = $this->filesService->shareFilesWithSubContrator($job, $input['sub_id'], $input['file_ids']);

            return ApiResponse::success(['message' => trans('response.success.copied', ['attribute' => 'File'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * POST /create_share_dir
     * @todo  Create common function in service to get/create subrootDir
     * @return [type] [description]
     */
    public function createSubDir()
    {
        $input = Request::onlyLegacy('job_id', 'sub_ids');

        $rules = [
            'job_id' => 'required',
            'sub_ids' => 'required_with:job_id|array',
        ];

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($input['job_id']);

        try {
            $dir = $this->filesService->createSubDir($job, $input['sub_ids']);

            return ApiResponse::success(['message' => trans('response.success.created', ['attribute' => 'Directory (s)'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * POST users/sub_contractor/set_password
     * set sub contractor password
     * @return  response
     */
    public function setSubContractorPassword()
    {
        DB::beginTransaction();
        try {
            $input = Request::onlyLegacy('email', 'password', 'sub_id', 'send_mail');
            $rules = [
                'sub_id' => 'required|integer',
                'email' => 'required|email',
                'password' => 'required',
            ];
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            $subContractor = $this->service->setSubContractorPassword($input);

            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Password']),
                'data' => $subContractor
            ]);
        } catch (EmailAlreadyExistsException $e) {
            DB::rollback();
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal($e->getMessage());
        }
    }

    /**
     * POST - /add_job_notes
     * add job notes
     * @return response
     */
    public function addJobNotes()
    {
        $input = Request::all();
        $validator = Validator::make($input, JobNote::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $createdBy = \Auth::id();
        $input['stage_code'] = isset($input['stage_code']) ? $input['stage_code'] : null;

        $note = $this->jobNotesRepo->saveNote($input['job_id'], $input['note'], $input['stage_code'], $createdBy);

        if ($note) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.added', ['attribute' => 'Note']),
                'data' => $this->response->item($note, new JobNotesTransformer)
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * GET - /get_job_notes
     * get job notes
     * @return response
     */
    public function getJobNotes()
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

    /**
     * GET - /job/{jobId}
     * get job by id
     * @param  Job $id
     * @return response
     */
    public function getJobById($jobId)
    {
        $filters['sub_ids'] = \Auth::id();
        $filters['include_projects'] = true;

        $transFormer = new JobProjectsTransformer;

        $transFormer->setDefaultIncludes(['customer', 'trades', 'current_stage', 'work_types',]);

        $job = $this->jobRepo->getJobsQueryBuilder($filters)->whereId($jobId)->firstOrFail();

        return ApiResponse::success($this->response->item($job, $transFormer));
    }

    /**
     * GET - /get_wrok_crew_notes
     * get work crew notes
     * @return response
     */
    public function getWorkCrewNotes()
    {
        $input = Request::onlyLegacy('job_id', 'limit');

        $validator = Validator::make($input, ['job_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $input['sub_ids'] = \Auth::id();

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $wcNotes = $this->wcNotesRepo->getFilteredNotes($input['job_id'], $input);

        if (!$limit) {
            return ApiResponse::success($this->response->collection($wcNotes->get(), new WorkCrewNotesTransformer));
        }

        $wcNotes = $wcNotes->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($wcNotes, new WorkCrewNotesTransformer));
    }
}
