<?php

namespace App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Repositories\AppointmentRepository;
use App\Http\OpenAPI\Transformers\AppointmentsTransformer;
use App\Http\OpenAPI\Transformers\AppointmentResultOptionsTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Models\Appointment;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Repositories\UserRepository;
use App\Repositories\JobRepository;
use App\Http\Requests\AppointmentRequest;
use App\Services\Appointments\AppointmentService;
use Illuminate\Support\Facades\App;
use App\Models\AppointmentResultOption;
use App\Repositories\AppointmentResultOptionRepository;

class AppointmentsController extends ApiController
{
	/**
	 * Appointment Repo
	 * @var \App\Repositories\AppointmentRepositories
	 */
	protected $repo;
	protected $jobRepo;
	protected $response;
	protected $userRepo;
	protected $service;
	protected $resultOptionRepo;

	public function __construct(Larasponse $response, AppointmentRepository $repo, JobRepository $jobRepo, UserRepository $userRepo, AppointmentService $service, AppointmentResultOptionRepository $resultOptionRepo)
	{
		$this->response = $response;
		$this->repo = $repo;
		$this->jobRepo = $jobRepo;
		$this->userRepo = $userRepo;
		$this->service = $service;
		$this->resultOptionRepo = $resultOptionRepo;
		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	/**
	 * Display a listing of the resource.
	 * GET /appointments
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();

		$appointments = $this->repo->getFilteredAppointments($input);
		
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$appointments = $appointments->paginate($limit);
		
		$data = $this->response->paginatedCollection($appointments, new AppointmentsTransformer);

		return ApiResponse::success($data);
	}

	/**
     * Store a newly created resource in storage.
     * POST /appointments
     *
     * @return Response
     */
    public function store(AppointmentRequest $request)
	{
		$input = $request->all();

		if(ine($input, 'user_id')) {
			$user = $this->userRepo->getById($input['user_id']);
		}

		if(ine($input, 'job_ids')) {
			$job = Job::whereNull('parent_id')->where('company_id', getScopeId())->findOrFail($input['job_ids']);
		}

		if(ine($input, 'attendees')) {
			$attendees = User::where('company_id', getScopeId())->findOrFail($input['attendees']);
		}

		$input['created_by'] = \Auth::id();
		$appointment = $this->executeCommand('\App\Commands\AppointmentCommand', $input);

		if($appointment) {
			return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Appointment']),
				'appointment' => $this->response->item($appointment, new AppointmentsTransformer)
			]);
		}

		return ApiResponse::errorInternal();
	}

    /**
     * Update the specified resource in storage.
     * PUT /appointments/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update(AppointmentRequest $request, $id)
	{
		$appointment = $this->repo->getById($id);

		$input = $request->all();

		if(ine($input, 'user_id')) {
			$user = $this->userRepo->getById($input['user_id']);
		}

		if(ine($input, 'job_ids')) {
			$job = Job::whereNull('parent_id')->where('company_id', getScopeId())->findOrFail($input['job_ids']);
		}

		if(ine($input, 'attendees')) {
			$attendees = User::where('company_id', getScopeId())->findOrFail($input['attendees']);
		}

		try {
			$input['id'] = $id;
			$input['previous_user_id'] = $appointment->user_id;
			$input['previous_attendees'] = $appointment->attendees->pluck('id')->toArray();
			$appointment = $this->executeCommand('\App\Commands\AppointmentCommand',$input);
			$appointmentResponse = null;

			if($appointment) {
				$appointmentResponse = $this->response->item($appointment, new AppointmentsTransformer);
			}

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Appointment']),
				'appointment' => $appointmentResponse
			]);
		} catch(ModelNotFoundException $e) {
			return ApiResponse::errorGeneral(trans('response.error.not_found', ['attribute' => 'Appointment']));
		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

	}

	/**
     * PUT appointment/{id}/result
     * add appointment result
     * @param $id
     */
    public function addResult($id)
    {
        $input = Request::onlyLegacy('result_option_id', 'result');
        $validator = Validator::make($input, [
        	'result_option_id' => 'required',
        	'result' => 'required|array'
        ]);

        if ($validator->fails()) {
        	return ApiResponse::validation($validator);
        }

        $resultOption = $this->resultOptionRepo->getById($input['result_option_id']);
        $appointment = $this->service->getById($id);
		$input['result_option_ids'] = $appointment->result_option_ids;

		if(empty($appointment->result_option_ids)) {
			$activeOptions = AppointmentResultOption::where('company_id', getScopeId())
				->where('active', true)
				->pluck('id')
				->toArray();
			$input['result_option_ids'] = $activeOptions;
			if(!$resultOption->active) {
	
				return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Appointment Result']));
			}
		}

        if( !in_array($input['result_option_id'], $input['result_option_ids']) ) {

        	return ApiResponse::errorGeneral('This appointment result is not supporting in this appointment.');
        }

        $rules = Appointment::getOpenAPIAddResultRules($resultOption->fields);
        $validator  = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $appointment = $this->service->addResult($appointment, $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' =>  trans('response.success.updated', ['attribute' => 'Appointment Result']),
            'data'    => $this->response->item($appointment, new AppointmentsTransformer),
        ]);
    }

    public function getResult($id)
    {
    	$appointment = $this->repo->getById($id);

    	$data = [];
    	if($appointment->result_option_id && !empty( $appointment->result )) {
    		$data['result_option_id'] = $appointment->result_option_id;
    		$data['result'] = array_values($appointment->result);
    	}
    	return ApiResponse::success(['data' => $data]);
    }

    public function getAvailableResultOptions($id)
    {
    	$appointment = $this->repo->getById($id);
    	$resultOptionIds = $appointment->result_option_ids;

    	if ($resultOptionIds) {
    		$availableResultOptions = AppointmentResultOption::whereIn('id', $resultOptionIds)->get();
    	} else {
    		$availableResultOptions = AppointmentResultOption::where('company_id', getScopeId())->where('active', true)->get();
    	}

    	$data = $this->response->collection($availableResultOptions, new AppointmentResultOptionsTransformer);

		return ApiResponse::success($data);
    }
}