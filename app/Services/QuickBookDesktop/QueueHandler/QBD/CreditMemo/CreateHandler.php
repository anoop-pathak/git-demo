<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\CreditMemo;

use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemo;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;

class CreateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(QBDCreditMemo $qbdCreditMemo, QBDJob $qbdJob)
    {
        $this->qbdCreditMemo = $qbdCreditMemo;
        $this->qbdJob = $qbdJob;
        $this->mappedInput = [];
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdCreditMemo->getJobCreditByQbdTxnId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdCreditMemo->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdCreditMemo->create($this->qbdEntity, $this->job, $this->mappedInput);
    }

    public function checkPreConditions()
    {
        $qbdCreditMemo = $this->getQBDEntity();

        $job = $this->qbdJob->getJobByQbdId($qbdCreditMemo['CustomerRef']);

        if (!$job) {
            TaskScheduler::addJobTask($qbdCreditMemo['CustomerRef'], $this->task);
            return $this->reSubmit();
        }

        $this->job = $job;

        $linkedTxns = $this->qbdCreditMemo->getLinkedTxn($qbdCreditMemo);

        if (ine($linkedTxns, 'linked_txns')) {

            $this->mappedInput['linked_txns'] = $linkedTxns['linked_txns'];

            $lastParent = null;
            $hasUnSyncedTxn = false;

            foreach ($linkedTxns['linked_txns'] as $line) {

                if (!ine($line, 'jpId') && $line['type'] == 'invoice') {
                    $hasUnSyncedTxn = true;
                    $lastParent = TaskSCheduler::addInvoiceTask(QuickBookDesktopTask::CREATE, $line['qbId'], $lastParent, $this->task->qb_username);
                }
            }

            if (!$lastParent && $hasUnSyncedTxn) {
                throw new Exception("Sync Linked Txn: Unable to create linked txn tasks");
            }

            if ($hasUnSyncedTxn) {
                $this->task->setParentTask($lastParent);
                return $this->reSubmit();
            }
        }

        return true;
    }
}