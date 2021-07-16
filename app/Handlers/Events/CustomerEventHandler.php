<?php 

namespace App\Handlers\Events;

use Queue;
use QBDesktopQueue;
use App\Models\Job;
use Auth;

class CustomerEventHandler
{

	const REF_TYPE = 'customers';
	const CREATE_OPERATION = 'create';

	public function subscribe($event)
	 {
		$event->listen('JobProgress.Customers.Events.CustomerCreated', 'App\Handlers\Events\CustomerEventHandler@customerCreated');
		$event->listen('JobProgress.Customers.Events.CustomerUpdated', 'App\Handlers\Events\CustomerEventHandler@customerUpdated');
		$event->listen('JobProgress.Customers.Events.CustomerCreated', 'App\Handlers\Events\CustomerEventHandler@openApiCustomerCreateWebhook');
	}

 	public function customerCreated($event)
	{
		Queue::push('App\Handlers\Events\CustomerQueueHandler@customerIndexSolr', ['customer_id' => $event->customerId]);
		$this->syncCompanyCam($event->customerId);
	}

 	public function customerUpdated($event)
	{
		Queue::push('App\Handlers\Events\CustomerQueueHandler@customerIndexSolr', ['customer_id' => $event->customerId]);
		$this->syncCompanyCam($event->customerId);
	}

	public function openApiCustomerCreateWebhook( $event )
	{
		$customerId = $event->customerId;
        $data = [
            'user_id' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
            'ref_id' => $customerId,
            'ref_type' => self::REF_TYPE,
            'operation' => self::CREATE_OPERATION
		];
        Queue::connection('open_api_webhook')->push($data);
	}

 	private function syncCompanyCam($customerId)
	{
		$jobIds = Job::where('customer_id', $customerId)->where('sync_on_companycam', true)->pluck('id')->toArray();
		foreach ($jobIds as $jobId) {
			Queue::push('App\Handlers\Events\JobQueueHandler@createCompanyCamProject', [
				'company_id' => getScopeId(),
				'job_id'     => $jobId
			]);
		}
	}
} 