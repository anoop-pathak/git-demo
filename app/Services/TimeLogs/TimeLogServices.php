<?php

namespace App\Services\TimeLogs;

use App\Exceptions\AlreadyCheckInException;
use App\Exceptions\CheckInNotFoundException;
use App\Repositories\TimeLogRepository;
use FlySystem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Event;
use App\Events\TimeLogs\UserCheckIn;
use App\Events\TimeLogs\UserCheckOut;
use Sorskod\Larasponse\Larasponse;
use App\Models\User;
use App\Models\Division;
use Excel;
use App\Models\Trade;
use App\Models\JobType;
use Settings;
use App\Models\TimeLog;
use App\Models\Resource;

class TimeLogServices
{

    function __construct(TimeLogRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
    }

    public function getLogEntries($filters)
    {
        return $this->repo->getLogEntries($filters);
    }

    public function getTimelogsSummary($group, $subGroup, $limit, $filters)
    {
        return $this->repo->getTimelogsSummary($filters, $limit);
    }

    /**
     * Check In
     * @param  integer $jobId job id
     * @param  Date Time  $startDateTime Start Date time
     * @param  Array $meta optional values
     * @return Response
     */
    public function checkIn($startDateTime, $meta = [])
    {
        $userId = Auth::id();
        $checkInImage = null;

        //check check in
        $timeLog = $this->repo->getCheckInLogByUserId($userId);
        if ($timeLog) {
            throw new AlreadyCheckInException(trans('response.error.already_check_in'));
        }

        if (ine($meta, 'check_in_image')) {
            $checkInImage = $this->saveImageFile($meta['check_in_image']);
            $meta['file_with_new_path'] = true;
        }

        $meta['check_in_image'] = $checkInImage;
        $timeLog = $this->repo->save($startDateTime, $userId, $meta);

        Event::fire('JobProgress.TimeLogs.Events.UserCheckIn', new UserCheckIn($timeLog));

        return $timeLog;
    }

    /**
     * Check out
     * @param  Instance $timeLog Instance of Job time tracker log
     * @param  DateTime $endDateTime Date Time
     * @param  File $checkOutImage CheckOut image
     * @return Response
     */
    public function checkOut($endDateTime, $checkOutImage, $clockOutNote = null, $checkOutLocation = null)
    {
        $timeLog = $this->repo->getCheckInLogByUserId(\Auth::id());
        if (!$timeLog) {
            throw new CheckInNotFoundException(trans('response.error.check_in_not_found'));
        }

        if ($checkOutImage) {
            $timeLog->check_out_image = $this->saveImageFile($checkOutImage, $timeLog);
			if (!$timeLog->check_in_image) {
				$timeLog->file_with_new_path = true;
			}
        }

        $startDate = Carbon::parse($timeLog->start_date_time);
        $endDate = Carbon::parse($endDateTime);
        $duration = $startDate->diffInSeconds($endDate);

        $timeLog->clock_out_note = $clockOutNote;
        $timeLog->end_date_time = $endDateTime;
        $timeLog->duration = $duration;
        $timeLog->check_out_location = $checkOutLocation;
        $timeLog->update();

        $timeLog = $this->repo->getById($timeLog->id);

        Event::fire('JobProgress.TimeLogs.Events.UserCheckOut', new UserCheckOut($timeLog));

        return $timeLog;
    }

    /**
     * Get Current user check in
     * @return Response
     */
    public function getCurrentUserCheckIn()
    {
        $userTTLog = $this->repo->getCheckInLogByUserId(\Auth::id());
        if (!$userTTLog) {
            throw new CheckInNotFoundException(trans('response.error.check_in_not_found'));
        }

        return $userTTLog;
    }

    /**
     * Get job time tracker log by id
     * @param  Int $id job time tracker log id
     * @return Response
     */
    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    /**
	 * export csv
	 *
	 * GET - /timelogs/csv_export
	 *
	 * @return response
	 */
	public function csvExport($timeLogs, $filters=[])
	{
		$timeLogs = $this->response->collection($timeLogs, function($timeLog) {
			$job      = $timeLog->job;
			$timezone = Settings::get('TIME_ZONE');

			return [
				'User Name'			=> $timeLog->user ? $timeLog->user->full_name : '',
				'Customer/Job'		=> $job ? $job->customer->full_name.' / '.$job->number : '',
				'Job #' 			=> $job ? $job->full_alt_id : '',
				'Job Address'		=> $job ? str_ireplace(["<br />","<br>","<br/>"], " ", $job->address->present()->fullAddress) : '',
				'Trades'			=> $job ? implode(',', $job->trades->pluck('name')->toArray()) : '',
				'Duration (HH:MM)'	=> durationFromSeconds($timeLog->duration),
				'Clocked In Time'	=> convertTimezone($timeLog->start_date_time, $timezone),
				'Clocked Out Time'	=> convertTimezone($timeLog->end_date_time, $timezone),
				'Clocked In Note'	=> $timeLog->clock_in_note,
				'Clocked Out Note'	=> $timeLog->clock_out_note,
			];
		});

		if(empty($timeLogs['data'])) {
			$timeLogs['data'][] = $this->getDefaultColumns();
		}

        $csvFilters = $this->getCsvFilters($filters);

		Excel::create('Clock In & Clock Out Report', function($excel) use($timeLogs, $csvFilters){
			$excel->sheet('sheet1', function($sheet) use($timeLogs, $csvFilters){
				$sheet->mergeCells('A1:L1');
				$sheet->cells('A1:L1', function($cells) {
				   $cells->setAlignment('center');
				});
				$sheet->row(1, $csvFilters);
				$sheet->prependRow(2, array_keys($timeLogs['data'][0]));
				$sheet->rows($timeLogs['data']);
			});
		})->export('xlsx');
	}


	/**
	 * Get duration of entries
	 * @return Response
	 */
	public function duration($filters)
	{
		return $this->repo->duration($filters);
	}

    /*********************PRIVATE METHOD *********************/

    /**
     * Save Image File
     * @param  file $file File Data
     * @return response
     */
    private function saveImageFile($file, $timeLog = null)
    {
        $companyRoot = Resource::companyRoot(getScopeId())->path.'/';
        $originalName = $file->getClientOriginalName();
        $imageName = Carbon::now()->timestamp . rand() . str_replace(' ', '_', strtolower($originalName));

        $thumbName = preg_replace('/(\.gif|\.jpg|\.png)/', '_thumb$1', $imageName);
        if ($timeLog && $timeLog->check_in_image && !$timeLog->file_with_new_path) {
			$imagePath = config('jp.BASE_PATH').'check_in_check_out/'.$imageName;
			$thumbPath = config('jp.BASE_PATH').'check_in_check_out/'.$thumbName;
			$filePath  = 'check_in_check_out/'. $imageName;
		}else {
			$imagePath = TimeLog::getBasePath().$imageName;
			$thumbPath = TimeLog::getBasePath().$thumbName;
			$filePath  = $imagePath;
		}

        $image = \Image::make($file);
        FlySystem::put($imagePath, $image->encode()->getEncoded());

        //create thumb
        if ($image->height() > $image->width()) {
            $image->heighten(250, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(250, function ($constraint) {
                $constraint->upsize();
            });
        }
        $thumb = $image->encode();
        FlySystem::put($thumbPath, $thumb->getEncoded());

        return $filePath;
    }

    private function getDefaultColumns()
	{
		return [
			'User Name'	,
			'Customer/Job',
			'Job #',
			'Job Address',
			'Trades',
			'Duration',
			'Clocked In Time',
			'Clocked Out Time',
			'Clocked In Note',
			'Clocked Out Note',
		];
	}

	private function getCsvFilters($inputs){
		$filters = 'Filters:';
		// Filters: Division:A,B,C| Trade:Roofing
		$startDate = ine($inputs, 'start_date') ? $inputs['start_date']: null;
		$endDate = ine($inputs, 'end_date') ? $inputs['end_date']: null;
		if ($startDate || $endDate){
			$filters .= ' Duration: '.$startDate.' - '.$endDate.'|';
		}

		if(ine($inputs, 'group')){
			$filters .= ' View By: '.$inputs['group'].'|';
		}

		if(ine($inputs, 'division_id')){
			$names = Division::where('company_id', getScopeId())
				->whereIn('id', (array)$inputs['division_id'])
                ->pluck('name')
                ->toArray();

			if(!empty($names)){
				$names = implode(', ', $names);
				$filters .= ' Division: '.$names.'|';
			}
		}

		if(ine($inputs, 'user_id')){
			$userNames = User::where('company_id', getScopeId())
				->whereIn('id', (array)$inputs['user_id'])
				->selectRaw("CONCAT(first_name, ' ', last_name) AS full_name")
                ->pluck('users.full_name')
                ->toArray();

			if(!empty($userNames)){
				$userNames = implode(', ', $userNames);
				$filters .= ' Users: '.$userNames.'|';
			}
		}

		if(ine($inputs, 'trades')){
			$trades = Trade::whereIn('id', (array)$inputs['trades'])->pluck('name')->toArray();

			if(!empty($trades)){
				$trades = implode(', ', $trades);
				$filters .= ' Trades: '.$trades.'|';
			}
		}

		if(ine($inputs, 'work_types')){
			$workTypes = JobType::whereIn('id', (array)$inputs['work_types'])->pluck('name')->toArray();

			if(!empty($workTypes)){
				$workTypes = implode(', ', $workTypes);
				$filters .= ' Work Types: '.$workTypes.'|';
			}
		}

		$filters = substr($filters, 0, -1);

		return array($filters);
	}
}
