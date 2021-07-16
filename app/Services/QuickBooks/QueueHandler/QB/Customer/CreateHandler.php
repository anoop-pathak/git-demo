<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Customer;

use Exception;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Exceptions\CustomerDuplicateException;
use App\Services\QuickBooks\Exceptions\CustomerValidationException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Repositories\JobRepository;
use App\Events\CustomerCreated;
use Solr;
use Illuminate\Support\Facades\Event;

class CreateHandler extends QBBaseTaskHandler
{
    public function __construct(Notification $notification, JobRepository $jobRepo) {
        $this->notification = $notification;
        $this->jobRepo = $jobRepo;
    }

	function getQboEntity($entityId)
    {
        return  QBCustomer::get($entityId);
    }

    function synch($task, $customer)
    {
        $customer = QuickBooks::toArray($customer['entity']);
        try {

            $jpCustomer = QBCustomer::create($customer['Id'], $task);

            if($jpCustomer && $jpCustomer->customer_id){
                $this->jobRepo->qbGenerateJobNumber($jpCustomer);
                Solr::jobIndex($jpCustomer->id);
            }

            if($jpCustomer && !$jpCustomer->customer_id){
                Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($jpCustomer->id));
            }


            return $jpCustomer;

        } catch(ParentCustomerNotSyncedException $e) {

            $meta = $e->getMeta();

            if(!ine($meta, 'parent_customer_id')) {

                throw $e;
            }

            $name = QBOQueue::getQuickBookTaskName([
                'object' => QuickBookTask::CUSTOMER,
                'operation' => QuickBookTask::CREATE
            ]);

            $parentTask = QBOQueue::addTask($name, [
                'queued_by' => 'create_job',
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

        } catch(CustomerValidationException $e) {

            $this->notification->send("Customer details are not valid so can't be saved.", Auth::user()->id);
            throw $e;

        } catch(CustomerDuplicateException $e) {

            $this->notification->send("Customer details are not valid so can't be saved.", Auth::user()->id);

           throw $e;
        } catch(Exception $e) {

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
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}