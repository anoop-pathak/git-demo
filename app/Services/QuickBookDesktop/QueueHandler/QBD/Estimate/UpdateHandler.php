<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Estimate;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;

class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(QBDInvoice $qbdInvoice, QBDJob $qbdJob)
    {
        $this->qbdInvoice = $qbdInvoice;
        $this->qbdJob = $qbdJob;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdInvoice->getWorkSheetByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdInvoice->parse($xml);
    }

    function synch($task, $meta)
    {
        $this->task = $task;

        $invoice = $this->qbdInvoice->update($this->qbdEntity, $this->entity);

        return $invoice;
    }

    public function checkPreConditions()
    {
        $qbdInvoice = $this->getQBDEntity();

        $job = $this->qbdJob->getJobByQbdId($qbdInvoice['CustomerRef']);

        if ($job) {

            $this->job = $job;

            return true;
        }

        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::JOB,
            'object_id' => $qbdInvoice['CustomerRef'],
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_JOB,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
        ];

        $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_CUSTOMER, $this->task->qb_username, $taskMeta);

        $this->task->setParentTask($parentTask);

        $this->resubmitted = true;

        return false;
    }
}