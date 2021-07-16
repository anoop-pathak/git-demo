<?php
namespace App\Handlers\Events\NotificationEventHandlers;

use App\Repositories\NotificationsRepository;
use App\Models\User;
use Sorskod\Larasponse\Larasponse;
use MobileNotification;
use App\Transformers\Optimized\JobsTransformer;

class JobPriceRequestNotificationEventHandler {

	protected $repo;

	function __construct(NotificationsRepository $repo,Larasponse $response) {
		$this->repo = $repo;
		$this->response = $response;
	}

	public function handle($event) {
		$jobPriceRequest = $event->jobPriceRequest;
		$this->jobPriceUpdateNotification($jobPriceRequest);
	}

	/**************Private Functions******************/

	private function jobPriceUpdateNotification($jobPriceRequest) {

		$users = User::where('company_id', $jobPriceRequest->company_id)
			->join('user_permissions', 'user_permissions.user_id', '=', 'users.id')
			->where('permission', 'approve_job_price_request')
			->where('allow', true)
			->active()
			->standard()
			->select('users.id')
            ->pluck('id')
            ->toArray();

		$id = User::where('company_id', $jobPriceRequest->company_id)
			->authority()
			->pluck('id')
            ->toArray();
		$ids = array_merge($id, $users);
		$this->sendNotification($jobPriceRequest, $ids);
		$this->pushNotification($jobPriceRequest, $ids);
	}

	private function sendNotification($jobPriceRequest, $recipients) {
		try{
			$job = $jobPriceRequest->job;
			$customer = $job->customer;
			$requestedBy = $jobPriceRequest->requestedBy;
			$info = $customer->first_name.' '.$customer->last_name.' / '. $job->present()->jobIdReplace;
			$message = 'You have received a job price update request for '.$info;
			$meta = [
				'job_id' => $job->id,
				'job_price_request_id' => $jobPriceRequest->id,
				'multi_job' => $job->multi_job,
				'parent_id' => $job->parent_id,
				'customer_id' => $job->customer_id,
			];
			$this->repo->notification($requestedBy, $recipients, $message, $jobPriceRequest, $meta);
		}catch(\Exception $e){
		}
	}

	private function pushNotification($jobPriceRequest, $userIds) {
		$type = 'job_price_request_submitted';
		$job = $jobPriceRequest->job;
		$customer = $job->customer;
		$job = $this->response->item($job,new JobsTransformer);
		$meta = [
			'job_id' => $job['id'],
			'customer_id' => $job['customer_id'],
			'stage_resource_id'  => isset($job['current_stage']['resource_id']) ? $job['current_stage']['resource_id'] : Null,
			'job_resource_id'  => isset($job['meta']['resource_id']) ? $job['meta']['resource_id'] : Null,
			'company_id' => $jobPriceRequest->company_id,
		];

		$title =  'Job Price Update Request';
		$info = $customer->first_name.' '.$customer->last_name.' / '. $jobPriceRequest->job->present()->jobIdReplace;
		$message = 'You have received a job price update request for '.$info;
		MobileNotification::send($userIds, $title, $type, $message, $meta);
	}

}