<?php
namespace App\Services\QuickBookDesktop;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\TaskScheduler;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Setting\Time;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class SyncTask
{
    public $username = null;

    public function __construct(TaskScheduler $taskScheduler, Settings $settings, Time $time)
    {
        $this->taskScheduler = $taskScheduler;
        $this->settings = $settings;
        $this->timeSettings = $time;
    }

    public function addSyncTasks($username)
    {
        $this->username = $username;
        $this->importCustomerAndJob();
        $this->importTaxes();

        if($this->isTwoWayEnable($username)){

            $this->importPaymenMethods();
            $this->importTansactions();
            $this->importAccounts();
            $this->importItems();
            $this->importDeletedTxns();
            $this->importVendors();
        }
        // $this->importUnitMeasurement();
    }

	public function importCustomerAndJob()
	{
        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_CUSTOMER, $this->username, [
            'action' => QuickBookDesktopTask::IMPORT,
            'object' => QuickBookDesktopTask::CUSTOMER,
            'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_CUSTOMER,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::POLL_EVENT
        ]);
    }

    public function importTansactions()
    {
        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_TRANSACTION, $this->username, [
            'action' => QuickBookDesktopTask::IMPORT,
            'object' => QuickBookDesktopTask::TRANSACTION,
            'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_TRANSACTION,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::POLL_EVENT
        ]);
    }

    public function importPaymenMethods()
    {
        $subTime = Carbon::now()->subHours(QuickBookDesktopTask::IMPORT_PAYMENT_METHOD_DURATION)->toDateTimeString();
        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_PAYMENTMETHOD)){
            $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_PAYMENTMETHOD, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::PAYMENT_METHOD,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_PAYMENTMETHOD,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);
        }
    }


    public function importTaxes()
    {
        $parent = null;
        $subTime = Carbon::now()->subHours(QuickBookDesktopTask::IMPORT_TAXES_DURATION)->toDateTimeString();
        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_SALESTAXCODE)){
            $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_SALESTAXCODE, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::SALES_TAX_CODE,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_SALESTAXCODE,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);
        }

        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_SALESTAXITEM)){

            $parent = $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_SALESTAXITEM, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::ITEM_SALES_TAX,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_SALESTAXITEM,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);
        }

        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_SALESTAXGROUPITEM)){

            $child = $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_SALESTAXGROUPITEM, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::ITEM_SALES_TAX_GROUP,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_SALESTAXGROUPITEM,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);

            if($parent){
                $child->setParentTask($parent);
            }

        }
    }

    public function importVendors()
    {
        $subTime = Carbon::now()->subHours(QuickBookDesktopTask::IMPORT_VENDOR_DURATION)->toDateTimeString();
        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_VENDOR)){
            $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_VENDOR, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::VENDOR,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_VENDOR,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);
        }
    }

    public function importAccounts()
    {
        $subTime = Carbon::now()->subHours(QuickBookDesktopTask::IMPORT_ACCOUNT_DURATION)->toDateTimeString();
        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_ACCOUNT)){
            $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_ACCOUNT, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::ACCOUNT,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_ACCOUNT,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);
        }
    }

    public function importItems()
    {
        $subTime = Carbon::now()->subHours(QuickBookDesktopTask::IMPORT_ITEM_DURATION)->toDateTimeString();
        if($subTime > $this->timeSettings->getCDCLastRun($this->username, QUICKBOOKS_IMPORT_ITEM)){
            $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_ITEM, $this->username, [
                'action' => QuickBookDesktopTask::IMPORT,
                'object' => QuickBookDesktopTask::ITEM,
                'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_ITEM,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::POLL_EVENT
            ]);
        }
    }

    public function importUnitMeasurement()
    {
        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_UNITOFMEASURESET, $this->username, [
            'action' => QuickBookDesktopTask::IMPORT,
            'object' => QuickBookDesktopTask::UNIT_OF_MEASUREMENT,
            'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_UNITOFMEASURESET,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::POLL_EVENT
        ]);
    }

    public function importDeletedTxns()
    {
        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_DELETEDTXNS, $this->username, [
            'action' => QuickBookDesktopTask::IMPORT,
            'object' => QuickBookDesktopTask::DELETED_TRANSACTION,
            'priority' => QuickBookDesktopTask::IMPORT_DELETEDTXNS,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::POLL_EVENT
        ]);
    }

    public function addDumpTasks($username, $meta=[])
    {
        $meta['group_id'] = generateUniqueToken();

        $this->addDumpCustomerTask($username, $meta);
        $this->addDumpInvoiceTask($username, $meta);
        $this->addDumpPaymentTask($username, $meta);
        $this->addDumpCreditMemoTask($username, $meta);
        $this->addDumpBillTask($username, $meta);
    }

    public function addDumpCustomerTask($username, $meta)
    {
        $meta =  [
            'action' => QuickBookDesktopTask::DUMP,
            'batch_id' => ine($meta, 'batch_id') ? $meta['batch_id'] : null,
            'group_id' => ine($meta, 'group_id') ? $meta['group_id'] : null,
            'object' => QuickBookDesktopTask::CUSTOMER,
            'priority' => QuickBookDesktopTask::PRIORITY_IMPORT_CUSTOMER,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_CUSTOMER, $username, $meta);
    }

    public function addDumpInvoiceTask($username, $meta)
    {
        $meta =  [
            'action' => QuickBookDesktopTask::DUMP,
            'batch_id' => ine($meta, 'batch_id') ? $meta['batch_id'] : null,
            'group_id' => ine($meta, 'group_id') ? $meta['group_id'] : null,
            'object' => QuickBookDesktopTask::INVOICE,
            'priority' => QuickBookDesktopTask::PRIORITY_DUMP_IMPORT_ENTITIES,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_INVOICE, $username, $meta);
    }

    public function addDumpPaymentTask($username, $meta)
    {
        $meta =  [
            'action' => QuickBookDesktopTask::DUMP,
            'batch_id' => ine($meta, 'batch_id') ? $meta['batch_id'] : null,
            'group_id' => ine($meta, 'group_id') ? $meta['group_id'] : null,
            'object' => QuickBookDesktopTask::RECEIVEPAYMENT,
            'priority' => QuickBookDesktopTask::PRIORITY_DUMP_IMPORT_ENTITIES,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_RECEIVEPAYMENT, $username, $meta);
    }

    public function addDumpCreditMemoTask($username, $meta)
    {
        $meta =  [
            'action' => QuickBookDesktopTask::DUMP,
            'batch_id' => ine($meta, 'batch_id') ? $meta['batch_id'] : null,
            'group_id' => ine($meta, 'group_id') ? $meta['group_id'] : null,
            'object' => QuickBookDesktopTask::CREDIT_MEMO,
            'priority' => QuickBookDesktopTask::PRIORITY_DUMP_IMPORT_ENTITIES,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_CREDITMEMO, $username, $meta);
    }

    public function addDumpBillTask($username, $meta)
    {
        $meta =  [
            'action' => QuickBookDesktopTask::DUMP,
            'batch_id' => ine($meta, 'batch_id') ? $meta['batch_id'] : null,
            'group_id' => ine($meta, 'group_id') ? $meta['group_id'] : null,
            'object' => QuickBookDesktopTask::BILL,
            'priority' => QuickBookDesktopTask::PRIORITY_DUMP_IMPORT_ENTITIES,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $this->taskScheduler->addTask(QUICKBOOKS_IMPORT_BILL, $username, $meta);
    }

    public function isTwoWayEnable($user)
    {
        try{
            if(!$this->settings->setCompanyScope($user)) {
                return false;
            }

            $settings = $this->settings->getSettings(getScopeId());

            if(ine($settings, 'sync_type') && ($settings['sync_type'] == 'two_way')) {
                return true;
            }
            return false;
        }catch(Exception $e){
            Log::info('QuickBook Desktop SyncTask Exception');
            Log::info($e->getMessage());
        }
    }


}