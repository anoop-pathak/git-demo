<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Estimate;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Estimate as QBDEstimate;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;

class CreateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(QBDEstimate $qbdEstimate, QBDJob $qbdJob)
    {
        $this->qbdEstimate = $qbdEstimate;
        $this->qbdJob = $qbdJob;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdEstimate->getWorkSheetByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdEstimate->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdEstimate->create($this->qbdEntity, $this->job);
    }

    public function checkPreConditions()
    {
        $qbdEstimate = $this->getQBDEntity();

        $job = $this->qbdJob->getJobByQbdId($qbdEstimate['CustomerRef']);

        if($job) {

            $this->job = $job;

            return true;
        }

        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::JOB,
            'object_id' => $qbdEstimate['CustomerRef'],
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_CUSTOMER,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
        ];

        $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_CUSTOMER, $this->task->qb_username, $taskMeta);

        $this->task->setParentTask($parentTask);

        $this->resubmitted = true;

        return false;
    }
}