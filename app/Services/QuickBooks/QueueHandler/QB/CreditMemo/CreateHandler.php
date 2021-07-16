<?php
namespace App\Services\QuickBooks\QueueHandler\QB\CreditMemo;

use Exception;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Exceptions\GhostJobNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\Facades\QuickBooks;
use Illuminate\Support\Facades\Log;

class CreateHandler extends QBBaseTaskHandler
{

	function getQboEntity($entityId)
    {
        return  QBCreditMemo::get($entityId);
    }

    function synch($task, $credit)
    {
        $credit = QuickBooks::toArray($credit['entity']);

        try {

            $jpCredit = QBCreditMemo::create($credit['Id']);
            return $jpCredit;

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
                'queued_by' => 'create credit memo',
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
                'queued_by' => 'create credit memo',
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
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}