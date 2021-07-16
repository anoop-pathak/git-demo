<?php

namespace App\Http\Controllers;

use App\Exceptions\AccessForbiddenException;
use App\Exceptions\InternalServerErrorException;
use App\Exceptions\PaymentRequredException;
use App\Exceptions\AuthorizationException;
use App\Models\ApiResponse;
use App\Models\JobMeta;
use App\Repositories\JobRepository;
use App\Services\CompanyCam\CompanyCamService;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\CompanyCam\TimeoutException;

class CompanyCamController extends ApiController
{

    protected $companyCamService;
    protected $jobRepo;

    public function __construct(CompanyCamService $companyCamService, JobRepository $jobRepo)
    {
        $this->companyCamService = $companyCamService;
        $this->jobRepo = $jobRepo;

        parent::__construct();
    }

    /**
     * Connect Company Cam Acount
     * POST /company_cam/connect
     *
     * @return Response
     */
    public function connect()
    {
        $input = Request::onlyLegacy('token', 'username');

        $validator = Validator::make($input, [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $this->companyCamService->connect($input['token'], $input['username']);

            return ApiResponse::success([

                'message' => 'Connected successfully.'
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral('CompanyCam Payment issue occured.');
        } catch (AccessForbiddenException $e) {
            return ApiResponse::errorGeneral("Token doesn't have sufficient permissions.");
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Disconnect Company Cam Acount
     * DELETE /company_cam/disconnect
     *
     * @return Response
     */
    public function disconnect()
    {
        $this->companyCamService->disconnect();

        return ApiResponse::success([

            'message' => 'Disconnected successfully.'
        ]);
    }

    /**
     * Create Company Cam Project
     *
     * @return Response
     */
    public function createProject()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $project = $this->companyCamService->createOrUpdateProject($input['job_id']);

            if(!$project) {
                return ApiResponse::errorNotFound('Job Not Found.');
            }

            $data = [
                JobMeta::COMPANY_CAM_ID => $project['id']
            ];
            return ApiResponse::success([
                'message' => trans('response.success.created', ['attribute' => 'Project']),
                'data'    => $data
            ]);
        } catch(UnauthorizedException $e) {
            return ApiResponse::errorGeneral(trans('response.error.unauthorised_third_party',['attribute' => 'CompanyCam']));
        }
    }

    /**
     * List Project photos
     * GET /company_cam/projects/photos
     *
     * @return Response
     */
    public function getProjectPhotos()
    {
        $input = Request::onlyLegacy('project_id', 'per_page', 'page');

        $validator = Validator::make($input, ['project_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $photos = $this->companyCamService->getProjectPhotos($input['project_id'], $input);

            return ApiResponse::success([

                'data' => $photos,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(TimeoutException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * All project list
     * GET /company_cam/projects
     *
     * @return Response
     */
    public function getProjectsList()
    {
        $input = Request::onlyLegacy('per_page', 'page', 'query');

        try {
            $projects = $this->companyCamService->getAllProjects($input);

            return ApiResponse::success([

                'data' => $projects,
                'params' => $input,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(TimeoutException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Get Single Project By Id
     * GET /company_cam/projects/{projectId}
     *
     * @return Response
     */
    public function getSingleProject($projectId)
    {
        try {
            $project = $this->companyCamService->getProjectById($projectId);

            return ApiResponse::success([

                'data' => $project,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * All company photos
     * GET /company_cam/get_all_photos
     *
     * @return Response
     */
    public function getAllPhotos()
    {
        $input = Request::onlyLegacy('project_ids', 'per_page', 'page');

        try {
            $projects = $this->companyCamService->getAllPhotos($input);

            return ApiResponse::success([

                'data' => $projects,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Save/Copy CompanyCam photo
     * POST /company_cam/save_photo
     *
     * @return Response
     */
    public function savePhoto()
    {
        $input = Request::onlyLegacy('photo_id', 'save_to');

        $validator = Validator::make($input, ['photo_id' => 'required', 'save_to' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $this->companyCamService->savePhoto($input['photo_id'], $input['save_to']);

            return ApiResponse::success(['message' => 'Photo saved successfully.']);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Link job to company cam
     * POST /company_cam/link_job
     *
     * @return Response
     */
    public function linkJob()
    {
        $input = Request::onlyLegacy('job_id', 'project_id');

        $validator = Validator::make($input, ['job_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->jobRepo->getById($input['job_id']);

        try {
            $companyCamId = $job->getMetaByKey(JobMeta::COMPANY_CAM_ID);

            if ($companyCamId) {
                return ApiResponse::errorGeneral('Job already linked to CompanyCam.');
            }

            if (!empty($input['project_id'])) {
                $projectAlreadyConnected = JobMeta::whereMetaKey(JobMeta::COMPANY_CAM_ID)
                    ->whereMetaValue($input['project_id'])
                    ->exists();

                if ($projectAlreadyConnected) {
                    return ApiResponse::errorGeneral('Selected project already linked to another job.');
                }

                $job->saveMeta(JobMeta::COMPANY_CAM_ID, $input['project_id']);

                goto SUCCESS_MESSAGE;
            }

            $this->companyCamService->createOrUpdateProject($job->id);

            SUCCESS_MESSAGE:
            return ApiResponse::success(['message' => 'Job successfully linked to CompanyCam.']);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (PaymentRequredException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InternalServerErrorException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Unlink job to company cam
     * Delete /company_cam/unlink_job
     *
     * @return Response
     */
    public function unlinkJob()
    {
        $input = Request::onlyLegacy('job_id');

        $validator = Validator::make($input, ['job_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->jobRepo->getById($input['job_id']);

        $companyCamLink = JobMeta::whereMetaKey(JobMeta::COMPANY_CAM_ID)
            ->whereJobId($job->id)
            ->first();

        if (!$companyCamLink) {
            return ApiResponse::errorGeneral('Job not linked to any CompanyCam project');
        }

        if ($companyCamLink->delete()) {
            return ApiResponse::success(['message' => 'CompanyCam project unlinked successfully.']);
        }

        return ApiResponse::errorInternal(trans('response.error.something_wrong'));
    }
}
