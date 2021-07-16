<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Job;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Models\QuickbookMappedJob;

class MapHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDJob $qbdJob)
    {
        $this->qbdJob = $qbdJob;
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
        $extra = $task->extra;
        $updateMeta = [];

        $mappedJob = QuickbookMappedJob::where('qb_job_id', $task->object_id)
            ->where('qb_customer_id', $extra['qb_customer_id'])
            ->where('customer_id',  $extra['customer_id'])
            ->whereNotNull('job_id')
            ->first();

        $job = $this->qbdJob->mapJobInQuickBooks($mappedJob->job_id, $this->qbdEntity);

        return $job;
    }
}