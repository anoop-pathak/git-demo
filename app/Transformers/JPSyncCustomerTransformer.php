<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\AddressesTransformer;

class JPSyncCustomerTransformer extends TransformerAbstract
{
	/**
	 * List of resources to automatically include
	 *
	 * @var array
	 */
	protected $defaultIncludes = [];

	/**
	 * List of resources possible to include
	 *
	 * @var array
	 */
	protected $availableIncludes = [
		'phones', 'address', 'qb_customers', 'financials'
	];

	/**
	 * Turn this item object into a generic array
	 *
	 * @return array
	 */
	public function transform($customer) {
		return [
			'id'                    => $customer->id,
			'first_name'            => $customer->first_name,
			'last_name'             => $customer->last_name,
			'full_name'             => $customer->full_name,
			'is_commercial'         => $customer->is_commercial,
			'full_name_mobile'      => $customer->full_name_mobile,
			'company_name'          => $customer->company_name,
			'email'                 => $customer->email,
			'additional_emails'     => $customer->additional_emails,
			'ignored'               => (bool)$customer->ignored,
			'total_jobs'            => $customer->total_jobs,
			'total_change_orders_with_invoice' => $customer->total_change_orders_with_invoice,
			'total_job_invoices'	=> $customer->total_job_invoices,
			'total_credits'			=> $customer->total_credits,
			'total_refunds'			=> $customer->total_refunds,
			'total_bills'			=> $customer->total_bills,
			'total_payments'		=> $customer->total_payments,
			'mapped'				=> (bool)$customer->mapped,
			'retain_financial'		=> (int)$customer->retain_financial,
			'sync_status'			=> $customer->sync_status,
			'quickbook_id'          => $customer->quickbook_id,
		];
	}

	/**
	 * Include Phones
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includePhones($customer){
		$phones = $customer->phones;
		if($phones) {
			return $this->collection($phones, function($phones) {
				return [
					'label'  => $phones->label,
					'number' => $phones->number,
					'ext'    => $phones->ext 
				];
			}); 
		}
	}

	/**
	 * Include Job Count
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeCount($customer)
	{
		$jobs = $customer->jobs; 

		return $this->item($jobs, function($jobs) {

			return [
				'jobs_count' => $jobs->count(),
			];
		}); 
	}

	/**
	 * Include Job fiancials
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeFinancials($customer)
	{

		return $this->item($customer, function($customer) {

			return [
				'total_change_order_amount' => (float)$customer->total_change_orders_amount,
				'total_invoice_amount' => (float)($customer->total_invoice_amount + $customer->total_invoice_tax_amount),
				'total_received_amount' => (float)$customer->total_received_amount,
				'total_credit_amount' => (float)$customer->total_credit_amount,
				'total_account_payable_amount' => (float)$customer->total_account_payable_amount,
				'total_refund_amount' => (float)$customer->total_refund_amount,
			];
		});
	}

	/**
	 * Include address
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeAddress($customer) {
		$address = $customer->address;
		if($address){
			return $this->item($address, new AddressesTransformer);    
		}
	}

	/**
	 * Include Quickbooks Customer
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeQbCustomers($customer) {
		$qbCustomer = $customer->QBOCustomers;
		if($qbCustomer){
			return $this->collection($qbCustomer, new QBSyncCustomerTransformer);
		}
	}
}