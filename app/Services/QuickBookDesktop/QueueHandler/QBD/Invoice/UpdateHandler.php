<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Invoice;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Tax as QBDTax;

class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(
        QBDInvoice $qbdInvoice,
        QBDJob $qbdJob,
        QBDAccount $qbdAccount,
        QBDTax $qbdTax
    ) {
        $this->qbdInvoice = $qbdInvoice;
        $this->qbdJob = $qbdJob;
        $this->qbdAccount = $qbdAccount;
        $this->qbdTax = $qbdTax;
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
        $this->qbdEntity = $this->qbdInvoice->parse($xml);
    }

    function synch($task, $meta)
    {
        $this->task = $task;

        $invoice = $this->qbdInvoice->update($this->qbdEntity, $this->entity);

        return $invoice;
    }

    public function checkPreConditions()
    {
        $qbdInvoice = $this->getQBDEntity();

        $this->job = $this->entity->job;

        if ($qbdInvoice['ItemSalesTaxRef']['ListID']) {

            $tax = $this->qbdTax->getTaxByQbdId($qbdInvoice['ItemSalesTaxRef']['ListID'], $this->task);

            if (!$tax) {

                TaskScheduler::addItemTaxTask($qbdInvoice['ItemSalesTaxRef']['ListID'], $this->task);
                $this->resubmitted = true;
                return false;
            }
        }

        $invoiceLines = $qbdInvoice['InvoiceLineRet'];

        foreach ($invoiceLines as $line) {

            $taxCode = $this->qbdTax->getTaxCodeByQbdId($line['SalesTaxCodeRef']['ListID']);

            if (!$taxCode) {

                TaskScheduler::addTaxCodeTask($line['SalesTaxCodeRef']['ListID'], $this->task);
                $this->resubmitted = true;
                return false;
            }
        }

        return true;
    }
}