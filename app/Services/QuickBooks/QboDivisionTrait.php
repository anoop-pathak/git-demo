<?php
namespace App\Services\QuickBooks;

use App\Models\Job;

trait QboDivisionTrait {

    public function updateJobDivision($job, $divisionId)
    {
    	if(($job && $job->division_id) || !$divisionId){
    		return false;
    	}

        return Job::where('id', $job->id)->update([
				'division_id'   => $divisionId,
			]);
    }
}