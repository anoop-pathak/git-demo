<?php
namespace App\Services\QuickBooks\ActivityLogs;

use App\Models\QuickbooksActivity;
use Illuminate\Support\Facades\DB;

class Service
{
	protected $qbSyncCustomerManager;

	public function __construct()
	{
	}

	public function getLogs($filters = [])
	{
		$logs = QuickbooksActivity::where('quickbooks_activity.company_id', getScopeId())
			->join('quickbook_sync_tasks', function($join) {
				$join->on('quickbook_sync_tasks.id', '=', 'quickbooks_activity.task_id')
					->where('quickbook_sync_tasks.company_id', '=', getScopeId());
			})
			->join('customers', function($join) {
				$join->on('customers.id', '=', 'quickbooks_activity.customer_id')
					->where('customers.company_id', '=', getScopeId());
			})
			->whereNotNull('task_id')
			->whereNotNull('customer_id')
			->select('customer_id','quickbook_sync_tasks.object as entity',
				DB::raw("
					max(quickbooks_activity.created_at) as created_date,
					min(quickbooks_activity.created_at) as min_created_date,
					GROUP_CONCAT(DISTINCT(activity_type)) as type,
					CONCAT(customers.first_name, ' ', customers.last_name, ' has been synched with Quickbooks') as msg,
					COUNT(DISTINCT(quickbooks_activity.id)) AS group_count
				"))
			->groupBy('customer_id', DB::raw('hour(quickbooks_activity.created_at)'))->orderBy('quickbooks_activity.created_at', 'DESC');

		return $logs;
	}

	public function getEntitiesLogs($logs){
		foreach ($logs as $log){
			$log->entities = QuickbooksActivity::where('quickbooks_activity.company_id', getScopeId())
				->join('quickbook_sync_tasks', function($join) {
					$join->on('quickbook_sync_tasks.id', '=', 'quickbooks_activity.task_id')
						->where('quickbook_sync_tasks.company_id', '=', getScopeId());
				})
				->where('quickbooks_activity.customer_id', $log->customer_id)
				->whereBetween('quickbooks_activity.created_at',[$log->min_created_date, $log->created_date])
				->select('customer_id','quickbook_sync_tasks.object as entity',
					'quickbooks_activity.created_at',
					'quickbook_sync_tasks.object_id as entity_id',
					'quickbooks_activity.activity_type as type',
					'quickbook_sync_tasks.action',
					'quickbooks_activity.msg')
				->groupBy('quickbooks_activity.id')
				->orderBy('quickbooks_activity.created_at', 'DESC')
				->get();
		}

		return $logs;
	}
}