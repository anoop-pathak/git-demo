<?php namespace App\Services\QuickBookDesktop\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb\CustomerAccountHandler;

trait CustomerAccountHandlerTrait
{
    private function resynchCustomerAccount($customer_id, $source, $delayTime=null)
    {
        $data['customer_id'] = $customer_id;
		$data['company_id'] = getScopeId();
		$data['created_source'] = $source;
		$data['auth_user_id'] = Auth::id();
		if($delayTime){
			Queue::connection('qbo')->later($delayTime, CustomerAccountHandler::class, $data);
		}else{
			Queue::connection('qbo')->push(CustomerAccountHandler::class, $data);
		}
    }
}