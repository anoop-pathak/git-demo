<?php
namespace App\Models;

use Carbon\Carbon;
use App\Services\QuickBooks\Facades\QBOQueue;

class QuickBookTask extends BaseModel
{
    protected $table = 'quickbook_sync_tasks';

    protected $fillable = [
        'name','extra','payload', 'status', 'action','object_id', 'object',
        'company_id', 'created_by','last_modified_by', 'quickbook_webhook_id',
        'quickbook_webhook_entry_id','origin', 'msg', 'created_by', 'msg',
        'object_last_updated', 'parent_id', 'queue_started_at', 'created_source', 'group_id'
    ];

    const STATUS_ERROR = 'Error';
    const STATUS_PENDING = 'Pending';
    const STATUS_INPROGRESS = 'InProgress';
    const STATUS_SUCCESS = 'Success';
    /**
     * @deprecated in favor of QuickbookWebhookEntry:: STATUS_PROCESSED
     */
    const STATUS_ENTRY_PROCESSED = 'Processed';

    const CREATE = 'Create';
    const UPDATE = 'Update';
    const DELETE = 'Delete';
    const SYNC = 'Sync';
    const IMPORT = 'Import';
    const APPLY = 'Apply';
    const MAP = 'Map';
    const DELETE_FINANCIAL = 'Delete Financial';

    const ORIGIN_JP = 0;
    const ORIGIN_QB = 1;

    const SYNC_REQUEST_ANALYZING = 'Analyzing';

    // Tasks on QuickBooks from JobProgress

    const QUICKBOOKS_CUSTOMER_CREATE = 'Customer Create In QuickBooks';

    const QUICKBOOKS_CUSTOMER_UPDATE = 'Customer Update In QuickBooks';

    const QUICKBOOKS_CUSTOMER_DELETE = 'Customer Delete In QuickBooks';

    const QUICKBOOKS_JOB_CREATE = 'Job Create In QuickBooks';

    const QUICKBOOKS_JOB_UPDATE = 'Job Update In QuickBooks';

    const QUICKBOOKS_JOB_DELETE = 'Job Delete In QuickBooks';

    const QUICKBOOKS_CREDIT_CREATE = 'Credit Create In QuickBooks';

    const QUICKBOOKS_CREDIT_UPDATE = 'Credit Update In QuickBooks';

    const QUICKBOOKS_CREDIT_DELETE = 'Credit Delete In QuickBooks';

    const QUICKBOOKS_PAYMENT_CREATE = 'Payment Create In QuickBooks';

    const QUICKBOOKS_PAYMENT_UPDATE = 'Payment Update In QuickBooks';

    const QUICKBOOKS_PAYMENT_DELETE = 'Payment Delete In QuickBooks';

    const QUICKBOOKS_PAYMENT_APPLY = 'Apply Payment On QuickBooks';

    const QUICKBOOKS_INVOICE_CREATE = 'Invoice Create In QuickBooks';

    const QUICKBOOKS_INVOICE_UPDATE = 'Invoice Update In QuickBooks';

    const QUICKBOOKS_INVOICE_DELETE = 'Invoice Delete In QuickBooks';

    const QUICKBOOKS_BILL_CREATE = 'Bill Create In QuickBooks';

    const QUICKBOOKS_REFUND_CREATE = 'Refund Create In QuickBooks';

    const QUICKBOOKS_ANALYZING_REQUEST = 'Analyzing Sync Request In QuickBooks';

    const QUICKBOOKS_ITEM_CREATE = 'Item Create In QuickBooks';

    const CUSTOMER = 'Customer';

    const DUMP_QBO_CUSTOMER = 'DumpQBOCustomer';

    const CREDIT_MEMO = 'CreditMemo';

    const ITEM = 'Item';

    const PAYMENT = 'Payment';

    const INVOICE = 'Invoice';

    const PAYMENT_METHOD = 'PaymentMethod';

    const JOB = 'Job';

    const GHOST_JOB = 'GhostJob';
    const ACCOUNT = 'Account';
    const VENDOR = 'Vendor';
    const BILL = 'Bill';
    const ATTACHABLE = 'Attachable';
    const REFUND_RECEIPT = 'RefundReceipt';

    const SYNC_REQUEST = 'SyncRequest';

    const SYNC_STATUS_EMPTY       = 0;
    const SYNC_STATUS_IN_PROGRESS = 1;
    const SYNC_STATUS_SUCCESS     = 2;
    const SYNC_STATUS_ERROR       = 3;

    /** Task Sources */
    const SYNC_MANAGER = 'Sync Manager';
    const SYSTEM_EVENT = 'System Event';
    const POLL_EVENT = 'Poll Event';
    const WEBHOOK_EVENT = 'Webhook Event';
    const QUEUE_HANDLER_EVENT = 'Queue Handler Event';

    const QUEUE_ATTEMPTS = 2;

    protected $rules = [
		'name'	=> 'required',
        'payload'	=> 'required|array',
        'created_by' => 'required',
        'company_id' => 'required',
        'origin' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
	}

    public function getPayloadAttribute($value)
    {
        if(is_array(json_decode($value,true))) {

            return json_decode($value,true);
		} else {

			return $value;
		}
	}

    public function setPayloadAttribute($value)
    {
        if(is_array($value)) {

        	$this->attributes['payload'] = json_encode($value);
		} else {

			$this->attributes['payload'] = json_encode([$value]);
		}
    }

    public function setExtraAttribute($value)
    {

        if(is_array($value)) {

        	$this->attributes['extra'] = json_encode($value);
		} else {

			$this->attributes['extra'] = $value;
		}
    }

    public function getExtraAttribute($value)
    {

		if(is_array(json_decode($value,true))) {

			return json_decode($value,true);
		} else {

			return $value;
		}
    }

    public function markStarted($attempt){
        if($attempt == 1){
            $this->queue_started_at = Carbon::now()->toDateTimeString();
        }
        $this->save();
    }

    public function markInProgress(){
        $this->status = self::STATUS_INPROGRESS;
        $this->save();
    }

    /**
     * mark task as failed. Receives failure msg as string
     * after updating the status. It notifies the webhook entry to track it's status
     * and update the status of associated object for the task
     */

    public function markSuccess($entity, $attempt = 1)
    {
        $this->status = self::STATUS_SUCCESS;

        //attach debug information
        $this->qb_object_id = $entity->getQBOId();
        $this->jp_object_id = $entity->id;
        $this->queue_completed_at = Carbon::now()->toDateTimeString();
        $this->queue_attempts = $attempt;

        $this->save();

        $this->notifiyDependents();

        return $entity;
    }
    /**
     * mark task as failed. Receives failure msg as string
     * after updating the status. It notifies the webhook entry to track it's status
     * and update the status of associated object for the task
     */
    public function markFailed($msg = '', $attempt = 1)
    {
        $this->status = Self::STATUS_ERROR;
        $this->msg = $this->msg . ' ' . $msg;
        $this->queue_completed_at = Carbon::now()->toDateTimeString();
        $this->queue_attempts = $attempt;

        $this->save();

        $this->notifiyDependents();

        return true;
    }

    public function isParentTaskComplete(){

        // if this is parent task then return true so that it can continue
        if(!$this->parent_id){
            return true;
        }

        $depTask = self::find($this->parent_id);

        if(in_array($depTask->status, [self::STATUS_PENDING, self::STATUS_INPROGRESS]))
        {
            return false;
        }

        return true;
    }

    public function isParentTaskFailed(){
        // if this is parent task then return false so that it can continue
        if(!$this->parent_id){
            return false;
        }

        $depTask = self::find($this->parent_id);

        return $depTask->status == self::STATUS_ERROR;

    }

    public function reSubmit(){
        $this->status = self::STATUS_PENDING;
        $this->save();
    }

    private function notifiyDependents(){
        // QBOQueue::updateWebhookEntryStatus($this->quickbook_webhook_entry_id, $this->status, $this->msg);
        QBOQueue::updateSyncStatus($this->object, $this->object_id, $this->status, $this->origin);
        QBOQueue::updateCustomerAccountSyncStatus($this->group_id, $this->company_id);
    }
}