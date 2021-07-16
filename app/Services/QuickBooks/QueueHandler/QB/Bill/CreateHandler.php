<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Bill;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Services\QuickBooks\Facades\Vendor as QBVendor;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\Log;

class CreateHandler extends QBBaseTaskHandler
{
    public function __construct() {}

	function getQboEntity($entityId)
    {
        $response = [];
        $response['entity'] = QBBill::get($entityId);
        return  $response;
    }

    function synch($task, $bill)
    {
        $jpBill = QBBill::actionImport($bill['entity']);

        QBBill::createAttachmentTask($bill['entity'], $jpBill, QuickBookTask::CREATE);

        return $jpBill;
    }

    protected function checkPreConditions($bill){

        $bill = $bill['entity'];

        $isValid = QBBill::validateBill($bill);
        if(!$isValid){
            $this->task->markFailed("Invalid or Unsupported details found in this bill. so mark it as failed");
            return false;
        }

        $data = QBBill::getUnsynchedCustomerAndAccountIds($bill);

        if(isset($data['customer_ids'])){
            $customerId =  $data['customer_ids'][0];

            $jpJob = QBBill::getJpJob($customerId);
            $jpCustomer = QBBill::getJpCustomer($customerId);
            $object = null;

            if(!$jpJob && $jpCustomer){
                $object = QuickBookTask::GHOST_JOB;
            }

            if(!$jpJob && !$jpCustomer){
                $object = QuickBookTask::CUSTOMER;
            }
            if($object){
                $task = QBOQueue::addTask($object . ' ' . QuickBookTask::CREATE, [
                        'id' => $customerId,
                    ], [
                        'object_id' => $customerId,
                        'object' => $object,
                        'action' => QuickBookTask::CREATE,
                        'origin' => QuickBookTask::ORIGIN_QB,
                        'created_source' => $this->task->createdSource
                    ]);

                $this->task->parent_id = $task ? $task->id : null;
                $this->task->status = QuickBookTask::STATUS_PENDING;
                $this->task->save();
                return false;
            }

        }

        if(ine($data, 'account_ids')){
            foreach ($data['account_ids'] as $accountId) {
                $task = QBAccount::createTask($accountId, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_QB);
                $taskId = $task ? $task->id : null;
            }

            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }

        //check vendor is synch in JP
        $jpVendor = QBVendor::getJpEntity($bill->VendorRef);
        if(!$jpVendor){
            $task = QBVendor::createTask($bill->VendorRef, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_QB);
            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }

        return true;
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}