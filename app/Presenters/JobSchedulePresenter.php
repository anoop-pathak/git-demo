<?php

namespace App\Presenters;

use Carbon\Carbon;
use Laracasts\Presenter\Presenter;
use Settings;
use App\Models\ScheduleRecurring;

class JobSchedulePresenter extends Presenter
{
    /**
     * manage off days
     * @return [array] $data
     */
    public function manageOffDays()
    {
        $startDate = convertTimezone($this->start_date_time, Settings::get('TIME_ZONE'));
        $endDate = convertTimezone($this->end_date_time, Settings::get('TIME_ZONE'));
        $diffInDays = $startDate->diffInDays($endDate);
        $companyOffDays = Settings::get('OFF_DAYS');

        /* format all company off days in a year into array (also multiple years) */
        $allOffDates = [];

        if (ine($companyOffDays, 'dates')) {
            foreach ($companyOffDays['dates'] as $day) {
                $date = Carbon::createFromFormat('d/m/Y', $day['date'])->startOfDay();

                if (!ine($day, 'is_yearly')) {
                    $allOffDates[] = $day['date'];
                    continue;
                }
                /* manage off days if is_yearly true */
                $date->year($startDate->year);
                if ($date->between($startDate, $startDate->copy()->endOfYear())) {
                    $allOffDates[] = $date->format('d/m/Y');
                } else {
                    $allOffDates[] = $date->year($endDate->year)->format('d/m/Y');
                }
            }
        }

        /* find off days that come between date duration */
        $offDays = [];
        $offDates = [];
        for ($i = 0; $i < $diffInDays; $i++) {
            $date = $startDate->copy()->format('d/m/Y');
            if (ine($companyOffDays, 'days') && in_array(strtolower($startDate->copy()->formatLocalized('%A')), $companyOffDays['days'])) {
                /* weekly off days */
                $offDays[] = $date;
            } else {
                if (in_array($date, $allOffDates)) {
                    /* company off dates */
                    $offDates[] = $date;
                }
            }
            /* increase day */
            $startDate->addDay(1);
        }

        $data['off_dates'] = $offDates;
        $data['working_days'] = $diffInDays - (count($offDates) + count($offDays));

        return $data;
    }

    public function recurringText()
    {
        if (!$this->repeat) {
            return 'No';
        }

        return ucfirst(strtolower($this->repeat)) . ', ' . $this->occurence . ' times';
    }

    /**
     *
     * @return [string] [address]
     */
    public function jobRepLaborSubAll()
    {
        $data = [];
        /* set Reps */
        $recurring = ScheduleRecurring::findOrFail($this->recurring_id);
        foreach( $recurring->recurringsReps as $key => $rep ) {

			$data[] = [
				'name' => $rep->full_name,
				'status' => $rep->pivot->status
			];
        }

        if(\Auth::user()->isSubContractorPrime()&& in_array(\Auth::id(), $recurring->recurringsSubContractors->pluck('id')->toArray())) {
            if(\Auth::user()->company_name) {
                $data[] = [
					'name' => Auth::user()->company_name.'(S)',
					'status' => $this->getStatus()
				];
            } else {
                $data[] = [
					'name' => Auth::user()->full_name.'(S)',
					'status' => $this->getStatus()
				];
            }
        } else {
            foreach($recurring->recurringsSubContractors as $key => $sub ) {
                if(!$sub->company_name) {
                    $data[] = [
						'name' =>  $sub->full_name.'(S)',
						'status' => $sub->pivot->status
					];
                    continue;
                }
                $data[] = [
					'name' =>  $sub->full_name.'(S)',
					'status' => $sub->pivot->status
				];
            }
        }

        uasort($data, function($a, $b) {
			// equal items sort equally
		    if ($a["status"] === $b["status"]) {
		        return 0;
		    }
		    // nulls sort after anything else
		    else if ($a["status"] === null) {
		        return 1;
		    }
		    else if ($b["status"] === null) {
		        return -1;
		    }
		    // if descending, highest sorts first
		    else {
		        return $a["status"] < $b["status"] ? -1 : 1;
		    }
		});
        return $data;

        // if( $jobRLS = implode(", ", $jobRepLaborSub)) {
		// 	return $jobRLS;
		// }

        return 'Unassigned';
    }

    public function getStatus()
	{
		$status = \DB::table('job_rep')
            ->where('schedule_id', $this->id)
            ->where('recurring_id', $this->recurring_id)
            ->where('rep_id', \Auth::id())
            ->pluck('status');
		if(!$status) return;

		if ($status == 'pending') {
			return ucfirst($status);
		}

		if ($status == 'decline') {
			return ucfirst($status).'d';
		}

		if ($status == 'accept') {
			return ucfirst($status).'ed';
		}
	}
}
