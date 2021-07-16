<?php
namespace App\Models;

class QuickbookSyncInvoice extends BaseModel
{
	protected $table = 'quickbook_sync_invoices';

	protected $fillable = [
		'company_id', 'job_progress_customer_id', 'quickbook_customer_id', 'object_id', 'balance', 'payment',
		'meta', 'errors', 'status', 'action', 'msg', 'created_by', 'last_modified_by',
	];
}