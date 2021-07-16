<?php

namespace App\Handlers\Events;

use Firebase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\Jobs\JobService;
use App\Services\ProductionBoard\ProductionBoardService;
use Settings;
use Illuminate\Support\Facades\Queue;

class JobEventHandler
{
    function __construct(JobService $service)
    {
        $this->service = $service;
    }

    public function subscribe($event)
    {
        // $event->listen('JobProgress.Jobs.Events.DocumentUploaded', 'App\Handlers\Events\JobEventHandler@updateJob');
        $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@autoPostToBoard');
        $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@generateAutoIncrementNumbers');
        $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@jobCompletedDate');
        $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@updateFirebaseWorkflow');
        $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@hoverJobSync');
        // $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@createOrUpdateCompanyCamProject');
        $event->listen('JobProgress.Jobs.Events.JobUpdated', 'App\Handlers\Events\JobEventHandler@hoverJobSync');
        $event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Handlers\Events\JobEventHandler@openApiJobCreateWebhook');
    }


    public function updateFirebaseWorkflow( $event )
    {
        $data['current_user_id'] = Auth::id();
        Queue::push('App\Handlers\Events\JobQueueHandler@updateWorkflow', $data);
    }

    public function autoPostToBoard($event)
    {
        try {
            $job = $event->job;
            //job moved to production board
            $setting = Settings::get('PB_AUTO_POST');
            foreach ($settings as $setting) {
				if( !($job->isProject())
					&& ine($setting, 'stage')
					&& ine($setting, 'board_ids')
					&& !empty($boardIds = array_filter((array)$setting['board_ids']))) {
					$jobWorkflow = $job->jobWorkflow;
					$stageCode = $jobWorkflow->current_stage;

					if($stageCode == $setting['stage']) {
						$pbService = App::make(ProductionBoardService::class);
						$pbService->addJobToPB($job, $boardIds);
					}
				}
			}
        } catch (\Exception $e) {
            Log::error('Job Auto-post to boards failed after job creation. Error Detail: ' . getErrorDetail($e));
        }
    }

    /**
	 * Generate Auto increment numbers
	 * @param  Object $event Event
	 * @return [type]        [description]
	 */
	public function generateAutoIncrementNumbers($event)
	{
		try{
			$job = $event->job;
			$jobWorkflow = $job->jobWorkflow;
			$this->service->setAutoIncrementNumberBySystemSetting($job, $jobWorkflow->current_stage);
		} catch(\Exception $e) {
			Log::error('Job model event auto increment numbers. Error Detail: '.getErrorDetail($e));
		}
	}

    public function jobCompletedDate($event)
    {
        try {
            $job = $event->job;
            $this->service->jobCompletedDate($job);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function hoverJobSync($event)
    {
        try {
            $job = $event->job;
            if((bool)($job->sync_on_hover && $job->hover_user_id)) {
                Queue::push('\App\Handlers\Events\JobQueueHandler@createHoverJob', ['job_id' => $job->id,
                    'company_id' => $job->company_id,
                    'hover_deliverable_id' => $job->hover_deliverable_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function openApiJobCreateWebhook( $event )
	{
		$job = $event->job;
        $data = [
            'user_id' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
            'ref_id' => $job->id,
            'ref_type' => 'jobs',
            'operation' => 'create'
        ];
		Log::info('data Captured');
		Log::info($data);
        Queue::connection('open_api_webhook')->push($data);
	}

    // public function createOrUpdateCompanyCamProject( $event )
    // {
    // 	try {
    // 		$job = $event->job;
    // 		$company = $job->company;

    // 		if($company->companyCamClient) {

    // 			$companyCamService = App::make(\App\CompanyCam\CompanyCamService::class);
    // 			$companyCamService->createOrUpdateProject($job->id);
    // 		}

    // 	} catch (\Exception $e) {
    // 		// nothing to do..
    // 	}
    // }
}
