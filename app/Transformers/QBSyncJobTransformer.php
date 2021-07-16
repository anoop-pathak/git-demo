<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QBSyncJobTransformer extends TransformerAbstract
{
	public function transform($job)
	{
		return [
			'id'				=> $job->id,
			'qb_job_id'			=> $job->qb_id,
			'display_name'		=> $job->display_name,
			'qb_creation_date'	=> $job->qb_creation_date,
			'qb_modified_date'	=> $job->qb_modified_date,
			'qb_customer_id'	=> $job->qb_parent_id,
			'meta'				=> $job->meta,
			'address_meta'		=> $job->address_meta,
			'email'					=> $job->email,
			'primary_phone_number'	=> $job->primary_phone_number,
			'alter_phone_number'	=> $job->alter_phone_number,
			'mobile_number'			=> $job->mobile_number,
		];
	}
}
