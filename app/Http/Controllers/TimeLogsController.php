<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadyCheckInException;
use App\Exceptions\CheckInNotFoundException;
use App\Models\ApiResponse;
use App\Models\TimeLog;
use App\Services\TimeLogs\TimeLogServices;
use App\Transformers\TimeLogEntryTransformer;
use App\Transformers\TimeLogSummaryTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Validator;
use Excel;
use Sorskod\Larasponse\Larasponse;
use Settings;
use App\Models\Division;
use App\Models\User;

class TimeLogsController extends Controller
{

    public function __construct(Larasponse $response, TimeLogServices $service)
    {
        $this->service = $service;
        $this->response = $response;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     * Timelog listing
     * Get timelogs/listing
     * @return response
     */

    public function listing()
    {
        $input = Request::all();
        $validator = Validator::make($input, TimeLog::getListingRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $subGroup = issetRetrun($input, 'sub_group');
		$timelogs = $this->service->getTimelogsSummary($input['group'], $subGroup, $limit, $input);

        $trans = new TimeLogSummaryTransformer;
        if (!$limit) {
            return ApiResponse::success($this->response->collection($timelogs, $trans));
        }

        return ApiResponse::success($this->response->paginatedCollection($timelogs, $trans));
    }

    /**
     * Get TimeLogs Entries
     * Get timelogs/entries
     * @return Response
     */
    public function getLogEntries()
    {
        $input = Request::all();

        if (!\Auth::user()->isAuthority()) {
            unset($input['user_id']);
            $input['user_id'] = \Auth::id();
        }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $timeLogs = $this->service->getLogEntries($input);

        if(ine($input, 'csv_export')) {
            return $this->service->csvExport($timeLogs->get(), $input);
       }


        if (!$limit) {
            $timeLogs = $timeLogs->get();

            return ApiResponse::success(
                $this->response->collection($timeLogs, new TimeLogEntryTransformer)
            );
        }

        $timeLogs = $timeLogs->paginate($limit);

        return ApiResponse::success(
            $this->response->paginatedCollection($timeLogs, new TimeLogEntryTransformer)
        );
    }

    /**
     * Check In
     * POST timelogs/check_in
     * @return Response
     */
    public function checkIn()
    {
        $input = Request::onlyLegacy('job_id', 'location', 'clock_in_note', 'check_in_image', 'lat', 'long');

        if($input['job_id']) {
			$job = Job::where('company_id', getScopeId())->findOrFail($input['job_id']);
		}

        $validator = Validator::make($input, TimeLog::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $startDateTime = Carbon::now()->second(0)->format('Y-m-d H:i:s');
            $timelog = $this->service->checkIn(
                $input['job_id'],
                $startDateTime,
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.check_in'),
                'data' => $this->response->item($timelog, new TimeLogEntryTransformer)
            ]);
        } catch (AlreadyCheckInException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * CheckOut
     * Post /timelogs/check_out
     * @return Response
     */
    public function checkOut()
    {
        $input = Request::onlyLegacy('check_out_image', 'clock_out_note', 'check_out_location');
        $validator = Validator::make($input, TimeLog::getCheckOutRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $endDateTime = Carbon::now()->second(0)->format('Y-m-d H:i:s');
            $ttLog = $this->service->checkOut($endDateTime, $input['check_out_image'], $input['clock_out_note'], $input['check_out_location']);

            return ApiResponse::success([
                'message' => trans('response.success.check_out'),
                'data' => $this->response->item($ttLog, new TimeLogEntryTransformer)
            ]);
        } catch (CheckInNotFoundException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get TimeLog by Job Id
     * Get /timelogs/{id}
     * @param  int $id TimeLog Id
     * @return response
     */
    public function show($id)
    {
        $timeLog = $this->service->getById($id);
        try {
            return ApiResponse::success([
                'data' => $this->response->item($timeLog, new TimeLogEntryTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get Current User CheckIn
     * Get /timelogs/user_check_in
     * @return Response
     */
    public function getCurrentUserCheckIn()
    {
        try {
            $timeLog = $this->service->getCurrentUserCheckIn();

            return ApiResponse::success([
                'data' => $this->response->item($timeLog, new TimeLogEntryTransformer)
            ]);
        } catch (CheckInNotFoundException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * export csv
     *
     * GET - /timelogs/csv_export
     *
     * @return response
     */
    public function exportCsv()
    {
        $input = Request::all();

        if (!\Auth::user()->isAuthority()) {
            unset($input['user_id']);
            $input['user_id'] = \Auth::id();
        }

        $timeLogs = $this->service->getLogEntries($input);

        return $this->service->csvExport($timeLogs->get(), $input);
    }

    /**
	 * duration of entries
	 *
	 * GET - /timelogs/duration
	 *
	 * @return response
	 */
	public function duration()
	{
		$input = Request::all();

		$timelogs = $this->service->duration($input);

		$timelogs = $timelogs->first();

		$data = [
			'duration' => durationFromSeconds($timelogs->duration)
		];

		return ApiResponse::success([
			'data' => $data,
		]);
	}

	private function getFilters($inputs){
		$filters = 'Filters:';
		// Filters: Division:A,B,C| Trade:Roofing

		if(ine($inputs, 'start_date')){
			$filters .= ' Start Date: '.$inputs['start_date'].'|';
		}

		if(ine($inputs, 'end_date')){
			$filters .= ' End Date: '.$inputs['end_date'].'|';
		}

		if(ine($inputs, 'group')){
			$filters .= ' View By: '.$inputs['group'].'|';
		}

		if(ine($inputs, 'division_id')){
			$names = Division::where('company_id', getScopeId())
				->whereIn('id', $inputs['division_id'])
				->pluck('name')
                ->toArray();

			if(!empty($names)){
				$names = implode(', ', $names);
				$filters .= ' Division: '.$names.'|';
			}
		}

		if(ine($inputs, 'user_id')){
			$userNames = User::where('company_id', getScopeId())
				->whereIn('id', $inputs['user_id'])
				->selectRaw('CONCAT(first_name, last_name) AS full_name')
				->pluck('users.full_name')
                ->toArray();

			if(!empty($userNames)){
				$userNames = implode(', ', $userNames);
				$filters .= ' Users: '.$userNames.'|';
			}
		}

		return array($filters);
	}
}
