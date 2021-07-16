<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\ApiResponse;
use App\Models\Appointment;
use App\Models\Company;
use App\Repositories\AppointmentRepository;
use App\Services\Appointments\AppointmentService;
use App\Transformers\AppointmentsTransformer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use PDF;
use Settings;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\InvalideAttachment;
use Exception;

class AppointmentsController extends ApiController
{

    /**
     * Appointment Repo
     * @var \App\Repositories\AppointmentRepositories
     */
    protected $repo;

    /**
     * Display a listing of the resource.
     * GET /Appointments
     *
     * @return Response
     */
    protected $response;
    protected $calenderServices;

    public function __construct(Larasponse $response, AppointmentRepository $repo, AppointmentService $service)
    {
        $this->response = $response;
        $this->repo = $repo;
        $this->service = $service;
        parent::__construct();

        if (Request::get('includes')) {
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

        // if(isset($input['for']) && $input['for'] == 'users') {
        //  $input['users'] = isset($input['users']) ? $input['users'] : (array)\Auth::id();
        // }

        $appointments = $this->repo->getFilteredAppointments($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $settings =  new \App\Services\Settings\Settings(Auth::id(), getScopeId());
        if (!$limit) {
            $appointments = $appointments->get();
            $data = $this->response->collection($appointments, new AppointmentsTransformer);
            $data['filters']['setting_users'] = $this->repo->getSettingUsers();
            $data['filters']['settings'] = $settings->getSettingByKeys(['STAFF_CALENDAR_DIVISION_REMINDER', 'ST_CAL_OPT']);

            return ApiResponse::success($data);
        }
        $appointments = $appointments->paginate($limit);
        $data = $this->response->paginatedCollection($appointments, new AppointmentsTransformer);
        $data['filters']['setting_users'] = $this->repo->getSettingUsers();
        $data['filters']['settings'] = $settings->getSettingByKeys(['STAFF_CALENDAR_DIVISION_REMINDER', 'ST_CAL_OPT']);

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
        $data = $request->all();
        $data['created_by'] = \Auth::id();
        $appointment = $this->executeCommand('\App\Commands\AppointmentCommand', $data);

        if ($appointment) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Appointment']),
                'appointment' => $this->response->item($appointment, new AppointmentsTransformer)
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Update the specified resource in storage.
     * PUT /customers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update(AppointmentRequest $request, $id)
    {
        $appointment = $this->repo->getById($id);

        $data = $request->all();

        try {
            $data['id'] = $id;
            $data['previous_user_id'] = $appointment->user_id;
            $data['previous_attendees'] = $appointment->attendees->pluck('id')->toArray();
            $appointment = $this->executeCommand('\App\Commands\AppointmentCommand', $data);
            $appointmentResponse = null;
            if ($appointment) {
                $appointmentResponse = $this->response->item($appointment, new AppointmentsTransformer);
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Appointment']),
                'appointment' => $appointmentResponse
            ]);
        } catch(InvalideAttachment $e){

			return ApiResponse::errorGeneral($e->getMessage());
        } catch(ModelNotFoundException $e) {
            return ApiResponse::errorGeneral(trans('response.error.not_found', ['attribute' => 'Appointment']));
        }catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Show resource from storage.
     * GET /appointments/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $appointment = $this->repo->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($appointment, new AppointmentsTransformer)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /appointments/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $input = Request::onlyLegacy('only_this', 'impact_type');
        $appointment = $this->service->getById($id);
        try {
            if (ine($input, 'only_this')) {
                $input['impact_type'] = 'only_this';
            }

            $this->service->delete($appointment, $input['impact_type']);
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.deleted', ['attribute' => 'Appointment'])
        ]);
    }

    /**
     * Get Upcoming appointments counts
     * GET /appointments/count
     *
     * @return Response
     */
    public function appointmentsCount()
    {
        $input = Request::all();

        if (!isset($input['for']) || !isset($input['users'])) {
            $input['for'] = 'users';
            $input['users'] = (array)\Auth::id();
        }

        if (!isset($input['duration'])) {
            $input['duration'] = 'upcoming';
        }

        $input['appointment_counts_only'] = true;

        $appointments = $this->repo->getFilteredAppointments($input);

        $count = $appointments->count();

        return ApiResponse::success(['count' => $count]);
    }

    public function exportCsv()
	{
		$input = Request::all();

		return $this->service->exportCsv($input);
	}

    /**
     * Export Appoitmenrts in pdf formate
     * GET /appointments/export
     *
     * @return Response
     */
    public function pdf_print()
    {
        $input = Request::all();

        // if(isset($input['for']) && $input['for'] == 'users') {
        //  $input['users'] = isset($input['users']) ? $input['users'] : (array)\Auth::id();
        // }

        $appointments = $this->repo->getFilteredAppointments($input);
        $appointments = $appointments->with([
            'customer.secondaryNameContact',
            'jobs' => function($query) {
                if(\Auth::user()->isSubContractorPrime()) {
                    $query->own(\Auth::id());
                }
            },
        ])->get();
        $scope = App::make(\App\Services\Contexts\Context::class);
        $company = Company::find($scope->id());

        $contents = view('appointments.appointments-list', [
            'appointments' => $appointments,
            'company' => $company,
            'filters' => $input,
            'company_country_code' => $company->country->code
        ])->render();

        $pdf = PDF::loadHTML($contents)
            ->setPaper('a4')
            ->setOption('no-background', true)
            ->setOption('dpi', 200);

        return $pdf->stream('appointments.pdf');
    }

    /**
     * Get appointments/{id}/pdf_print
     * @param  [int] $id [description]
     * @return Pdf
     */
    public function singlePdfPrint($id)
    {
        $appointment = $this->repo->getById($id);
        $fileName ='appointment.pdf';
        $company = $appointment->company;

        $input 	  = Request::onlyLegacy('save_as_attachment');

        $contents = view('appointments.appointment', [
            'appointment' => $appointment,
            'company' => $company,
            'company_country_code' => $company->country->code
        ])->render();

        if(!ine($input, 'save_as_attachment')) {
            $pdf = PDF::loadHTML($contents)
                ->setPaper('a4')
                ->setOption('dpi', 200);

            return $pdf->stream($fileName);
        }
        try{
            $attachment = $this->service->saveAsAttachment($contents, $fileName);
			return ApiResponse::success([
				'message' => trans('response.success.file_uploaded'),
				'file' 	  => $attachment,
			]);
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
     * Get Nearest Date
     * Get appointments/get_nearest_date
     * @return Response
     */
    public function getNearestDate()
    {
        $input = Request::onlyLegacy('job_id', 'customer_id');
        $date = $this->service->getNearestDate($input);

        return ApiResponse::success(['data' => ['date' => $date]]);
    }

    /**
     * Move Appointment
     * Get appointments/{id}/move
     * @param  Int $id Appointment Id
     * @return Json Appointment Data
     */
    public function move($id)
    {
        $appointment = $this->service->getById($id);
        $input = Request::onlyLegacy('start_date_time', 'end_date_time', 'date', 'attendees', 'user_id');
        $validator = Validator::make($input, Appointment::getMoveRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            DB::beginTransaction();

            //get appointment start date and end date
            if (ine($input, 'date')) {
                $date = new Carbon($input['date'], Settings::get('TIME_ZONE'));
                $input['start_date_time'] = $date->toDateTimeString();
                $input['end_date_time'] = $date->addHours(23)
                    ->addMinutes(59)
                    ->toDateTimeString();
            }

            $appointment = $this->service->move(
                $appointment,
                $input['start_date_time'],
                $input['end_date_time'],
                $fullDay = (bool)$input['date'],
                $input
            );
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.moved', ['attribute' => 'Appointment']),
            'data' => $this->response->item($appointment, new AppointmentsTransformer)
        ]);
    }

    /**
     * PUT appointment/{id}/result
     * add appointment result
     * @param $id
     */
    public function addResult($id)
    {
        $input      = Request::onlyLegacy('result_option_id', 'result', 'result_option_ids');
        $validator  = Validator::make($input, Appointment::getAddResultRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $appointment = $this->service->getById($id);
        // check result option
        $resOptionRepo = App::make('App\Repositories\AppointmentResultOptionRepository');
        $resOptionRepo->getById($input['result_option_id']);


        try {
            $appointment = $this->service->addResult($appointment, $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => 'Result Updated Successfully.',
            'data' => $this->response->item($appointment, new AppointmentsTransformer),
        ]);
    }

    /**
    * mark as completed appointment
    *
    * @param $id(appointment id)
    * @return appointment
    */
    public function markAsCompleted($id)
    {
        try {
            $inputs = Request::all();
            $validator  = Validator::make($inputs, Appointment::getAddCompletedAtRule());
            if($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $appointment = $this->service->markAsCompleted($id, $inputs);
            $message = 'Appointment marked as completed.';
            if(!$appointment->completed_at) {
                $message = 'Appointment marked as uncompleted.';
            }
            
            return ApiResponse::success([
                'message'   => $message,
                'data'      => $this->response->item($appointment, new AppointmentsTransformer),
            ]);
        }catch(ModelNotFoundException $e){
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Appointment']));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        
    }
}
