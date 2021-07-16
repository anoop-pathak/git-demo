<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QBSyncCustomerTransformer extends TransformerAbstract
{
	public function transform($customer)
	{
		return [
			'id'					=> $customer->id,
			'qb_customer_id'		=> $customer->qb_id,
			'first_name'			=> $customer->first_name,
			'last_name'				=> $customer->last_name,
			'email'					=> $customer->email,
			'primary_phone_number'	=> $customer->primary_phone_number,
			'alter_phone_number'	=> $customer->alter_phone_number,
			'mobile_number'			=> $customer->mobile_number,
			'display_name'			=> $customer->display_name,
			'ignored'				=> (bool)$customer->ignored,
			'sync_status'			=> $customer->sync_status,
			'qb_creation_date'		=> $customer->qb_creation_date,
			'qb_modified_date'		=> $customer->qb_modified_date,
			'total_jobs'            => $customer->qbJobs->count(),
			'meta'					=> $customer->meta,
			'address_meta'			=> $customer->address_meta,
		];
	}
}
