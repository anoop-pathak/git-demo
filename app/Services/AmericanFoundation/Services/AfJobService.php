<?php
namespace App\Services\AmericanFoundation\Services;

use App\Services\Grid\CommanderTrait;
use App\Services\Jobs\JobProjectService;

class AfJobService
{
    use CommanderTrait;

     /**
     * Instance for JobProjectService
     * @var App\Services\Jobs\JobProjectService;
     */
    protected $jobProjectService;

    public function __construct(JobProjectService $jobProjectService)
    {
        $this->jobProjectService = $jobProjectService;
    }

    public function createJpCustomer($jobsData)
    {
        $job = $this->jobProjectService->saveJobAndProjects($jobsData);

        return $job;
    }
}