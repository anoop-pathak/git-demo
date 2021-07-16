<?php
namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\JobSchedule;
use App\Repositories\JobRepository;
use App\Services\JobSchedules\JobSchedulesService;
use App\Transformers\JobScheduleTransformer;
use App\Transformers\Optimized\JobProjectsTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Settings;
use App\Exceptions\InvalideAttachment;
use App\Exceptions\AccessForbiddenException;
use App\Repositories\JobSchedulesRepository;

class JobSchedulesController extends Controller
{
    protected $service;
    protected $response;

    public function __construct(
        JobSchedulesService $service,
        Larasponse $response,
        JobRepository $jobRepo,
        JobSchedulesRepository $schedulesRepo
    ) {

        $this->service = $service;
        $this->response = $response;
        $this->jobRepo = $jobRepo;
        $this->schedulesRepo = $schedulesRepo;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     * Get schedules
     * @return [type] [description]
     */
    public function index()
    {
        $input = Request::all();
        $schedules = $this->service->getSchedules($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $schedules = $schedules->get();
            $data = $this->response->collection($schedules, new JobScheduleTransformer);
        } else {
            $schedules = $schedules->paginate($limit);
            $data = $this->response->paginatedCollection($schedules, new JobScheduleTransformer);
        }

        // add color for events.
        $data['meta']['color'] = config('jp.events_color_code');

        return ApiResponse::success($data);
    }

    /**
     * Post schedules
     * @return [object] [schedule]
     */
    public function makeSchedule()
    {
        $input = Request::all();
        $validator = Validator::make($input, JobSchedule::getCreateRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            if (ine($input, 'full_day')) {
                $date = new Carbon($input['date'], Settings::get('TIME_ZONE'));
                $input['start_date_time'] = $date->toDateTimeString();
                $input['end_date_time'] = $date->addHours(23)->addMinutes(59)->toDateTimeString();
            }

            $createdBy = \Auth::id();
            $schedule = $this->service->makeSchedule(
                $input['title'],
                $input['start_date_time'],
                $input['end_date_time'],
                $createdBy,
                $input
            );

            if ($schedule->isEvent()) {
                $message = trans('response.success.created', ['attribute' => 'Event']);
            } else {
                $job = $schedule->job;
                $attribute = 'Job';
                if ($job->isProject()) {
                    $attribute = 'Project';
                }

                $message = trans('response.success.job_scheduled', ['attribute' => $attribute]);
            }
        } catch(InvalideAttachment $e){
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => $message,
            'job_schedule' => $this->response->item($schedule, new JobScheduleTransformer)
        ]);
    }

    /**
     * Put schedules/{id}
     * @param  [type] $id [JobSchedule Id]
     * @return [type]     [description]
     */
    public function updateSchedule($id)
    {
        $input = Request::all();

        $validator = Validator::make($input, JobSchedule::getUpdatedRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $schedule = $this->service->getById($id);
        DB::beginTransaction();
        try {
            if (ine($input, 'full_day')) {
                $date = new Carbon($input['date'], Settings::get('TIME_ZONE'));
                $input['start_date_time'] = $date->toDateTimeString();
                $input['end_date_time'] = $date->addHours(23)->addMinutes(59)->toDateTimeString();
            }
            $modifiedBy = \Auth::id();
            $input['old_recurring_id'] = $id;

            // set response message attribute..
            $attribute = 'Job';
            $job = $schedule->job;

            if (($job) && $job->isProject()) {
                $attribute = 'Project';
            }

            $schedule = $this->service->updateSchedule(
                $schedule,
                $input['title'],
                $input['start_date_time'],
                $input['end_date_time'],
                $modifiedBy,
                $input
            );

            $scheduleResponse = null;
            if ($schedule) {
                $scheduleResponse = $this->response->item($schedule, new JobScheduleTransformer);

                if ($schedule->isEvent()) {
                    $attribute = 'Event';
                } else {
                    $job = $schedule->job;
                    $attribute = 'Job Schedule';
                    if ($job->isProject()) {
                        $attribute = 'Project Schedule';
                    }
                }
            }
        } catch(InvalideAttachment $e){
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => $attribute]),
            'job_schedule' => $scheduleResponse
        ]);
    }

    /**
     * DELETE schedule/{id}
     * @param  [type] $id [job schedule id]
     * @return [type]     [description]
     */
    public function deleteSchedule($id)
    {
        $input = Request::onlyLegacy('only_this');
        $schedule = $this->service->getById($id);

        DB::beginTransaction();
        try {
            $job = $schedule->job;

            $schedule = $this->service->deleteSchedule($schedule, $input);

            // set response message attribute..
            if ($schedule->isEvent()) {
                $message = trans('response.success.deleted', ['attribute' => 'Event']);
            } else {
                $job = $schedule->job;
                $attribute = 'Job';
                if ($job->isProject()) {
                    $attribute = 'Project';
                }

                $message = trans('response.success.job_scheduled_removed', ['attribute' => $attribute]);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => $message,
            'job_schedule' => $this->response->item($schedule, new JobScheduleTransformer)
        ]);
    }

    /**
     * get print(pdf) of job schedule
     *
     * GET schedules/{id}/pdf_print
     * @param  $id [int] [job schedule id]
     * @return pdf
     */
    public function printSchedule($id)
    {
        $schedule = $this->service->getById($id);
        $input = Request::onlyLegacy('save_as_attachment');
        try {
            if (!ine($input, 'save_as_attachment')) {
                return $this->service->printSchedule($id);
            }

            $attachment = $this->service->printSchedule($id, $input);

            return ApiResponse::success([
                'message' => Lang::get('response.success.file_uploaded'),
                'file' => $attachment,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get print(pdf) of multiple schedulea
     *
     * GET schedules/pdf_print
     * @param
     * @return pdf
     */
    public function printMultipleSchedules()
    {
        try {
            $input = Request::all();

            $validator = Validator::make($input, JobSchedule::getMultipleScheduleRules());
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            if (!ine($input, 'save_as_attachment')) {
                return $this->service->printMultipleSchedules($input);
            }

            $attachment = $this->service->printMultipleSchedules($input);

            return ApiResponse::success([
                'message' => Lang::get('response.success.file_uploaded'),
                'file' => $attachment,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get unschedules_jobs/pdf_print
     * Unschedule job print
     * @return PDF
     */
    public function printUnscheduleJobs()
    {
        $input = Request::all();
        $input['without_schedules'] = true;
        $input['exclude_parent'] = true;
        try {
            if (!ine($input, 'save_as_attachment')) {
                return $this->service->printUnscheduleJobs($input);
            }

            $attachment = $this->service->printUnscheduleJobs($input);

            return ApiResponse::success([
                'message' => Lang::get('response.success.file_uploaded'),
                'file' => $attachment,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Jobs Count
     * @return [array] [description]
     */
    public function jobsCount()
    {
        $input = Request::all();
        // validation
        $validator = Validator::make($input, ['stage_code' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $data = $this->service->getJobCount($input);

        return ApiResponse::success([
            'data' => $data
        ]);
    }

    /**
     * [show description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function show($id)
    {
        $schedule = $this->service->getById($id);
        try {
            return ApiResponse::success([
                'schedule' => $this->response->item($schedule, new JobScheduleTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get /unschedules_jobs
     * @return Response
     */
    public function unscheduleJobs()
    {
        $input = Request::all();
        $input['without_schedules'] = true;
        $input['exclude_parent'] = true;
        $jobs = $this->jobRepo->getFilteredJobs($input);
        $jobs->attachCurrentStage();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $includes = [
            'address',
            'reps',
            'labours',
            'sub_contractors',
            'work_types',
            'customer'
        ];

        if(isset($input['includes'])) {
			$includes = array_merge((array)$input['includes'], $includes);
        }

		$this->response->parseIncludes($includes);

        $with = $this->getIncludes($input);
		$jobs = $jobs->with($with);

        if (!$limit) {
            $jobs = $jobs->get();

            return ApiResponse::success(
                $this->response->collection($jobs, new JobProjectsTransformer)
            );
        }
        $jobs = $jobs->paginate($limit);

        return ApiResponse::success(
            $this->response->paginatedCollection($jobs, new JobProjectsTransformer)
        );
    }

    /**
     * Move Schdule
     * Put /schedules/{id}/move
     * @param  Int $id Schedule Id
     * @return Response
     */
    public function move($id)
    {
        $schedule = $this->service->getById($id);
        $input = Request::onlyLegacy('start_date_time', 'end_date_time', 'date');
        $validator = Validator::make($input, JobSchedule::getMoveRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (ine($input, 'date')) {
            $date = new Carbon($input['date'], Settings::get('TIME_ZONE'));
            $input['start_date_time'] = $date->toDateTimeString();
            $input['end_date_time'] = $date->addHours(23)
                ->addMinutes(59)
                ->toDateTimeString();
        }

        $schedule = $this->service->move($schedule, $input['start_date_time'], $input['end_date_time'], $fullDay = (bool)$input['date']);

        $attribute = 'Event';
        if (($job = $schedule->job)) {
            $attribute = 'Job schedule';
            if ($job->isProject()) {
                $attribute = 'Project schedule';
            }
        }

        return ApiResponse::success([
            'message' => trans('response.success.moved', ['attribute' => $attribute]),
            'job_schedule' => $this->response->item($schedule, new JobScheduleTransformer)
        ]);
    }

    /**
     * Get Nearest Date
     * Get /schedules/get_nearest_date
     * @return Response
     */
    public function getNearestDate()
    {
        $input = Request::onlyLegacy('job_id', 'customer_id');
        $date = $this->service->getNearestDate($input);

        return ApiResponse::success(['data' => ['date' => $date]]);
    }

    /**
     * Attach work orders
     * Post /schedules/attach_work_orders
     * @return response
     */
    public function attachWorkOrders()
    {
        $input = Request::onlyLegacy('work_order_ids', 'schedule_id');

        $validator = Validator::make($input, JobSchedule::getWorkOrderAttachRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $schedule = $this->service->getById($input['schedule_id']);
        try {
            $this->service->attachWorkOrders($schedule, (array)$input['work_order_ids']);

            return ApiResponse::success([
                'message' => 'Work order(s) attached succesfully.',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Detach work orders
     * Delete /schedules/detach_work_orders
     * @return Response
     */
    public function detachWorkOrders()
    {
        $input = Request::onlyLegacy('work_order_ids', 'schedule_id');

        $validator = Validator::make($input, JobSchedule::getWorkOrderAttachRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $schedule = $this->service->getById($input['schedule_id']);
        try {
            $this->service->detachWorkOrders($schedule, (array)$input['work_order_ids']);

            return ApiResponse::success([
                'message' => 'Work order(s) detached succesfully.',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * schedule mark as completed
     *
     * @param $id
     * @return $schedule
     */
    public function markAsCompleted($id)
    {
        DB::beginTransaction();
        try {
            $inputs = Request::all();
            $validator  = Validator::make($inputs, JobSchedule::getAddCompletedAtRule());

            if($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $schedule = $this->service->markAsCompleted($id, $inputs);
            $message = 'Schedule marked as completed.';
            if(!$schedule->completed_at) {
                $message = 'Schedule marked as uncompleted.';
            }
            DB::commit();
            return ApiResponse::success([
                'message'   => $message,
                'data'      => $this->response->item($schedule, new JobScheduleTransformer),
            ]);
        } catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            DB::rollback();
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Schedule']));
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * getIncludes
     * @param  $input
     * @return $with
	 */
    public function getIncludes($input)
	{
        $with = [
            'subContractors',
            'workTypes',
            'address',
			'address.state',
			'address.country',
			'reps',
			'customer.phones',
			'trades',
			'projects',
			'sellingPriceWorksheet',
			'jobMeta',
			'projectStatus'
        ];

		if(!isset($input['includes'])) {
            return $with;
        }
    	$includes = (array)$input['includes'];

        if(in_array('parent', $includes)) {
            $with[] = 'parentJob';
        }

		if(in_array('parent.division', $includes)) {
            $with[] = 'parentJob.division';
        }

		return $with;
	}

    /**
	 * schedule mark as Accept
	 * 
	 * @param $id
	 * @return $schedule
	 */

	public function statusMarkAsAccept($id)
	{
		try {
			$inputs = Request::onlyLegacy('only_this');
			$validator 	= Validator::make($inputs, JobSchedule::getAddStatusRule());

			if($validator->fails()) {
				return ApiResponse::validation($validator);
			}

			if(!Settings::get('SHOW_SCHEDULE_CONFIRMATION_STATUS')) {
				return ApiResponse::errorForbidden();
			}

			$authUser = Auth::user();
			if ((!$authUser) || ($authUser->isSubContractor())) {
				return ApiResponse::errorForbidden();
			}

			$status = JobSchedule::ACCEPT_STATUS;
			$schedule = $this->repo->updateStatus($id, $authUser->id, $status, $inputs['only_this']);

			return ApiResponse::success([
				'message' => trans('response.success.status_accepted', ['attribute' => 'Job Schedule']),
			]);
		} catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){

			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Schedule']));
		} catch (AccessForbiddenException $e) {

    			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * schedule mark as decline
	 *
	 * @param $id
	 * @return $schedule
	 */
	public function statusMarkAsDecline($id)
	{
		try {
			$inputs = Request::onlyLegacy('only_this');
			$validator 	= Validator::make($inputs, JobSchedule::getAddStatusRule());

			if($validator->fails()) {
				return ApiResponse::validation($validator);
			}

			if(!Settings::get('SHOW_SCHEDULE_CONFIRMATION_STATUS')) {
				return ApiResponse::errorForbidden();
			};

			$authUser = Auth::user();
			if ((!$authUser) || ($authUser->isSubContractor())) {
				return ApiResponse::errorForbidden();
			}

			$status = JobSchedule::DECLINE_STATUS;
			$schedule = $this->repo->updateStatus($id, $authUser->id, $status, $inputs['only_this']);

			return ApiResponse::success([
				'message' =>  trans('response.success.status_decline', ['attribute' => 'Job Schedule']),
			]);
		} catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){

			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Schedule']));
		} catch (AccessForbiddenException $e) {

    			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * schedule mark as pending
	 *
	 * @param $id
	 * @return $schedule
	 */
	public function statusMarkAsPending($id)
	{
		try {
			$inputs = Request::onlyLegacy('only_this');
			$validator 	= Validator::make($inputs, JobSchedule::getAddStatusRule());

			if($validator->fails()) {
				return ApiResponse::validation($validator);
			}

			if(!Settings::get('SHOW_SCHEDULE_CONFIRMATION_STATUS')) {
				return ApiResponse::errorForbidden();
			}

			$authUser = Auth::user();
			if ((!$authUser) || ($authUser->isSubContractor())) {
				return ApiResponse::validation($validator);
			}

			$status = JobSchedule::PENDING_STATUS;
			$schedule = $this->repo->updateStatus($id, $authUser->id, $status, $inputs['only_this']);

			return ApiResponse::success([
				'message' => trans('response.success.status_pending', ['attribute' => 'Job Schedule']),
			]);
		} catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){

			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Schedule']));
		} catch (AccessForbiddenException $e) {

    			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}