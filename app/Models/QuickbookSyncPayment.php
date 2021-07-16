<?php
namespace App\Models;

class QuickbookSyncPayment extends BaseModel
{
	protected $table = 'quickbook_sync_payments';

	protected $fillable = [
		'company_id', 'job_progress_customer_id', 'quickbook_customer_id', 'object_id', 'unapplied_amount', 'payment',
		'meta', 'errors', 'status', 'action', 'msg', 'created_by', 'last_modified_by',
	];
}