<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Invoice;

use Exception;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Exceptions\GhostJobNotSyncedException;
use App\Services\QuickBooks\Exceptions\InvoiceNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\Facades\QuickBooks;

class UpdateHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  QBInvoice::get($entityId);
    }

    function synch($task, $invoice)
    {
        $invoice = QuickBooks::toArray($invoice['entity']);

        try {

            $jpInvoice = QBInvoice::update($invoice['Id'], $task);
            return $jpInvoice;

        } catch (ParentCustomerNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'parent_customer_id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::CUSTOMER,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'update invoice',
            ], [
                'object_id' => $meta['parent_customer_id'],
                'object' => QuickBookTask::CUSTOMER,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);

            $task->parent_id = $parentTask ? $parentTask->id : $task->parent_id;

            $task->save();

            return $this->reSubmit();


        } catch (JobNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'job_id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::CUSTOMER,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'update invoice',
            ], [
                'object_id' => $meta['job_id'],
                'object' => QuickBookTask::CUSTOMER,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);

            $task->parent_id = $parentTask ? $parentTask->id : $task->parent_id;

            $task->save();

            return $this->reSubmit();

        } catch (InvoiceNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'invoice_id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::INVOICE,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'update invoice',
            ], [
                'object_id' => $meta['invoice_id'],
                'object' => QuickBookTask::INVOICE,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);

            return $this->failSilently("converted to creation task");

        } catch (GhostJobNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'customer_id')) {

                throw $e;
            }

            $tsk = QBOQueue::getTask([
                'company_id' => getScopeId(),
                'object_id' => $meta['customer_id'],
                'object' => QuickBookTask::GHOST_JOB,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
            ]);

            if (!$tsk) {

                $name = QBOQueue::getQuickBookTaskName([
                    'object' => QuickBookTask::GHOST_JOB,
                    'operation' => QuickBookTask::CREATE
                ]);

                $tsk = QBOQueue::addTask($name, [
                    'queued_by' => 'create invoice',
                ], [
                    'object_id' => $meta['customer_id'],
                    'object' => QuickBookTask::GHOST_JOB,
                    'action' => QuickBookTask::CREATE,
                    'origin' => QuickBookTask::ORIGIN_QB,
                    'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
                ]);
            }

            if (!$tsk) {
                Log::error("Unable to create ghost job task");
                throw $e;
            }

            $parentTask = $tsk;

            $task->parent_id = $parentTask->id;

            $task->save();

            return $this->reSubmit();

        } catch (Exception $e) {

            throw $e;
        }
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);

        return $message;
    }
}