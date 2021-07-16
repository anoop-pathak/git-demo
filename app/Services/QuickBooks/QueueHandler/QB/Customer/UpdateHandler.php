<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Customer;

use Exception;
use QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\Exceptions\CustomerNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Facades\QuickBooks;
use Illuminate\Support\Facades\Log;
use App\Events\CustomerUpdated;
use Solr;
use Illuminate\Support\Facades\Event;

class UpdateHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  QBCustomer::get($entityId);
    }

    function synch($task, $customer)
    {
        $customer = QuickBooks::toArray($customer['entity']);
        try {

            $jpCustomer = QBCustomer::update($customer['Id'], $task);

            if($jpCustomer){
                if($jpCustomer->customer_id){
                    Solr::jobIndex($jpCustomer->id);
                }else{
                    Event::fire('JobProgress.Customers.Events.CustomerUpdated', new CustomerUpdated($jpCustomer->id));
                }
            }
            return $jpCustomer;

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
                'queued_by' => 'update_job',
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

        } catch (CustomerNotSyncedException $e) {

            $meta = $e->getMeta();

            if (!ine($meta, 'customer_id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::CUSTOMER,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'update customer hander',
            ], [
                'object_id' => $meta['customer_id'],
                'object' => QuickBookTask::CUSTOMER,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);
            return $this->failSilently("converted to creation task");
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
                'queued_by' => 'update customer hander',
            ], [
                'object_id' => $meta['job_id'],
                'object' => QuickBookTask::CUSTOMER,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_QB,
                'created_source' => QuickBookTask::QUEUE_HANDLER_EVENT
            ]);

            return $this->failSilently("converted to creation task");

        } catch (Exception $e) {

            throw $e;
        }
    }

    public function checkPreConditions($customerEntity)
    {
        $isValid = true;
        $customer = $customerEntity['entity'];

        if($customer->Job == 'true'){
            $isValid = QBCustomer::validateQBSubCustomer($customer);

            if(!$isValid){
                $this->task->markFailed("This entity is invalid or doesn't support by our system. so mark it as failed");
                return $isValid;
            }
        }

        return $isValid;

    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}