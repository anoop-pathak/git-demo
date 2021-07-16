<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Bill;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBillEntity;
use App\Services\QuickBookDesktop\Entity\Job as QBDJobEntity;
use App\Models\QBDBill;

class DeleteFinancialHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(QBDBillEntity $qbdBill, QBDJobEntity $qbdJob)
    {
        $this->qbdBill = $qbdBill;
        $this->qbdJob = $qbdJob;
    }

    public function getEntity($qbdId)
    {
        $this->entity = [];

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
        $bill = QBDBill::where('qb_desktop_txn_id', $task->object_id)
           ->where('company_id', getScopeId())
           ->delete();
        return $bill;
    }
}