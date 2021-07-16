<?php
namespace App\Services\QuickBooks;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\CustomerAccountHandler;

trait CustomerAccountHandlerTrait {
    private function resynchCustomerAccount($customer_id, $source){
        $data['customer_id'] = $customer_id;
		$data['company_id'] = getScopeId();
		$data['created_source'] = $source;
		$data['auth_user_id'] = Auth::id();
		Queue::connection('qbo')->push(CustomerAccountHandler::class, $data);
    }
}