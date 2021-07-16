<?php
namespace App\Models;

class QuickbookSyncBatch extends BaseModel
{
	protected $table = 'quickbook_sync_batches';

	protected $fillable = [
		'company_id', 'status', 'created_by', 'status_changed_date_time', 'sync_action', 'sync_scope', 'sync_request_meta', 'connection_type'
	];

	const STATUS_PENDING	 = 'pending';
	const STATUS_IN_PROGRESS = 'in_progress';
	const STATUS_SUCCESS	 = 'success';
	const STATUS_CANCELED	 = 'canceled';
	const STATUS_ANALYZING   = 'analyzing';
	const STATUS_SUBMITTED	 = 'submitted';
	const STATUS_AWAITING	 = 'Awaiting Client Action';
	const STATUS_TERMINATED	 = 'terminated';
	const STATUS_CLOSED	 	 = 'closed';
	const STATUS_SNAPSHOT	 = 'taking_customers_snapshot';

	const SYNC_JP_TO_QB	 = 'jp_to_qb';
	const SYNC_QB_TO_JP	 = 'qb_to_jp';
	const SYNC_TWO_WAY	 = '2_way';
	const QBO 			 = 'qbo';
	const QBD 			 = 'qbd';

	/***** Relationships *****/

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function completedBy()
	{
		return $this->belongsTo(User::class, 'completed_by');
	}

	public function selectedCustomers()
	{
		return $this->belongsToMany(Customer::class, 'sync_request_selected_customer', 'batch_id');
	}

	/***** Relationships End *****/

	protected function hasPendingStatus()
	{
		$query = self::whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_TERMINATED])
			->where('company_id', getScopeId());

		return $query->count() > 0;
	}

	protected function getSaveQBBatchRules()
	{
		$rules = [
			'sync_action'	=> 'required|in:everything,customers,other',
			'customer_ids'	=> 'array|required_if:sync_action,customers',
			'duration'		=> 'required_if:sync_action,other|in:YTD,MTD,WTD,last_month,since_inception,custom',
			'sync_scope'	=> 'required_if:sync_action,other|in:2_way,jp_to_qb,qb_to_jp',
			'start_date'	=> 'required_if:duration,custom|date',
			'end_date'		=> 'required_if:duration,custom|date'
		];

		return $rules;
	}

	/***** Relations Start *****/

	public function qboCustomers()
	{
		return $this->belongsToMany('QBOCustomer', 'quickbook_sync_customers', 'batch_id', 'qb_id');
	}

	public function customers()
	{
		return $this->belongsToMany('Customer', 'quickbook_sync_customers', 'batch_id', 'customer_id');
	}

	public function qbCustomers()
	{
		return $this->hasMany(QuickbookSyncCustomer::class, 'batch_id')
			->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
			->where('quickbook_sync_customers.company_id', getScopeId())
			->whereNull('quickbook_sync_customers.customer_id');
	}

	public function jpCustomers()
	{
		return $this->hasMany(QuickbookSyncCustomer::class, 'batch_id')
			->where('origin', QuickbookSyncCustomer::ORIGIN_JP)
			->where('quickbook_sync_customers.company_id', getScopeId())
			->whereNull('quickbook_sync_customers.qb_id');;
	}

	public function matchingCustomers()
	{
		return $this->hasMany(QuickbookSyncCustomer::class, 'batch_id')
			->where('quickbook_sync_customers.company_id', getScopeId())
			->whereNotNull('quickbook_sync_customers.customer_id')
			->whereNotNull('quickbook_sync_customers.qb_id')
			->where('quickbook_sync_customers.action_required', false);
	}

	public function actionRequiredCustomers()
	{
		return $this->hasMany(QuickbookSyncCustomer::class, 'batch_id')
			->where('quickbook_sync_customers.company_id', getScopeId())
			->whereNotNull('quickbook_sync_customers.customer_id')
			->whereNotNull('quickbook_sync_customers.qb_id')
			->where('quickbook_sync_customers.action_required', true);
	}

	/***** Relations End *****/
	public function setSyncRequestMetaAttribute($value)
	{
		$this->attributes['sync_request_meta'] = json_encode($value);
	}

	public function getSyncRequestMetaAttribute($value)
	{
		return json_decode($value, true);
	}

}