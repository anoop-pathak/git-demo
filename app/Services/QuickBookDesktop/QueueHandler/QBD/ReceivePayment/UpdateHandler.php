<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\ReceivePayment;

use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePayment;
use App\Services\QuickBookDesktop\Entity\PaymentMethod as QBDPaymentMethod;
use App\Repositories\PaymentMethodsRepository;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;

class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(
        QBDReceivePayment $qbdReceivePayment,
        QBDJob $qbdJob,
        QBDPaymentMethod $qbdPaymentMethod,
        PaymentMethodsRepository $paymentMethodsRepo
    ) {
        $this->qbdReceivePayment = $qbdReceivePayment;
        $this->qbdJob = $qbdJob;
        $this->qbdPaymentMethod = $qbdPaymentMethod;
        $this->paymentMethodsRepo = $paymentMethodsRepo;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdReceivePayment->getJobPaymentByQbdTxnId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdReceivePayment->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdReceivePayment->update($this->qbdEntity, $this->entity, $this->mappedInput);
    }

    public function checkPreConditions()
    {
        $qbdPayment = $this->getQBDEntity();

        if (empty($qbdPayment['PaymentMethodRef']['ListID'])) {

            $jpPaymentMethod = $this->paymentMethodsRepo->getByLabel("Other");
            $this->mappedInput['method'] = $jpPaymentMethod['method'];
        } else {

            $jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($qbdPayment['PaymentMethodRef']['FullName']);

            if (!$jpPaymentMethod) {

                TaskScheduler::addPaymentMethodTask($qbdPayment['PaymentMethodRef']['ListID'], $this->task);
                return $this->reSubmit();
            }

            $this->mappedInput['method'] = $jpPaymentMethod['method'];
        }

        $paymementLines = $this->qbdReceivePayment->getLines($qbdPayment);

        if (ine($paymementLines, 'lines')) {

            $this->mappedInput['lines'] = $paymementLines['lines'];

            $lastParent = null;
            $hasUnSyncedLine = false;

            foreach ($paymementLines['lines'] as $line) {

                if (!ine($line, 'jpId') && $line['type'] == 'invoice') {

                    $hasUnSyncedLine = true;

                    $tsk = TaskSCheduler::addInvoiceTask(QuickBookDesktopTask::CREATE, $line['qbId'], $lastParent, $this->task->qb_username);

                    if ($tsk) {
                        $lastParent = $tsk;
                    }
                }

                if (!ine($line, 'jpId') && $line['type'] == 'credit_memo') {

                    $hasUnSyncedLine = true;

                    $tsk = TaskSCheduler::addCreditMemoTask($line['qbId'], $lastParent, $this->task->qb_username);

                    if ($tsk) {
                        $lastParent = $tsk;
                    }
                }
            }

            if (!$lastParent && $hasUnSyncedLine) {
                throw new Exception("Sync Line Itmes: Unable to create line item tasks");
            }

            if ($hasUnSyncedLine) {

                $this->task->setParentTask($lastParent);
                return $this->reSubmit();
            }
        }

        $job = $this->qbdJob->getJobByQbdId($qbdPayment['CustomerRef']);

        $this->job = $job;

        return true;
    }
}
