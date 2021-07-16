<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\CreditMemo;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemoEntity;
use App\Services\QuickBookDesktop\Entity\Job as QBDJobEntity;
use App\Models\QBDCreditMemo;

class DeleteFinancialHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(QBDCreditMemoEntity $qbdCredit, QBDJobEntity $qbdJob)
    {
        $this->qbdCredit = $qbdCredit;
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
        $credit = QBDCreditMemo::where('qb_desktop_txn_id', $task->object_id)
           ->where('company_id', getScopeId())
           ->delete();
        return $credit;
    }
}