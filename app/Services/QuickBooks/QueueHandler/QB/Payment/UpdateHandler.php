<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Payment;

use Exception;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Exceptions\GhostJobNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\Exceptions\PaymentLineNotSyncedException;
use App\Services\QuickBooks\Exceptions\PaymentMethodNotSyncedException;
use App\Services\QuickBooks\Exceptions\PaymentNotSyncedException;
use App\Services\QuickBooks\Facades\QuickBooks;


class UpdateHandler extends QBBaseTaskHandler
{

	function getQboEntity($entityId)
    {
        return  QBPayment::get($entityId);
    }

    function synch($task, $payment)
    {
        $payment = QuickBooks::toArray($payment['entity']);

        try {

            $jpPayment = QBPayment::update($payment['Id']);
            return $jpPayment;

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
        } catch (PaymentNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'payment_id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::PAYMENT,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'update payment',
            ], [
                'object_id' => $meta['payment_id'],
                'object' => QuickBookTask::PAYMENT,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);

            throw $e;

        } catch (PaymentLineNotSyncedException $e) {

            $lines = $e->getMeta();

            if (empty($lines)) {

                throw $e;
            }

            $lastParentId = null;

            foreach ($lines as $line) {

                if ($line['type'] == 'Invoice') {

                    /**
                     * Check if line task is already created
                     */

                    $tsk = QBOQueue::getTask([
                        'company_id' => getScopeId(),
                        'object_id' => $line['id'],
                        'object' => QuickBookTask::INVOICE,
                        'action' => QuickBookTask::CREATE,
                        'origin' => QuickBookTask::ORIGIN_QB,
                        'group_id' => $task->group_id,
                        'status' => [QuickBookTask::STATUS_PENDING, QuickBookTask::STATUS_INPROGRESS],
                    ]);

                    if (!$tsk) {

                        $name = QBOQueue::getQuickBookTaskName([
                            'object' => QuickBookTask::INVOICE,
                            'operation' => QuickBookTask::CREATE
                        ]);

                        $tsk = QBOQueue::addTask($name, [
                            'queued_by' => 'create payment',
                        ], [
                            'object_id' => $line['id'],
                            'parent_id' => ($lastParentId) ? $lastParentId : null,
                            'object' => QuickBookTask::INVOICE,
                            'action' => QuickBookTask::CREATE,
                            'origin' => QuickBookTask::ORIGIN_QB,
                            'group_id' => $task->group_id,
                            'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
                        ]);
                    }

                    if ($tsk) {
                        $lastParentId = $tsk->id;
                    }
                }

                if ($line['type'] == 'CreditMemo') {

                    /**
                     * Check if line task is already created
                     */

                    $tsk = QBOQueue::getTask([
                        'company_id' => getScopeId(),
                        'object_id' => $line['id'],
                        'object' => QuickBookTask::CREDIT_MEMO,
                        'action' => QuickBookTask::CREATE,
                        'origin' => QuickBookTask::ORIGIN_QB,
                        'group_id' => $task->group_id,
                        'status' => [QuickBookTask::STATUS_PENDING, QuickBookTask::STATUS_INPROGRESS],
                    ]);

                    if (!$tsk) {

                        $name = QBOQueue::getQuickBookTaskName([
                            'object' => QuickBookTask::CREDIT_MEMO,
                            'operation' => QuickBookTask::CREATE
                        ]);

                        $tsk = QBOQueue::addTask($name, [
                            'queued_by' => 'create payment',
                        ], [
                            'object_id' => $line['id'],
                            'parent_id' => ($lastParentId) ? $lastParentId : null,
                            'object' => QuickBookTask::CREDIT_MEMO,
                            'action' => QuickBookTask::CREATE,
                            'origin' => QuickBookTask::ORIGIN_QB,
                            'group_id' => $task->group_id,
                            'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
                        ]);
                    }

                    if ($tsk) {
                        $lastParentId = $tsk->id;
                    }
                }
            }

            if (!$lastParentId) {

                Log::info("Sync Line Itmes: Unable to create line item tasks");
                throw $e;
            }

            $task->parent_id = $lastParentId;

            $task->save();

            return $this->reSubmit();
        } catch (PaymentMethodNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::PAYMENT_METHOD,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'update payment',
            ], [
                'object_id' => $meta['id'],
                'object' => QuickBookTask::PAYMENT_METHOD,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);

            $task->parent_id = $parentTask ? $parentTask->id : $task->parent_id;

            $task->save();

            return $this->reSubmit();

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
        $format = "%s %s failed to  %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}