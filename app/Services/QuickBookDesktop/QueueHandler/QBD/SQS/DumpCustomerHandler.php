<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\SQS;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DumpCustomerHandler
{
	public function updateCustomers($queueJob, $data)
	{
		try {
			$customerData = [];
			foreach ($data as $entity)
			{
				DB::table('qbo_customers')
					->where('company_id', $entity['company_id'])
					->where('qb_id', $entity['qb_id'])
					->delete();

				if(ine($entity, 'is_active') && $entity['is_active'] == 'false') {
					continue;
				}
				unset($entity['is_active']);
				$customerData[] = $entity;
			}
			DB::table('qbo_customers')->insert($customerData);
		    $queueJob->delete();
		} catch (QueryException $e){
			 $errorCode = $e->errorInfo[1];
		    if($errorCode == 1062){
		        // houston, we have a duplicate entry problem
		    } else {
		    	throw $e;
		    }
		} catch(Exception $e){
			if($queueJob->attempts() >= 2) {
				$queueJob->delete();
				Log::error('SQS QBO Customers Dump Update After 3 Attempts: '. getErrorDetail($e));
			} else {
				Log::error('SQS QBO Customers Dump Update: '. getErrorDetail($e));
			}
			Log::error($e);
		}
	}

}