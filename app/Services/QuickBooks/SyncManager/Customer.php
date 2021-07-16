<?php
namespace App\Services\QuickBooks\SyncManager;

use App\Models\QuickbookSyncCustomer;
use Illuminate\Support\Facades\DB;
use App\Models\Customer as CustomerModel;
use App\Models\QBOCustomer;
use App\Repositories\JobRepository;

class Customer
{
	function __construct(JobRepository $jobRepo)
    {
		$this->jobRepo = $jobRepo;
	}
	public function getQBSyncCustomers($batchId, $filters = [])
	{
		$customers = QBOCustomer::where('qbo_customers.company_id', getScopeId())
			->join('quickbook_sync_customers', function($join) use($batchId) {
				$join->on('quickbook_sync_customers.qb_id', '=', 'qbo_customers.qb_id')
					->where('quickbook_sync_customers.company_id', '=', getScopeId())
					->whereNull('quickbook_sync_customers.customer_id')
					->where('quickbook_sync_customers.batch_id', '=', $batchId);
			})
			->where('quickbook_sync_customers.batch_id', $batchId)->orderBy('quickbook_sync_customers.qb_id','DESC');

		$this->applyFilters($customers, $filters);

		$customers->select('qbo_customers.*', 'quickbook_sync_customers.ignored', 'quickbook_sync_customers.sync_status', 'quickbook_sync_customers.retain_financial');

		return $customers;
	}

	/**
	 * get sync customers with JP origin
	 * @param  Integer | $batchId | Id of Sync Request
	 * @param  Array   | $filters | Array of inputs
	 * @return QueryBuilder $customers
	 */
	public function getJpSyncCustomers($batchId, $filters = [])
	{
		$with = $this->getJpSyncCustomerIncludes($filters, $batchId);

		$customers = CustomerModel::with($with)->where('customers.company_id', getScopeId());
		$customers->join('quickbook_sync_customers', function($join) use($batchId) {
			$join->on('customers.id', '=', 'quickbook_sync_customers.customer_id')
				->where('quickbook_sync_customers.origin', '=', QuickbookSyncCustomer::ORIGIN_JP)
				->whereNull('quickbook_sync_customers.qb_id')
				->where('batch_id', '=', $batchId);
		});

		$customers = $this->includeCustomerFinancials($customers);

		$customers->leftJoin('jobs', function($query){
			$query->on('jobs.customer_id', '=', 'customers.id')
				  ->whereNull('jobs.archived')
				  ->whereNull('jobs.deleted_at');
		});

		$customers->leftJoin('job_credits', function($query){
			$query->on('jobs.id', '=', 'job_credits.job_id')
				 ->whereNull('job_credits.canceled')
				 ->whereNull('job_credits.deleted_at');
		});

		$customers->leftJoin('job_invoices', function($query){
			$query->on('jobs.id', '=', 'job_invoices.job_id')
				  ->where('job_invoices.type', '=', 'job')
				  ->whereNull('job_invoices.deleted_at');
		});

		$customers->leftJoin('job_invoices as change_order_invoices', function($query){
			$query->on('jobs.id', '=', 'change_order_invoices.job_id')
				  ->where('change_order_invoices.type', '=', 'change_order')
				  ->whereNull('change_order_invoices.deleted_at');
		});

		$customers->leftJoin('vendor_bills', function($query){
			$query->on('jobs.id', '=', 'vendor_bills.job_id')
				  ->whereNull('vendor_bills.deleted_at');
		});

		$customers->leftJoin('job_payments', function($query){
			$query->on('jobs.id', '=', 'job_payments.job_id')
				  ->whereNull('job_payments.canceled')
				  ->whereNull('job_payments.ref_id')
				  ->whereNull('job_payments.credit_id')
				  ->whereNull('job_payments.deleted_at');
		});

		$customers->leftJoin('job_refunds', function($query){
			$query->on('jobs.id', '=', 'job_refunds.job_id')
				  ->whereNull('job_refunds.canceled_at')
				  ->whereNull('job_refunds.deleted_at');
		});

		$this->applyFilters($customers, $filters);
		$customers->select('customers.*', 'quickbook_sync_customers.ignored', 'quickbook_sync_customers.sync_status', 'quickbook_sync_customers.retain_financial',  DB::raw("
				COUNT(DISTINCT(job_invoices.id)) as total_job_invoices,
				COUNT(DISTINCT(change_order_invoices.id)) as total_change_orders_with_invoice,
				COUNT(DISTINCT(job_credits.id)) AS total_credits,
				COUNT(DISTINCT(vendor_bills.id)) AS total_bills,
				COUNT(DISTINCT(job_payments.id)) AS total_payments,
				COUNT(DISTINCT(job_refunds.id)) AS total_refunds,
				COUNT(DISTINCT(CASE WHEN jobs.parent_id IS NULL AND jobs.archived IS NULL THEN jobs.id END)) as total_jobs,
				financial_jobs.total_change_orders_amount,
				financial_jobs.total_invoice_amount,
				financial_jobs.total_invoice_tax_amount,
				financial_jobs.total_received_amount,
				financial_jobs.total_credit_amount,
				financial_jobs.total_refund_amount,
				financial_jobs.total_account_payable_amount"));
		$customers->groupBy('customers.id')
			->orderBy('customers.id','DESC');

		return $customers;
	}

	/**
	 * get matching customers of a sync request
	 * @param  Integer | $batchId | Id of Sync Request
	 * @param  Array   | $filters | Array of inputs
	 * @return QueryBuilder $customers
	 */
	public function getMatchingCustomers($batchId, $filters = [])
	{
		$with = $this->getJpSyncCustomerIncludes($filters, $batchId);

		$customers = CustomerModel::with($with)->where('customers.company_id', getScopeId());
		$customers->join('quickbook_sync_customers', function($join) use($batchId) {
				$join->on('customers.id', '=', 'quickbook_sync_customers.customer_id')
					->where('quickbook_sync_customers.batch_id', '=', $batchId)
					->where('quickbook_sync_customers.action_required', '=', false);
		  })->whereNotNull('quickbook_sync_customers.qb_id')
			->whereNotNull('quickbook_sync_customers.customer_id');

		$customers = $this->includeCustomerFinancials($customers);

		$customers->leftJoin('jobs', function($query){
			$query->on('jobs.customer_id', '=', 'customers.id')
				->whereNull('jobs.archived')
				->whereNull('jobs.deleted_at');
		});

		$customers->leftJoin('job_credits', function($query){
			$query->on('jobs.id', '=', 'job_credits.job_id')
				 ->whereNull('job_credits.canceled')
				 ->whereNull('job_credits.deleted_at');
		});

		$customers->leftJoin('job_invoices', function($query){
			$query->on('jobs.id', '=', 'job_invoices.job_id')
				  ->where('job_invoices.type', '=', 'job')
				  ->whereNull('job_invoices.deleted_at');
		});

		$customers->leftJoin('job_invoices as change_order_invoices', function($query){
			$query->on('jobs.id', '=', 'change_order_invoices.job_id')
				  ->where('change_order_invoices.type', '=', 'change_order')
				  ->whereNull('change_order_invoices.deleted_at');
		});

		$customers->leftJoin('vendor_bills', function($query){
			$query->on('jobs.id', '=', 'vendor_bills.job_id')
				  ->whereNull('vendor_bills.deleted_at');
		});

		$customers->leftJoin('job_payments', function($query){
			$query->on('jobs.id', '=', 'job_payments.job_id')
				  ->whereNull('job_payments.canceled')
				  ->whereNull('job_payments.ref_id')
				  ->whereNull('job_payments.credit_id')
				  ->whereNull('job_payments.deleted_at');
		});

		$customers->leftJoin('job_refunds', function($query){
			$query->on('jobs.id', '=', 'job_refunds.job_id')
				  ->whereNull('job_refunds.canceled_at')
				  ->whereNull('job_refunds.deleted_at');
		});

		$this->applyFilters($customers, $filters);
		$customers->select('customers.*', 'quickbook_sync_customers.ignored', 'quickbook_sync_customers.mapped','quickbook_sync_customers.retain_financial', 'quickbook_sync_customers.sync_status',DB::raw("
				COUNT(DISTINCT(job_invoices.id)) as total_job_invoices,
				COUNT(DISTINCT(change_order_invoices.id)) as total_change_orders_with_invoice,
				COUNT(DISTINCT(job_credits.id)) AS total_credits,
				COUNT(DISTINCT(vendor_bills.id)) AS total_bills,
				COUNT(DISTINCT(job_payments.id)) AS total_payments,
				COUNT(DISTINCT(job_refunds.id)) AS total_refunds,
				COUNT(DISTINCT(CASE WHEN jobs.parent_id IS NULL AND jobs.archived IS NULL THEN jobs.id END)) as total_jobs,
				financial_jobs.total_change_orders_amount,
				financial_jobs.total_invoice_amount,
				financial_jobs.total_invoice_tax_amount,
				financial_jobs.total_received_amount,
				financial_jobs.total_credit_amount,
				financial_jobs.total_refund_amount,
				financial_jobs.total_account_payable_amount"));

		$customers->groupBy('customers.id')->orderBy('customers.id','DESC');

		return $customers;
	}

	/**
	 * get already synced customers of QB with JP
	 * @param  Integer | $batchId | Id of Sync Request
	 * @param  Array   | $filters | Array of inputs
	 * @return QueryBuilder $customers
	 */
	public function getActionRequiredCustomers($batchId, $filters = [])
	{
		$with = $this->getJpSyncCustomerIncludes($filters, $batchId);

		$customers = CustomerModel::with($with)->where('customers.company_id', getScopeId());
		$customers->join('quickbook_sync_customers', function($join) use($batchId) {
				$join->on('customers.id', '=', 'quickbook_sync_customers.customer_id')
					->where('quickbook_sync_customers.batch_id', '=', $batchId)
					->where('quickbook_sync_customers.action_required', '=', true);
		  })
		->whereNotNull('quickbook_sync_customers.qb_id')
		->whereNotNull('quickbook_sync_customers.customer_id');

		$this->applyFilters($customers, $filters);

		$customers = $this->includeCustomerFinancials($customers);

		$customers->leftJoin('jobs', function($query){
			$query->on('jobs.customer_id', '=', 'customers.id')
				->whereNull('jobs.archived')
				->whereNull('jobs.deleted_at');
		});

		$customers->leftJoin('job_credits', function($query){
			$query->on('jobs.id', '=', 'job_credits.job_id')
				 ->whereNull('job_credits.canceled')
				 ->whereNull('job_credits.deleted_at');
		});

		$customers->leftJoin('job_invoices', function($query){
			$query->on('jobs.id', '=', 'job_invoices.job_id')
				  ->where('job_invoices.type', '=', 'job')
				  ->whereNull('job_invoices.deleted_at');
		});

		$customers->leftJoin('job_invoices as change_order_invoices', function($query){
			$query->on('jobs.id', '=', 'change_order_invoices.job_id')
				  ->where('change_order_invoices.type', '=', 'change_order')
				  ->whereNull('change_order_invoices.deleted_at');
		});

		$customers->leftJoin('vendor_bills', function($query){
			$query->on('jobs.id', '=', 'vendor_bills.job_id')
				  ->whereNull('vendor_bills.deleted_at');
		});

		$customers->leftJoin('job_payments', function($query){
			$query->on('jobs.id', '=', 'job_payments.job_id')
				  ->whereNull('job_payments.canceled')
				  ->whereNull('job_payments.ref_id')
				  ->whereNull('job_payments.credit_id')
				  ->whereNull('job_payments.deleted_at');
		});

		$customers->leftJoin('job_refunds', function($query){
			$query->on('jobs.id', '=', 'job_refunds.job_id')
				  ->whereNull('job_refunds.canceled_at')
				  ->whereNull('job_refunds.deleted_at');
		});

		// $this->applyFilters($customers, $filters);
		$customers->select('customers.*', 'quickbook_sync_customers.ignored', 'quickbook_sync_customers.mapped','quickbook_sync_customers.retain_financial', 'quickbook_sync_customers.sync_status',DB::raw("
				COUNT(DISTINCT(job_invoices.id)) as total_job_invoices,
				COUNT(DISTINCT(change_order_invoices.id)) as total_change_orders_with_invoice,
				COUNT(DISTINCT(job_credits.id)) AS total_credits,
				COUNT(DISTINCT(vendor_bills.id)) AS total_bills,
				COUNT(DISTINCT(job_payments.id)) AS total_payments,
				COUNT(DISTINCT(job_refunds.id)) AS total_refunds,
				COUNT(DISTINCT(CASE WHEN jobs.parent_id IS NULL AND jobs.archived IS NULL THEN jobs.id END)) as total_jobs,
				financial_jobs.total_change_orders_amount,
				financial_jobs.total_invoice_amount,
				financial_jobs.total_invoice_tax_amount,
				financial_jobs.total_received_amount,
				financial_jobs.total_credit_amount,
				financial_jobs.total_refund_amount,
				financial_jobs.total_account_payable_amount"));
		$customers->groupBy('customers.id')->orderBy('customers.id','DESC');

		return $customers;
	}

	/**
	 * get customer stats
	 * @param  Integer | $batchId | Sync Request Id
	 * @return $data
	 */
	public function getSyncCustomerStats($batchId, $filters=[])
	{

		$customer = QuickbookSyncCustomer::where('quickbook_sync_customers.company_id', getScopeId())
			->where('batch_id', $batchId);
		if(ine($filters, 'keyword')){
			$customer->leftJoin('customers', 'customers.id', '=', 'quickbook_sync_customers.customer_id');
			$this->applyFilters($customer, $filters);
		}

		if(ine($filters, 'qb_keyword')){
			$customer->leftJoin('qbo_customers', 'qbo_customers.qb_id', '=', 'quickbook_sync_customers.qb_id');
			$this->applyFilters($customer, $filters);
		}

		$customer = $customer->select(DB::raw("
				COUNT(CASE WHEN quickbook_sync_customers.customer_id IS NULL AND quickbook_sync_customers.qb_id IS NOT NULL THEN quickbook_sync_customers.qb_id END) AS total_qb_customers,
				
				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL THEN 1 END) AS total_jp_customers,
				
				COUNT(CASE WHEN action_required = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL THEN 1 END) AS total_matching_customers,

				COUNT(CASE WHEN action_required = 1 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL THEN 1 END) AS total_action_required_customers,

				COUNT(CASE WHEN customer_id IS NULL AND ignored = 1 THEN 1 END) AS ignored_qb_customers,
				
				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL AND ignored = 1 THEN 1 END) AS ignored_jp_customers,
				
				COUNT(CASE WHEN action_required = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND ignored = 1 THEN 1 END) AS ignored_matching_customers,
				
				COUNT(CASE WHEN action_required = 1 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND ignored = 1 THEN 1 END) AS ignored_action_required_customers,

				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL AND ignored = 0 AND sync_status = 'ready_for_synching' THEN 1 END) AS ready_for_synching_jp_customers,
				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL AND ignored = 0 AND sync_status = 'submit_for_synching' THEN 1 END) AS submit_for_synching_jp_customers,
				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL AND ignored = 0 AND sync_status = 'synching' THEN 1 END) AS synching_jp_customers,
				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL AND ignored = 0 AND sync_status = 'synch_complete' THEN 1 END) AS synch_complete_jp_customers,
				COUNT(CASE WHEN quickbook_sync_customers.qb_id IS NULL AND ignored = 0 AND sync_status = 'synch_failed' THEN 1 END) AS synch_failed_jp_customers,

				COUNT(CASE WHEN customer_id IS NULL AND ignored = 0 AND sync_status = 'ready_for_synching' THEN 1 END) AS ready_for_synching_qb_customers,
				COUNT(CASE WHEN customer_id IS NULL AND ignored = 0 AND sync_status = 'submit_for_synching' THEN 1 END) AS submit_for_synching_qb_customers,
				COUNT(CASE WHEN customer_id IS NULL AND ignored = 0 AND sync_status = 'synching' THEN 1 END) AS synching_qb_customers,
				COUNT(CASE WHEN customer_id IS NULL AND ignored = 0 AND sync_status = 'synch_complete' THEN 1 END) AS synch_complete_qb_customers,
				COUNT(CASE WHEN customer_id IS NULL AND ignored = 0 AND sync_status = 'synch_failed' THEN 1 END) AS synch_failed_qb_customers,

				COUNT(CASE WHEN action_required = 0 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'ready_for_synching' THEN 1 END) AS ready_for_synching_matching_customers,
				COUNT(CASE WHEN action_required = 0 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'submit_for_synching' THEN 1 END) AS submit_for_synching_matching_customers,
				COUNT(CASE WHEN action_required = 0 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'synching' THEN 1 END) AS synching_matching_customers,
				COUNT(CASE WHEN action_required = 0 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'synch_complete' THEN 1 END) AS synch_complete_matching_customers,
				COUNT(CASE WHEN action_required = 0 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'synch_failed' THEN 1 END) AS synch_failed_matching_customers,

				COUNT(CASE WHEN action_required = 1 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'ready_for_synching' THEN 1 END) AS ready_for_synching_action_required_customers,
				COUNT(CASE WHEN action_required = 1 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'submit_for_synching' THEN 1 END) AS submit_for_synching_action_required_customers,
				COUNT(CASE WHEN action_required = 1 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'synching' THEN 1 END) AS synching_action_required_customers,
				COUNT(CASE WHEN action_required = 1 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'synch_complete' THEN 1 END) AS synch_complete_action_required_customers,
				COUNT(CASE WHEN action_required = 1 AND ignored = 0 AND quickbook_sync_customers.qb_id IS NOT NULL AND customer_id IS NOT NULL AND sync_status = 'synch_failed' THEN 1 END) AS synch_failed_action_required_customers

			"))
			->first();

		$data = [
			'jp_to_qb' => [
				'total'					=> $customer->total_jp_customers,
				'ignored'				=> $customer->ignored_jp_customers,
				'ready_for_synching'	=> $customer->ready_for_synching_jp_customers,
				'submit_for_synching'	=> $customer->submit_for_synching_jp_customers,
				'synching'				=> $customer->synching_jp_customers,
				'synch_complete'		=> $customer->synch_complete_jp_customers,
				'synch_failed'			=> $customer->synch_failed_jp_customers,
			],
			'qb_to_jp' => [
				'total'					=> $customer->total_qb_customers,
				'ignored'				=> $customer->ignored_qb_customers,
				'ready_for_synching'	=> $customer->ready_for_synching_qb_customers,
				'submit_for_synching'	=> $customer->submit_for_synching_qb_customers,
				'synching'				=> $customer->synching_qb_customers,
				'synch_complete'		=> $customer->synch_complete_qb_customers,
				'synch_failed'			=> $customer->synch_failed_qb_customers,
			],
			'matching' => [
				'total'					=> $customer->total_matching_customers,
				'ignored'				=> $customer->ignored_matching_customers,
				'ready_for_synching'	=> $customer->ready_for_synching_matching_customers,
				'submit_for_synching'	=> $customer->submit_for_synching_matching_customers,
				'synching'				=> $customer->synching_matching_customers,
				'synch_complete'		=> $customer->synch_complete_matching_customers,
				'synch_failed'			=> $customer->synch_failed_matching_customers,
			],
			'action_required' => [
				'total'					=> $customer->total_action_required_customers,
				'ignored'				=> $customer->ignored_action_required_customers,
				'ready_for_synching'	=> $customer->ready_for_synching_action_required_customers,
				'submit_for_synching'	=> $customer->submit_for_synching_action_required_customers,
				'synching'				=> $customer->synching_action_required_customers,
				'synch_complete'		=> $customer->synch_complete_action_required_customers,
				'synch_failed'			=> $customer->synch_failed_action_required_customers,
			],
		];

		return $data;
	}

	/***** Private Functions *****/

	private function applyFilters($query, $filters)
	{
		if(ine($filters, 'origin')) {
			$query->where('quickbook_sync_customers.origin', $filters['origin']);
		}

		if(ine($filters, 'customer_account_status')) {
			if($filters['customer_account_status'] == 'ignored') {
				$query->where('ignored', true);

			}elseif($filters['customer_account_status'] == 'selected') {
				$query->where('ignored', false);
			}
		}

		if(ine($filters, 'sync_status')){
			$query->where('ignored', false)
				->whereIn('sync_status', (array)$filters['sync_status']);
		}

		if(ine($filters,'qb_keyword')) {
			$query->where(function($query) use($filters){
	            $query->where('qbo_customers.display_name','Like','%'.$filters['qb_keyword'].'%');
	            $query->orWhere('qbo_customers.company_name','Like','%'.$filters['qb_keyword'].'%');
	        });
		}

		if(ine($filters,'keyword')) {
			$query->where(function($query) use($filters){
	            $query->whereRaw("CONCAT(customers.first_name,' ',customers.last_name) LIKE ?",['%'.$filters['keyword'].'%']);
	            $query->orWhere('customers.company_name','Like','%'.$filters['keyword'].'%');
	        });
		}
	}

	private function getJpSyncCustomerIncludes($input, $batchId)
	{
		$with = [
			'jobs'
		];

		$includes = isSetNotEmpty($input, 'includes') ?: [];

		if(!arry_fu($includes)) return $with;

		if(in_array('qb_customers', $includes)) {
			$qbCustomer = [
				'QBOCustomers' => function($query) use($batchId) {
					$query->where('batch_id', $batchId);
				} 
			];
			$with = array_merge($qbCustomer, $with);
		}

		if(in_array('phones', $includes)) {
			$with[] = 'phones';
		}

		if(in_array('address', $includes)) {
			$with[] = 'address';
		}

		return $with;
	}

	private function includeCustomerFinancials($customerQuery)
	{
		$customers = clone $customerQuery;
		$customerIds = $customers->select('customers.*')->pluck('customers.id')->toArray();
		$filters['customer_ids'] = $customerIds;
		$jobsQuery = $this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation'])
			->select('jobs.customer_id', DB::raw("
				SUM(IFNULL(job_financial_calculations.total_change_order_invoice_amount, 0)) as total_change_orders_amount,
				SUM(IFNULL(job_financial_calculations.job_invoice_amount, 0)) as total_invoice_amount,
				SUM(IFNULL(job_financial_calculations.job_invoice_tax_amount, 0)) as total_invoice_tax_amount,
				SUM(IFNULL(job_financial_calculations.total_received_payemnt, 0)) as total_received_amount,
				SUM(IFNULL(job_financial_calculations.total_credits, 0)) as total_credit_amount,
				SUM(IFNULL(job_financial_calculations.total_refunds, 0)) as total_refund_amount,
				SUM(IFNULL(job_financial_calculations.total_account_payable_amount, 0)) as total_account_payable_amount"))
			->groupBy('jobs.customer_id');
		$financialJobsQuery = generateQueryWithBindings($jobsQuery);

		return $customerQuery->leftJoin(DB::raw("({$financialJobsQuery}) as financial_jobs "), 'financial_jobs.customer_id', '=', 'customers.id');
	}
}