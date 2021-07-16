<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Bill;

use Exception;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBill;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;
use App\Services\QuickBookDesktop\Entity\Vendor as QBDVendor;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;

class CreateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    public $job = null;

    public $customer = null;

    private $qbdEntity = null;

    public function __construct(
        QBDBill $qbdBill,
        QBDJob $qbdJob,
        QBDCustomer $qbdCustomer,
        QBDVendor $qbdVendor,
        QBDAccount $qbdAccount
    ) {
        $this->qbdBill = $qbdBill;
        $this->qbdJob = $qbdJob;
        $this->qbdCustomer = $qbdCustomer;
        $this->qbdVendor = $qbdVendor;
        $this->qbdAccount = $qbdAccount;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdBill->getBillByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdBill->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdBill->create($this->qbdEntity, $this->job);
    }

    public function checkPreConditions()
    {
        $qbdEntity = $this->getQBDEntity();

        $customers = [];

        if (ine($qbdEntity, 'ItemLineRet')) {
            throw new Exception("Bill can not be synced.");
        }

        $vandor = $this->qbdVendor->getVendorByQbdId($qbdEntity['VendorRef']);

        if(!$vandor) {
            throw new Exception("Vendor not synced.");
        }

        if (!ine($qbdEntity, 'ExpenseLineRet')) {
            throw new Exception("Bill can not be synced.");
        }

        foreach ($qbdEntity['ExpenseLineRet'] as $line) {

            $account = $this->qbdAccount->getAccountByQbdId($line['AccountRef']);

            if (!$account) {
                throw new Exception("Line account not synced.");
            }

            $customers[] = $line['CustomerRef'];
        }

        if (count(arry_fu($customers)) != 1) {
            throw new Exception("Multiple or No customer assigned to Line Items", 1);
        }

        return true;
    }
}