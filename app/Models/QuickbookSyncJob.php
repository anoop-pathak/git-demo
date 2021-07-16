<?php
namespace App\Models;

class QuickbookSyncJob extends BaseModel
{
	protected $table = 'quickbook_sync_jobs';

	protected $fillable = [
		'batch_id', 'company_id', 'is_project', 'multi_job', 'parent_id', 'object_id', 'meta', 'status', 'action',
		'msg', 'created_by', 'last_modified_by', 'qb_customer_id', 'qb_sync_customer_id',
	];
}