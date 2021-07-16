<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Invoice;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;

class DeleteHandler extends BaseTaskHandler
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
        $this->entity = $this->qbdInvoice->getJobInvoiceByQbdTxnId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = [];
    }

    function synch($task, $meta)
    {
        return $this->qbdInvoice->delete($this->entity);
    }

    public function checkPreConditions()
    {
        if(!$this->entity) {
            return false;
        }

        return true;
    }
}