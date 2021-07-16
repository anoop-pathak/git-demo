<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Transaction;

use App\Models\QuickBookDesktopTask;
use App\Models\TransactionUpdatedTime;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Transaction;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePayment;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemo;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBill;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;


class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        Transaction $entity,
        QBDReceivePayment $qbdReceivePayment,
        QBDInvoice $qbdInvoice,
        QBDCreditMemo $qbdCreditMemo,
        QBDBill $qbdBill,
        TaskRegistrar $taskRegistrar
    ) {
        $this->entity = $entity;
        $this->qbdReceivePayment = $qbdReceivePayment;
        $this->qbdInvoice = $qbdInvoice;
        $this->qbdCreditMemo = $qbdCreditMemo;
        $this->qbdBill = $qbdBill;
        $this->taskRegistrar = $taskRegistrar;
    }

    public function getEntity()
    {
       return false;
    }

    function synch($task, $meta)
    {

        $transactions = $this->entity->parse($meta['xml']);

        foreach ($transactions as $entity) {

            $extraParam = [];
            $extraParam['object'] = $entity['type'];
            $extraParam['ident'] = $entity['qb_desktop_txn_id'];
            $extraParam['object_id'] = $entity['qb_desktop_txn_id'];
            $extraParam['object_last_updated'] = $entity['object_last_updated'];

            if ($entity['type'] == QuickBookDesktopTask::RECEIVEPAYMENT) {

                $jobPayment = $this->qbdReceivePayment->getJobPaymentByQbdTxnId($entity['qb_desktop_txn_id']);

                if ($jobPayment) {
                    $entity['id'] = $jobPayment->id;
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_RECEIVEPAYMENT;
                } else {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_RECEIVEPAYMENT;
                }
            }

            if ($entity['type'] == QuickBookDesktopTask::INVOICE) {

                $jobInvoice = $this->qbdInvoice->getJobInvoiceByQbdTxnId($entity['qb_desktop_txn_id']);

                if ($jobInvoice) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_INVOICE;
                    $entity['id'] = $jobInvoice->id;
                } else {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_INVOICE;
                }
            }

            if ($entity['type'] == QuickBookDesktopTask::CREDIT_MEMO) {

                $jobCredit = $this->qbdCreditMemo->getJobCreditByQbdTxnId($entity['qb_desktop_txn_id']);

                if ($jobCredit) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_CREDITMEMO;
                    $entity['id'] = $jobCredit->id;
                } else {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_CREDITMEMO;
                }
            }

            if ($entity['type'] == QuickBookDesktopTask::BILL) {

                $vendorBill = $this->qbdBill->getBillByQbdId($entity['qb_desktop_txn_id']);

                if ($vendorBill) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_BILL;
                    $entity['id'] = $vendorBill->id;
                } else {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_BILL;
                }
            }

            if (ine($entity, 'id')) {
                $extraParam['action'] = QuickBookDesktopTask::UPDATE;
            } else {
                $extraParam['action'] = QuickBookDesktopTask::CREATE;
            }

            $extraParam = array_merge($entity, $extraParam);

            $actions = [
                QuickBookDesktopTask::BILL => QUICKBOOKS_IMPORT_BILL,
                QuickBookDesktopTask::CREDIT_MEMO => QUICKBOOKS_IMPORT_CREDITMEMO,
                QuickBookDesktopTask::RECEIVEPAYMENT => QUICKBOOKS_IMPORT_RECEIVEPAYMENT,
                QuickBookDesktopTask::INVOICE => QUICKBOOKS_IMPORT_INVOICE
            ];

            if($extraParam['action'] == QuickBookDesktopTask::UPDATE
                && TransactionUpdatedTime::where([
                'company_id' => getScopeId(),
                'type' => $entity['type'],
                'qb_desktop_txn_id' => $entity['qb_desktop_txn_id'],
                'object_last_updated' => $entity['object_last_updated']
            ])->first()) {
                // Log::warning("Transaction already update", [$entity['type']]);
                continue;
            }

            if(ine($actions, $entity['type'])) {
                $this->taskRegistrar->addTask($actions[$entity['type']], $meta['user'], $extraParam);
            }
        }
    }
}