<?php

namespace App\Presenters;

use App\Models\User;
use Laracasts\Presenter\Presenter;
use Settings;

class AppointmentPresenter extends Presenter
{

    /**
     * jobs descriptions
     * @return [string] [description]
     */
    public function jobsDescription()
    {

        if (!$this->jobs->count()) {
            return false;
        }
        $contents = [];

        foreach ($this->jobs as $key => $job) {
            $contents[] = $this->jobIdsReplace($job);
            $contents[] = $job->description . '<br>';
        }

        return '<br>' . implode('<br>', $contents);
    }

    public function jobIdsReplace($job)
	{
		$contents = null;
		$settings = Settings::get('JOB_ID_REPLACE_WITH');
		switch ($settings) {
			case 'name':
			$contents = $job->name;
			break;
			case 'alt_id':
			$contents = $job->full_alt_id;
			break;
			default:
			$contents = $job->number;
			break;
		}
        if (!$contents) {
			$contents = $job->number;
		}
		return $contents;
	}

	public function jobDetails()
	{
		if(! $this->jobs->count()) {
			return false;
		}

		foreach ($this->jobs as $key => $job) {
			$contents[] = $this->jobIdsReplace($job);
		}

		return implode('<br> ', $contents);
	}

    public function recurringText()
    {
        if (!$this->repeat) {
            return 'No';
        }

        return ucfirst(strtolower($this->repeat)) . ', ' . $this->occurence . ' times';
    }

    /**
     * Get assigned user name
     * @return User name
     */
    public function assignedUserName()
    {

        return ($user = $this->user) ? $user->full_name : 'Unassigned';
    }

    public function appointmentResult()
    {
        if($this->resultOption) {
            $resultField = $this->resultOption->name;
            $html = "<div class='mb10 app-result'>
                                    <span class='job-heading'>Appointment Result:</span>
                                    <p class='app-label'> $resultField</p>
                                </div>";
            if ($this->result) {
                foreach ((array)$this->result as $key => $field) {
                    $fName  = $field['name'];
                    $fValue = $field['value'];
                    $html .= "<div class='mb10'>
                                    <span class='job-heading'>{$fName}:</span>
                                    <p class=''> {$fValue}</p>
                                </div>";
                }
            }
            return $html;
        }
    }
}
