<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Job;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;

class UpdateHandler extends BaseTaskHandler
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
        return $this->qbdJob->update($this->qbdEntity, $this->entity);
    }

    public function checkPreConditions()
    {
        $qbdJob = $this->qbdEntity;

         if($qbdJob['SubLevel'] != '0'){
            $isValid = $this->qbdJob->validateQBSubCustomer($qbdJob);

            if(!$isValid){
                return $isValid;
            }
        }

        return true;
    }
}
