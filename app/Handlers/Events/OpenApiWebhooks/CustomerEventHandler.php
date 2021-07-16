<?php
namespace App\Handlers\Events\OpenApiWebhooks;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Auth;

class CustomerEventHandler {

    const REF_TYPE = 'customers';
    const CREATE_OPERATION = 'create';

    public function subscribe($event)
    {
		$event->listen('JobProgress.Customers.Events.CustomerCreated', 'App\Handlers\Events\OpenApiWebhooks\CustomerEventHandler@createEvent');
	}

	function __construct()
	{

    }

	public function createEvent( $event )
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
}