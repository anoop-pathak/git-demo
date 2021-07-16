<?php
namespace App\Models;

use App\Services\QuickBookDesktop\SyncStatus;
use App;

class QuickBookDesktopTask extends BaseModel
{
    protected $table = 'quickbooks_queue';

    protected $primaryKey = 'quickbooks_queue_id';

    public $timestamps = false;

    protected $fillable = [];

    const PRIORITY_DELETE_TRANSACTION = 223;

    const PRIORITY_MOD_RECEIVEPAYMENT = 224;
    const PRIORITY_ADD_RECEIVEPAYMENT = 225;

    const PRIORITY_MOD_BILL = 226;
    const PRIORITY_ADD_BILL = 227;

    const PRIORITY_MOD_ESTIMATE = 230;
    const PRIORITY_ADD_ESTIMATE = 231;

    const PRIORITY_MOD_CREDITMEMO = 232;
    const PRIORITY_ADD_CREDITMEMO = 233;

    const PRIORITY_MOD_INVOICE = 234;
    const PRIORITY_ADD_INVOICE = 235;

    const PRIORITY_MOD_PAYMENTMETHOD = 234;
    const PRIORITY_ADD_PAYMENTMETHOD = 235;

    const PRIORITY_MOD_ITEM = 234;
    const PRIORITY_ADD_ITEM = 235;

    const PRIORITY_MOD_UNIT_MEASUREMENT = 236;
    const PRIORITY_ADD_UNIT_MEASUREMENT = 237;

    const PRIORITY_MOD_VENDOR = 236;
    const PRIORITY_ADD_VENDOR = 237;

    const PRIORITY_ADD_SALESTAXGROUPITEM = 237;
    const PRIORITY_ADD_SALESTAXITEM = 237;
    const PRIORITY_ADD_SALESTAXCODE = 237;

    const PRIORITY_MOD_JOB = 238;
    const PRIORITY_ADD_JOB = 239;

    const PRIORITY_MOD_CUSTOMER = 240;
    const PRIORITY_ADD_CUSTOMER = 241;

    const PRIORITY_MOD_ACCOUNT = 242;
    const PRIORITY_ADD_ACCOUNT = 243;

    const IMPORT_DELETEDTXNS = 196;
    const PRIORITY_IMPORT_VENDOR =197;
    const PRIORITY_IMPORT_RECEIVEPAYMENT = 2;
    const PRIORITY_IMPORT_INVOICE = 2;
    const PRIORITY_IMPORT_ESTIMATE = 3;
    const PRIORITY_IMPORT_TRANSACTION = 198;
    const PRIORITY_IMPORT_BILL = 5;
    const PRIORITY_IMPORT_ITEM = 191;
    const PRIORITY_IMPORT_UNITOFMEASURESET = 1;
    const PRIORITY_IMPORT_ACCOUNT = 197;
    const PRIORITY_IMPORT_PAYMENTMETHOD = 192;
    const PRIORITY_IMPORT_SALESTAXGROUPITEM = 193;
    const PRIORITY_IMPORT_SALESTAXITEM = 194;
    const PRIORITY_IMPORT_SALESTAXCODE = 195;

    const PRIORITY_SYNC_REQUEST = 150;
    const PRIORITY_IMPORT_JOB = 12;

    const PRIORITY_IMPORT_CUSTOMER = 199;
    const PRIORITY_DUMP_IMPORT_ENTITIES = 200;
    const PRIORITY_MAP_CUSTOMER = 250;
    const PRIORITY_MAP_JOB = 249;
    const PRIORITY_DELETE_FINANCIAL = 248;
    const PRIORITY_CUSTOMER_DUMP_UPDATE = 299;
    const PRIORITY_ENTITY_DUMP_UPDATE = 300;

    /**
     * Queuing status for queued QuickBooks transactions - QUEUED
     */
    const STATUS_QUEUED = 'q';
    /**
     * QuickBooks status for queued QuickBooks transactions - was queued, then SUCCESSFULLY PROCESSED
     */
    const STATUS_SUCCESS = 's';
    /**
     * QuickBooks status for queued QuickBooks transactions - was queued, an ERROR OCCURED when processing it
     */
    const STATUS_ERROR = 'e';
    /**
     * QuickBooks status for items that have been dequeued and are being processed by QuickBooks (we assume) but we havn't received a response back about them yet
     */
    const STATUS_INPROGRESS = 'i';
    /**
     * QuickBooks status for items that were dequeued, had an error occured, and then the error was handled by an error handler
     */
    const STATUS_HANDLED = 'h';
    /**
     * QuickBooks status for items that were cancelled
     */
    const STATUS_CANCELLED = 'c';
    /**
     * QuickBooks status for items that were removred forcefuly
     */
    const STATUS_REMOVED = 'r';
    /**
     * QuickBooks status for items that were NoOp
     */
    const STATUS_NOOP = 'n';

    const CREATE = 'Create';
    const QUERY = 'Query';
    const UPDATE = 'Update';
    const DELETE = 'Delete';
    const SYNC = 'Sync';
    const IMPORT = 'Import';
    const APPLY = 'Apply';
    const MAP = 'Map';
    const DELETE_FINANCIAL = 'DeleteFinancial';
    const DUMP = 'Dump';
    const DUMP_UPDATE = 'DumpUpdate';
    const SYNC_ALL = 'SyncAll';

    const CUSTOMER = 'Customer';
    const CREDIT_MEMO = 'CreditMemo';
    const ITEM = 'Item';
    const PAYMENT = 'Payment';
    const RECEIVEPAYMENT = 'ReceivePayment';
    const INVOICE = 'Invoice';
    const PAYMENT_METHOD = 'PaymentMethod';
    const JOB = 'Job';
    const GHOST_JOB = 'GhostJob';
    const SYNC_REQUEST = 'SyncRequest';
    const TRANSACTION = 'Transaction';
    const ACCOUNT = 'Account';
    const VENDOR = 'Vendor';
    const BILL = 'Bill';
    const ATTACHABLE = 'Attachable';
    const ESTIMATE = 'Estimate';
    const CHECK = 'Check';
    const CREDITCARDREFUND = 'ARRefundCreditCard';

    const UNIT_OF_MEASUREMENT = 'UnitOfMeasurement';
    const DELETED_TRANSACTION = 'DeletedTransaction';

    const SALES_TAX_CODE = 'SalesTaxCode';
    const ITEM_SALES_TAX = 'ItemSalesTax';
    const ITEM_SALES_TAX_GROUP = 'ItemSalesTaxGroup';

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

    const ORIGIN_JP = 0;
    const ORIGIN_QB = 1;
    const ORIGIN_QBD = 2;

    const MAX_TIME_PERIOD = 1; //In days

    // Duration for cdc task(In Hours)
    const IMPORT_PAYMENT_METHOD_DURATION = 8;
    const IMPORT_TAXES_DURATION = 2;
    const IMPORT_ACCOUNT_DURATION = 2;
    const IMPORT_ITEM_DURATION = 2;
    const IMPORT_VENDOR_DURATION = 2;

    public function getExtraAttribute($value)
    {
		if($value && is_array(unserialize($value))) {
			return unserialize($value);
		} else {
			return $value;
		}
    }

    public function markInProgress($meta = [])
    {
        $this->status = self::STATUS_INPROGRESS;
        $this->save();
    }



    public function markSuccess($entity = null)
    {
        $this->status = self::STATUS_SUCCESS;

        if ($entity &&
            $this->action != self::IMPORT &&
            $this->action != self::DELETE &&
            $this->action != self::DUMP &&
            $this->action != self::DUMP_UPDATE &&
            $this->action != self::SYNC_ALL
        ) {
            $this->qb_object_id = $entity->getQBDId();
            $this->jp_object_id = $entity->id;
        }

        $this->save();

        $this->notifiyDependents();

        return $entity;
    }

    public function markFailed($msg = '')
    {
        $this->status = Self::STATUS_ERROR;
        $this->qb_status = Self::STATUS_ERROR;
        $this->message = $this->message . ' ' . $msg;
        $this->save();

        $this->notifiyDependents();

        return true;
    }

    public function isParentTaskComplete()
    {
        // if this is parent task then return true so that it can continue
        if(!$this->parent_id) {
            return true;
        }

        $depTask = self::find($this->parent_id);

        if(in_array($depTask->status, [self::STATUS_QUEUED, self::STATUS_INPROGRESS])) {
            return false;
        }

        return true;
    }

    public function isParentTaskFailed()
    {
        // if this is parent task then return false so that it can continue
        if(!$this->parent_id) {
            return false;
        }

        $depTask = self::find($this->parent_id);

        return $depTask->status == self::STATUS_ERROR;
    }

    public function reSubmit()
    {
        $this->status = self::STATUS_QUEUED;
        $this->qb_status = self::STATUS_QUEUED;
        $this->save();
    }

    public function setParentTask(QuickBookDesktopTask $parentTask)
    {
        $this->parent_id = $parentTask->quickbooks_queue_id;
        $this->save();
    }

    private function notifiyDependents()
    {
        App::make(SyncStatus::class)->update($this->object, $this->object_id, $this->status, $this->origin);
        App::make(SyncStatus::class)->updateCustomerAccountSyncStatus($this->group_id, $this->qb_username);
    }
}