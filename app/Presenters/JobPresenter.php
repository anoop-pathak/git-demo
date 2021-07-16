<?php

namespace App\Presenters;

use Laracasts\Presenter\Presenter;
use Settings;

class JobPresenter extends Presenter
{

    /**
     *
     * @return [string] [address]
     */
    public function jobRepLaborSubAll()
    {
        $jobRepLaborSub = [];
        /* set Reps */
        foreach ($this->reps as $key => $rep) {
            $jobRepLaborSub[] = $rep->full_name;
        }

        /* set Sub Contractors */
       if(\Auth::user()->isSubContractorPrime() && in_array(\Auth::id(), $this->subContractors->pluck('id')->toArray())) {
            if(\Auth::user()->company_name) {
                $jobRepLaborSub[] = \Auth::user()->company_name.'(S)';
            } else {
                $jobRepLaborSub[] = \Auth::user()->full_name.'(S)';
            }
        } else {
            foreach( $this->subContractors as $key => $sub ) {
                if(!$sub->company_name) {
                    $jobRepLaborSub[] = $sub->full_name.'(S)';
                    continue;
                }
                $jobRepLaborSub[] = $sub->company_name.'(S)';
            }
        }

        if ($jobRLS = implode(", ", $jobRepLaborSub)) {
            return $jobRLS;
        }

        return 'Unassigned';
    }

    public function jobEstimators()
    {
        $estimators = [];

        /* set Estimators */
        foreach ($this->estimators as $key => $estimator) {
            $estimators[] = $estimator->full_name;
        }

        if ($estimator = implode(", ", $estimators)) {
            return $estimator;
        }

        return 'Unassigned';
    }

    public function jobDuration()
    {

        $jobDuration = [];

        $duration = explode(':', $this->duration);

        $styles = "white-space:nowrap; font-style:normal;clear:both;";

        if (isset($duration[0]) && !empty((int)$duration[0])) {
            $jobDuration[] = '<i style="' . $styles . '">' . $duration[0] . ' Day(s)</i>';
        }

        if (isset($duration[1]) && !empty((int)$duration[1])) {
            $jobDuration[] = '<i style="' . $styles . '">' . $duration[1] . ' Hour(s)</i>';
        }

        if (isset($duration[2]) && !empty((int)$duration[2])) {
            $jobDuration[] = '<i style="' . $styles . '">' . $duration[2] . ' Minute(s)</i>';
        }

        if (count($jobDuration)) {
            return implode(', ', $jobDuration);
        }

        return false;
    }

    public function workCrew()
    {
        $jobRepLaborSub = [];
        /* set Reps */
        foreach ($this->reps as $key => $rep) {
            $jobRepLaborSub[] = $rep->full_name;
        }

        /* set Sub Contractors */
        foreach ($this->subContractors as $key => $sub) {
            if (!$sub->company_name) {
                $jobRepLaborSub[] = $sub->full_name . '(S)';
                continue;
            }
            $jobRepLaborSub[] = $sub->company_name . '(S)';
        }

        if (empty($jobRepLaborSub)) {
            return null;
        }

        $data = [];

        foreach ($jobRepLaborSub as $sub) {
            $data[] = '<span style="text-transform: Capitalize;white-space:nowrap;" class="btn-flags label label-default">' . $sub . '</span>';
        }

        return implode('', $data);
    }

    public function jobTrades()
    {
        $trades = $this->trades->pluck('name')->toArray();

        return implode(', ', $trades);
    }

    public function jobWorkTypes()
    {
        $workTypes = $this->workTypes->pluck('name')->toArray();

        return implode(', ', $workTypes);
    }

    public function jobReps()
    {
        $jobReps = [];

        foreach ($this->reps as $key => $rep) {
            $jobReps[] = $rep->full_name;
        }

        return implode(', ', $jobReps);
    }

    public function jobSubNames()
    {
        $subs = [];
        foreach ($this->subContractors as $key => $sub) {
            if (!$sub->company_name) {
                $subs[] = $sub->full_name;
                continue;
            }
            $subs[] = $sub->company_name;
        }

        return implode(', ', $subs);
    }

    public function jobLaboursName()
    {
        $labours = [];

        return implode(', ', $labours);
    }

    public function jobIdReplace()
	{
		$content = null;
		$settings = Settings::get('JOB_ID_REPLACE_WITH');

		switch ($settings) {
			case 'name':
			$content = $this->name;
			break;
			case 'alt_id':
			$content = $this->full_alt_id;
			break;
			default:
			$content = $this->number;
			break;
		}

        if (!$content) {
			$content = $this->number;
		}

		return $content;
	}

	public function jobIdReplaceWithLable()
	{
		$content = null;
		$settings = Settings::get('JOB_ID_REPLACE_WITH');

		switch ($settings) {
			case 'name':
			$content = 'Job Name: '. $this->name;
			break;
			case 'alt_id':
			$content = 'Job #: '. $this->full_alt_id;
			break;
			default:
			$content = 'Job Id: '. $this->number;
			break;
		}

        if ((($settings == 'name') && !$this->name) || (($settings == 'alt_id') && !$this->full_alt_id)) {
			$content = 'Job Id: '. $this->number;
		}

 		return $content;
 	}

    public function jobEstimatorsFirstName()
	{
		$estimators = [];
		foreach($this->estimators as $key => $estimator) {
			$estimators[] = $estimator->first_name;
		}

		$estimator = implode(", ", $estimators);
		return $estimator;
	}

	public function jobEstimatorsLastName()
	{
		$estimators = [];
		foreach($this->estimators as $key => $estimator) {
			$estimators[] = $estimator->last_name;
		}

		$estimator = implode(", ", $estimators);
		return $estimator;
	}

	public function jobEstimatorsEmail()
	{
		$emails = [];
		foreach( $this->estimators as $key => $estimator ) {
			$emails[] = $estimator->email;
		}

		$email = implode(", ", $emails);
		return $email;
	}

	public function jobEstimatorsAndRepPhone($users, $countryCode)
	{
		$phones = $userPhones = [];
		$usersCount = count($users);
		foreach($users as $key => $user) {
			$userNames[] = $user->full_name;
			$additionalPhones = $user->profile->additional_phone;
			if ($additionalPhones) {
				foreach ($additionalPhones as $additionalPhone) {
					switch ($additionalPhone->label) {
						case 'home':
						$phones[$user->full_name][] = phoneNumberFormat($additionalPhone->phone, $countryCode);
						break;
						case 'cell':
						$phones[$user->full_name][] = phoneNumberFormat($additionalPhone->phone, $countryCode);
						break;
						case 'phone':
						$phones[$user->full_name][] = phoneNumberFormat($additionalPhone->phone, $countryCode);
						break;

						case 'office':
						$phones[$user->full_name][] = phoneNumberFormat($additionalPhone->phone, $countryCode);
						break;

						case 'other':
						$phones[$user->full_name][] = phoneNumberFormat($additionalPhone->phone, $countryCode);
						break;
						case 'fax':
						$phones[$user->full_name][] = phoneNumberFormat($additionalPhone->phone, $countryCode);
						break;
					}
				}
			}
		}if ($phones) {
			for ($i = 0; $i < $usersCount ; $i++) {
				if (!array_key_exists($userNames[$i], $phones)) {
					continue;
				}
				$userPhones[] = $usersCount > 1 ? $userNames[$i].' ('.implode(', ', $phones[$userNames[$i]]) : $phones[$userNames[$i]];
			}
			$userPhones = $usersCount > 1 ? implode(', ', $userPhones) : implode(', ', array_flatten($userPhones));
			return $userPhones;
		}
	}

	public function jobRepsLastName()
	{
		$lastName = [];
		foreach($this->reps as $key => $rep) {
			$lastName[] = $rep->last_name;
		}

		return implode(', ', $lastName);
	}

	public function jobRepsFirstName()
	{
		$firstName = [];
		foreach($this->reps as $key => $rep) {
			$firstName[] = $rep->last_name;
		}

		return implode(', ', $firstName);
	}
}
