<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\DeletedTransaction;

use App\Models\QuickBookDesktopTask;
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
        $transactions = $this->entity->parseDeletedTxn($meta['xml']);

        foreach ($transactions as $entity) {

            $extraParam = [];
            $extraParam['object'] = $entity['TxnDelType'];
            $extraParam['object_id'] = $entity['TxnID'];
            $extraParam['time_deleted'] = $entity['TimeDeleted'];
            $extraParam['action'] = QuickBookDesktopTask::DELETE;

            if ($entity['TxnDelType'] == QuickBookDesktopTask::RECEIVEPAYMENT) {

                $jobPayment = $this->qbdReceivePayment->getJobPaymentByQbdTxnId($entity['TxnID']);

                if ($jobPayment && !$jobPayment->canceled) {
                    $entity['id'] = $jobPayment->id;
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_RECEIVEPAYMENT;
                }
            }

            if ($entity['TxnDelType'] == QuickBookDesktopTask::INVOICE) {

                $jobInvoice = $this->qbdInvoice->getJobInvoiceByQbdTxnId($entity['TxnID']);

                if ($jobInvoice) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_INVOICE;
                    $entity['id'] = $jobInvoice->id;
                }
            }

            if ($entity['TxnDelType'] == QuickBookDesktopTask::CREDIT_MEMO) {

                $jobCredit = $this->qbdCreditMemo->getJobCreditByQbdTxnId($entity['TxnID']);

                if ($jobCredit && !$jobCredit->canceled) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_CREDITMEMO;
                    $entity['id'] = $jobCredit->id;
                }
            }

            if ($entity['TxnDelType'] == QuickBookDesktopTask::BILL) {

                $vendorBill = $this->qbdBill->getBillByQbdId($entity['TxnID']);

                if ($vendorBill) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_BILL;
                    $entity['id'] = $vendorBill->id;
                }
            }

            if (!ine($entity, 'id')) {
            //    Log::warning('Deleted Txn: Transection is not synced');
               continue;
            }

            $extraParam = array_merge($entity, $extraParam);

            $actions = [
                QuickBookDesktopTask::BILL => QUICKBOOKS_IMPORT_BILL,
                QuickBookDesktopTask::ESTIMATE => QUICKBOOKS_IMPORT_ESTIMATE,
                QuickBookDesktopTask::RECEIVEPAYMENT => QUICKBOOKS_IMPORT_RECEIVEPAYMENT,
                QuickBookDesktopTask::CREDIT_MEMO => QUICKBOOKS_IMPORT_CREDITMEMO,
                QuickBookDesktopTask::INVOICE => QUICKBOOKS_IMPORT_INVOICE,
                QuickBookDesktopTask::CHECK => QUICKBOOKS_IMPORT_CHECK,
                QuickBookDesktopTask::CREDITCARDREFUND => QUICKBOOKS_QUERY_CREDITCARDREFUND
            ];

            if (ine($actions, $entity['TxnDelType'])) {
                $this->taskRegistrar->addTask($actions[$entity['TxnDelType']], $meta['user'], $extraParam);
            }
        }
    }
}