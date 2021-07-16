<?php
namespace App\Models;

class QuickbookSyncCreditMemo extends BaseModel
{
	protected $table = 'quickbook_sync_credit_memos';

	protected $fillable = [
		'company_id', 'status', 'job_progress_customer_id', 'quickbook_customer_id', 'object_id', 'amount', 'balance',
		'meta', 'errors', 'status', 'action', 'msg', 'created_by', 'last_modified_by',
	];
}