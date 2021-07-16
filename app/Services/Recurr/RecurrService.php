<?php

namespace App\Services\Recurr;

use App\Models\Appointment;
use App\Models\AppointmentRecurring;
use App\Models\JobSchedule;
use App\Models\ScheduleRecurring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Settings;
use App\Models\DripCampaignScheduler;
use App\Services\Contexts\Context;
use Exception;

class RecurrService
{

    protected $googleRule = false;
    protected $scope;

	function __construct(Context $scope)
    {
		$this->scope = $scope;
	}

    /**
     * Get Schedule Dates
     * @param  Instance $schedule Schedule Instance
     * @param  DateTime $startDateTime Start DateTime
     * @param  EndDate $endDateTime
     * @param  boolean $newRecuring Boolean
     * @param  boolean $exDates Boolean
     * @param  boolean $all Boolean
     * @return Array Of Dates
     */
    public function getscheduleDates($schedule, $startDateTime, $endDateTime)
    {
        $recurringData = ScheduleRecurring::whereScheduleId($schedule->id)
            ->select('id')
            ->first();

        $recurRule = $this->getRule($schedule->repeat, $schedule->occurence, $schedule->interval);

        $tz = Settings::get('TIME_ZONE');
        $startDateTime = Carbon::parse($startDateTime, $tz);
        $endDateTime   = Carbon::parse($endDateTime, $tz);

        $rule = new \Recurr\Rule($recurRule, $startDateTime, $endDateTime);

        $transformer = new \Recurr\Transformer\ArrayTransformer();
        $recurrings = $transformer->transform($rule);
        $dates = [];
        foreach ($recurrings as $key => $recurring) {
            $start = utcConvert($recurring->getStart()->format('Y-m-d H:i:s'));
            $end   = utcConvert($recurring->getEnd()->format('Y-m-d H:i:s'));
            $dates[$key]['start_date_time'] = $start->format('Y-m-d H:i:s');
            $dates[$key]['end_date_time'] = $end->format('Y-m-d H:i:s');
            $dates[$key]['schedule_id'] = $schedule->id;
            $dates[$key]['id'] = null;
        }

        if ($recurringData) {
            $dates[0]['id'] = $recurringData->id;
        }

        ScheduleRecurring::whereScheduleId($schedule->id)->forceDelete();

        return $dates;
    }

    /**
     * Get Appointment Dates
     * @param  Instance $appointment Appointment Instance
     * @param  DateTime $startDateTime Start DateTime
     * @param  EndDate $endDateTime
     * @param  boolean $newRecuring Boolean
     * @param  boolean $exDates Boolean
     * @param  boolean $all Boolean
     * @return Array Of Dates
     */

    public function getAppointmentRecurringDates($appointment, $startDateTime, $endDateTime)
    {
        $tz = Settings::get('TIME_ZONE');
        if (!$startDateTime instanceof carbon) {
            $startDateTime = Carbon::parse($startDateTime, $tz);
            $endDateTime = Carbon::parse($endDateTime, $tz);
        }

        $recurRule = $this->getRule(
            $appointment->repeat,
            $appointment->occurence,
            $appointment->interval,
            $appointment->until_date,
            $appointment->by_day
        );

        $rule = new \Recurr\Rule($recurRule, $startDateTime, $endDateTime);

        $recurringData = AppointmentRecurring::whereAppointmentId($appointment->id)
            ->withTrashed()
            ->select('id', 'deleted_at', 'deleted_by')
            ->get()->toArray();

        $transformer = new \Recurr\Transformer\ArrayTransformer();
        $recurrings = $transformer->transform($rule);
        $dates = [];

        foreach ($recurrings as $key => $recurring) {
            $oldRecuringData = ine($recurringData, $key) ? $recurringData[$key] : null;
            $start = $recurring->getStart()->format('Y-m-d H:i:s');
            $end = $recurring->getEnd()->format('Y-m-d H:i:s');
            $start = utcConvert($start);
            $end = utcConvert($end);
            $dates[$key]['start_date_time'] = $start->format('Y-m-d H:i:s');
            $dates[$key]['end_date_time'] = $end->format('Y-m-d H:i:s');
            $dates[$key]['appointment_id'] = $appointment->id;
            $dates[$key]['id'] = $oldRecuringData['id'];
            $dates[$key]['deleted_at'] = $oldRecuringData['deleted_at'];
            $dates[$key]['deleted_by'] = $oldRecuringData['deleted_by'];
        }

        AppointmentRecurring::whereAppointmentId($appointment->id)->withTrashed()->forceDelete();

        return $dates;
    }

    public function getGoogleRecurringRule($object)
    {
        $this->googleRule = true;

        if ($object instanceof JobSchedule) {
            $exDates = ScheduleRecurring::whereScheduleId($object->id)
                ->onlyTrashed()
                ->pluck('start_date_time')->toArray();
        } else {
            $exDates = AppointmentRecurring::whereAppointmentId($object->id)
                ->onlyTrashed()
                ->pluck('start_date_time')->toArray();
        }

        $rules[] = 'RRULE:' . $this->getRule(
            $object->repeat,
            $object->occurence,
            $object->interval,
            $object->until_date,
            $object->by_day
        );

        if (!empty($exDates)) {
            $date = [];
            foreach ($exDates as $exDate) {
                $dateTime = new Carbon($exDate, Settings::get('TIME_ZONE'));
                if ($object->full_day) {
                    $date[] = $dateTime->format('Ymd');
                    continue;
                }

                $date[] = $dateTime->format('Ymd\THis\Z');
            }

            if (!empty($date)) {
                $rules[] = "EXDATE;VALUE=DATE:" . implode(',', $date);
            }
        }

        return $rules;
    }

    public function updateAllRecurringAppointment($appointment, $startingDate, $endingDate)
    {
        $tz = Settings::get('TIME_ZONE');

        $appointmentStartDate = new Carbon($appointment->start_date_time);
        $appointmentStartDate->setTimezone($tz);

        $appointmentEndDate = new Carbon($appointment->end_date_time);
        $appointmentEndDate->setTimezone($tz);

        $untilDate = $appointment->until_date;

        if (!$startingDate instanceof carbon) {
            $startingDate = Carbon::parse($startingDate, $tz);
            $endingDate = Carbon::parse($endingDate, $tz);
        }

        $startingTimestampDiff = $appointmentStartDate->timestamp - $startingDate->timestamp;
        $startingSecDiff = $appointmentStartDate->diffInSeconds($startingDate);

        $firstAppointment = AppointmentRecurring::whereAppointmentId($appointment->id)
            ->withTrashed()
            ->orderBy('id', 'asc')
            ->first();
        if ($firstAppointment) {
            $startDateTime = new Carbon($firstAppointment->start_date_time);
            $startDateTime->setTimezone($tz);

            $endDateTime = new Carbon($firstAppointment->end_date_time);
            $endDateTime->setTimezone($tz);
            $test = new $startingDate;
            if ($startingTimestampDiff > 0) {
                $startDateTime->subSeconds($startingSecDiff);
            } else {
                $startDateTime->addSeconds($startingSecDiff);
            }

            $endingTimestampDiff = $appointmentEndDate->timestamp - $endingDate->timestamp;
            $endinggSecDiff = $appointmentEndDate->diffInSeconds($endingDate);

            if ($endingTimestampDiff > 0) {
                $endDateTime->subSeconds($endinggSecDiff);
            } else {
                $endDateTime->addSeconds($endinggSecDiff);
            }
        } else {
            $startDateTime = $startingDate;
            $endDateTime = $endingDate;
        }

        $recurringData = AppointmentRecurring::whereAppointmentId($appointment->id)
            ->withTrashed()
            ->select('id', 'deleted_at', 'deleted_by')
            ->get()->toArray();
        $recurRule = $this->getRule(
            $appointment->repeat,
            $appointment->occurence,
            $appointment->interval,
            $appointment->until_date,
            $appointment->by_day
        );

        $rule = new \Recurr\Rule(
            $recurRule,
            $startDateTime,
            $endDateTime,
            'UTC'
        );

        $transformer = new \Recurr\Transformer\ArrayTransformer();

        $recurrings = $transformer->transform($rule);
        $dates = [];

        $exDates = $this->getExdates($appointment);
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        foreach ($recurrings as $key => $recurring) {
            $oldRecuringData = ine($recurringData, $key) ? $recurringData[$key] : null;
            $start = $recurring->getStart();
            $end = $recurring->getEnd();
            $startDateTime = utcConvert($start->format('Y-m-d H:i:s'));
            $startDate = $startDateTime->format('Y-m-d');
            $endDateTime = utcConvert($end->format('Y-m-d H:i:s'));
            $dates[$key]['start_date_time'] = $startDateTime->format('Y-m-d H:i:s');
            $dates[$key]['end_date_time'] = $endDateTime->format('Y-m-d H:i:s');
            $dates[$key]['appointment_id'] = $appointment->id;
            $dates[$key]['id'] = $oldRecuringData['id'];
            $dates[$key]['deleted_at'] = $oldRecuringData['deleted_at'];
            $dates[$key]['deleted_by'] = $oldRecuringData['deleted_by'];

            if ($dates[$key]['deleted_at']) {
                continue;
            }

            if (empty($exDates)) {
                continue;
            }

            if ($appointment->full_day) {
                $date = $startDate;
            } else {
                $date = $dates[$key]['start_date_time'];
            }

            if (in_array($date, $exDates)) {
                $dates[$key]['deleted_at'] = $currentTime;
                $dates[$key]['deleted_by'] = Auth::id();
            }
        }

        if (!empty($exDates)) {
            Appointment::where('id', $appointment->id)->update(['exdates' => null]);
        }

        AppointmentRecurring::whereAppointmentId($appointment->id)->withTrashed()->forceDelete();

        return $dates;
    }


    private function getExdates($appointment)
    {

        if(!$appointment->exdates) {
            return [];
        }

		try {
			if($appointment->full_day) {
				$exDatesArray = explode(':', $appointment->exdates);
				$exDates = explode(',', $exDatesArray[1]);
				$dates = [];
				foreach ($exDates as $exDate) {
					$dates[] = Carbon::parse($exDate)->toDateString();
				}

				return $dates;
			}

			Log::info('ExDates');
			Log::info($appointment->exdates);

			$exdatesArray = explode('=', $appointment->exdates);
			if($exdatesArray < 1) return [];

			$tzDates =  explode(':', $exdatesArray[1]);

			$exdates   = explode(',', $tzDates[1]);
			$timezone  = $tzDates[0];

			$dates = [];
			foreach ($exdates as $exdate) {
				$carbon = Carbon::parse($exdate, $timezone);
				$carbon->setTimezone('UTC');
				$dates[] = $carbon->toDateTimeString();
			}

			return $dates;
		} catch (Exception $e) {
			$e->exdates = $appointment->exdates;
			$e->appointment_id = $appointment->id;

			throw $e;
		}
    }

    public function updateAllScheduleRecurring($schedule, $startDateTime, $endDateTime, $oldOccurence = null)
    {
        $tz = Settings::get('TIME_ZONE');
        $scheduleStartDate = new Carbon($schedule->start_date_time);
        $scheduleStartDate->setTimezone($tz);

        $scheduleEndDate = new Carbon($schedule->end_date_time);
        $scheduleStartDate->setTimezone($tz);

        $startingDate       = Carbon::parse($startDateTime, $tz);
        $endingDate         = Carbon::parse($endDateTime, $tz);

        $startingTimestampDiff = $scheduleStartDate->timestamp - $startingDate->timestamp;
        $startingSecDiff = $scheduleStartDate->diffInSeconds($startingDate);

        $firstSchedule = ScheduleRecurring::whereScheduleId($schedule->id)
            ->withTrashed()
            ->orderBy('id', 'asc')
            ->first();

        $startDateTime = new Carbon($firstSchedule->start_date_time);
        $startDateTime->setTimezone($tz);

        $endDateTime   = new Carbon($firstSchedule->end_date_time);
        $endDateTime->setTimezone($tz);

        $test = new $startingDate;
        if ($startingTimestampDiff > 0) {
            $startDateTime->subSeconds($startingSecDiff);
        } else {
            $startDateTime->addSeconds($startingSecDiff);
        }

        $endingTimestampDiff = $scheduleEndDate->timestamp - $endingDate->timestamp;
        $endinggSecDiff = $scheduleEndDate->diffInSeconds($endingDate);

        if ($endingTimestampDiff > 0) {
            $endDateTime->subSeconds($endinggSecDiff);
        } else {
            $endDateTime->addSeconds($endinggSecDiff);
        }

        $recurringData = ScheduleRecurring::whereScheduleId($schedule->id)
            ->withTrashed()
            ->select('id', 'deleted_at', 'deleted_by')
            ->get()->toArray();

        $recurRule = $this->getRule($schedule->repeat, $schedule->occurence);

        $rule = new \Recurr\Rule($recurRule, $startDateTime, $endDateTime);

        $transformer = new \Recurr\Transformer\ArrayTransformer();

        $recurrings = $transformer->transform($rule);
        $dates = [];
        foreach ($recurrings as $key => $recurring) {
            $oldRecuringData = ine($recurringData, $key) ? $recurringData[$key] : null;
            $start = utcConvert($recurring->getStart()->format('Y-m-d H:i:s'));
            $end   = utcConvert($recurring->getEnd()->format('Y-m-d H:i:s'));
            $dates[$key]['start_date_time'] = $start->format('Y-m-d H:i:s');
            $dates[$key]['end_date_time'] = $end->format('Y-m-d H:i:s');
            $dates[$key]['schedule_id'] = $schedule->id;
            $dates[$key]['id'] = $oldRecuringData['id'];
            $dates[$key]['deleted_at'] = $oldRecuringData['deleted_at'];
            $dates[$key]['deleted_by'] = $oldRecuringData['deleted_by'];
        }

        ScheduleRecurring::whereScheduleId($schedule->id)->withTrashed()->forceDelete();
        return $dates;
    }

    private function getRule($repeat, $occurence, $interval = 1, $untilDate = null, $byDay = null)
    {
        $freq = strtoupper($repeat);

        $rules = "FREQ={$freq};";

        if ($interval) {
            $rules .= "INTERVAL={$interval};";
        }

        if ($occurence && (!$untilDate)) {
            $rules .= "COUNT={$occurence};";
        }

        if ($freq && !($untilDate || $occurence)) {
            if ($freq == 'YEARLY') {
                $rules .= "COUNT=" . config('jp.appointment_yearly_occurence_limit') . "};";
            } else {
                $rules .= "COUNT=" . config('jp.appointment_occurence_limit') . ";";
            }
        }

        if ($untilDate && !$this->googleRule) {
            $dateTime = new Carbon($untilDate);
            $dateTime->setTimezone(Settings::get('TIME_ZONE'));
            $dateTime->endOfDay();
            $untilDate = $dateTime->format('Y-m-d H:i:s');
            $rules .= "UNTIL={$untilDate};";
        }

        if ($untilDate && $this->googleRule) {
            $dateTime = new Carbon($untilDate);
            $dateTime->setTimezone(Settings::get('TIME_ZONE'));
            $dateTime->endOfDay();
            $untilDate = $dateTime->format('Ymd\THis\Z');
            $rules .= "UNTIL={$untilDate};";
        }

        if (!empty($byDay)) {
            $byDay = implode(',', $byDay);
            $rules .= "BYDAY={$byDay}"; //SU,MO,TU,WE,TH,FR,SA,-1MO(for last monday),1MO(1st monday)
        }

        return $rules;
    }

    public function getDripCampaignRecurring($dripCampaign, $status, $mediumType)
	{
		$recurRule  = $this->getCampaignRule(
			$dripCampaign->repeat,
			$dripCampaign->occurence,
			$dripCampaign->interval, 
			$dripCampaign->until_date,
			$dripCampaign->by_day
		);

		$startDateTime = Carbon::parse(Carbon::now(), Settings::get('TIME_ZONE'))->toDateString();
		$rule = new \Recurr\Rule($recurRule, $startDateTime);

		$transformer = new \Recurr\Transformer\ArrayTransformer();
		$recurrings = $transformer->transform($rule);
		$dates = [];
		$currentDateTime = Carbon::now();
		foreach ($recurrings as $key => $recurring) {
			$start = $recurring->getStart()->format('Y-m-d');
			$dates[$key]['schedule_date_time'] = $start;
			$dates[$key]['company_id']		   = $this->scope->id();
			$dates[$key]['drip_campaign_id']   = $dripCampaign->id;
			$dates[$key]['medium_type']		   = $mediumType;
			$dates[$key]['status']	   = $status;
			$dates[$key]['created_at'] = $currentDateTime;
			$dates[$key]['updated_at'] = $currentDateTime;
		}

		return $dates;
	}

	private function getCampaignRule($repeat, $occurence, $interval = 1, $untilDate = null, $byDay = null)
	{
		$freq = strtoupper($repeat);

		$rules = "FREQ={$freq};";

		if($interval) {
			$rules .= "INTERVAL={$interval};";
		}

		if($occurence && (!$untilDate)) {
			$rules .= "COUNT={$occurence};";
		}

		if($freq && !($untilDate || $occurence )) {
			$rules .= "COUNT=". 1 .";";
		}

		if($untilDate) {
			$rules .= "UNTIL={$untilDate};";
		}

		if(!empty($byDay)) {
			$byDay = implode(',', $byDay);

			$rules .= "BYDAY={$byDay};"; //SU,MO,TU,WE,TH,FR,SA,-1MO(for last monday),1MO(1st monday)
		}

		return $rules;
	}

	public function getCampaignRecurringRule($object)
	{
		$this->campaignRule = true;
		$count = $object->schedulers->count();
		$addCount = $count + 1;
		$exDates = DripCampaignScheduler::whereDripCampaignId($object->id)
            ->pluck('schedule_date_time')
            ->toArray();

		$rules = $this->getRule($object->repeat,
			$object->occurence,
			$object->interval,
			$object->until_date,
			$object->by_day
		);

		if(!empty($exDates)) {
			$date = [];
			foreach ($exDates as $exDate) {
				$dateTime = new Carbon($exDate, Settings::get('TIME_ZONE'));
				$date[] = $dateTime->format('Y-m-d');
			}

			if(!empty($date)) {
				$rules .= "COUNT=". $addCount .";";
				$rules = $rules. "EXDATE=".implode(',', $date);
			}
		}

		return $rules;
	}

	public function  createDripCampaignSchedulerForNextDay($recurRule, $campaign, $medium, $status)
	{
		$rule = new \Recurr\Rule($recurRule);
		$transformer = new \Recurr\Transformer\ArrayTransformer();
		$recurrings = $transformer->transform($rule);

		$dates = [];
		$currentDateTime = Carbon::now();
		foreach ($recurrings as $key => $recurring) {
			$start = $recurring->getStart()->format('Y-m-d');
			$dates[$key]['schedule_date_time'] = $start;
			$dates[$key]['company_id']		   = $this->scope->id();
			$dates[$key]['drip_campaign_id']   = $campaign->id;
			$dates[$key]['medium_type']		   = $medium;
			$dates[$key]['status']	   = $status;
			$dates[$key]['created_at'] = $currentDateTime;
			$dates[$key]['updated_at'] = $currentDateTime;
		}

		return $dates;
	}
}
