<?php
namespace App\Handlers\Events\OpenApiWebhooks;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Auth;
use App\Services\Jobs\JobService;

class JobEventHandler {

    public function subscribe($event)
    {
		$event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\OpenApiWebhooks\JobEventHandler@createJobEvent');
	}

	function __construct(JobService $service)
	{
		$this->service = $service;
	}

	public function createJobEvent( $event )
	{
        $job = $event->job;
        $data = [
            'user_id' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
            'ref_id' => $job->id,
            'ref_type' => 'jobs',
            'operation' => 'create'
        ];
        Queue::connection('open_api_webhook')->push($data);
	}
}