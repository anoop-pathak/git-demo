<?php namespace App\Services\Jobs;

use Carbon\Carbon;

class JobNumber
{

    protected $jobNumber;

    public function generate($job)
    {

        if ($job->isProject()) {
            $this->generateProjectNumber($job);
        } else {
            $this->generateJobNumber($job);
        }

        return $this->jobNumber;
    }

    /* private function */
    private function getDateInitial($job)
    {
        return Carbon::now()->format('ym');
    }

    private function generateJobNumber($job)
    {
        $dateInitial = $this->getDateInitial($job);
        $customerId = sprintf("%03d", $job->customer_id);
        $jobSerielNumber = $job->serielNumber();
        $jobNumber = $dateInitial . '-' . $customerId . '-' . $jobSerielNumber;

        // if($job->isMultiJob()) {
        // 	$jobNumber .= '-P0';
        // }

        $this->jobNumber = $jobNumber;
    }

    private function generateProjectNumber($project)
    {
        $jobNumber = $project->parentJob->number;
        $projectSeriel = 'P' . $project->serielNumber();
        $projectNumber = $jobNumber . '-' . $projectSeriel;
        // $projectNumber = substr_replace($jobNumber, $projectSeriel, strpos($jobNumber,'P'), strlen($jobNumber));
        $this->jobNumber = $projectNumber;
    }
}
