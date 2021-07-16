<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Job;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;

class CreateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDJob $qbdJob, QBDCustomer $qbdCustomer)
    {
        $this->qbdJob = $qbdJob;
        $this->qbdCustomer = $qbdCustomer;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdJob->getJobByQbdId($qbdId);
        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdJob->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdJob->create($this->qbdEntity);
    }

    public function checkPreConditions()
    {
        $qbdEntity = $this->qbdEntity;

        $parentId = $qbdEntity['ParentRef'];

        if($qbdEntity['SubLevel'] != '0'){
            $isValid = $this->qbdJob->validateQBSubCustomer($qbdEntity);

            if(!$isValid){
                return $isValid;
            }
        }

        // if qbd entity is customer
        if ($qbdEntity['SubLevel'] == '0') {
            $customer = $this->qbdCustomer->getCustomerByQbdId($qbdEntity['ListID']);

            if ($customer) {
                return true;
            }

            $taskMeta = [
                'action' => QuickBookDesktopTask::CREATE,
                'object' => QuickBookDesktopTask::CUSTOMER,
                'object_id' => $qbdEntity['ListID'],
                'priority' => QuickBookDesktopTask::PRIORITY_ADD_CUSTOMER,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
            ];

            $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_CUSTOMER, $this->task->qb_username, $taskMeta);

            $this->task->setParentTask($parentTask);

            $this->resubmitted = true;

            return false;
        }

        if ($qbdEntity['SubLevel'] == '1') {

            $customer = $this->qbdCustomer->getCustomerByQbdId($parentId);

            if ($customer) {
                return true;
            }

            $taskMeta = [
                'action' => QuickBookDesktopTask::CREATE,
                'object' => QuickBookDesktopTask::CUSTOMER,
                'object_id' => $parentId,
                'priority' => QuickBookDesktopTask::PRIORITY_ADD_CUSTOMER,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
            ];

            $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_CUSTOMER, $this->task->qb_username, $taskMeta);

            $this->task->setParentTask($parentTask);

            $this->resubmitted = true;

            return false;
        }

        return true;
    }
}