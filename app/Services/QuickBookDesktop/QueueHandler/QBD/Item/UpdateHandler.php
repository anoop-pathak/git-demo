<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Item;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Item as QBDItem;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Tax as QBDTax;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;


class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    private $extra = [];

    public function __construct(
        QBDItem $qbdItem,
        QBDAccount $qbdAccount,
        QBDTax $qbdTax
    ) {
        $this->qbdItem = $qbdItem;
        $this->qbdAccount = $qbdAccount;
        $this->qbdTax = $qbdTax;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdItem->getItemByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdItem->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdItem->create($this->qbdEntity, $this->entity, $this->extra);
    }

    public function checkPreConditions()
    {
        $qbdItem = $this->getQBDEntity();

        $taxCode = $this->qbdTax->getTaxCodeByQbdId($qbdItem['SalesTaxCodeRef']['ListID']);

        if ($taxCode) {
            $this->extra['qbd_sales_tax_code_id'] = $taxCode->id;
        }

        if (!$taxCode) {

            $taskMeta = [
                'action' => QuickBookDesktopTask::CREATE,
                'object' => QuickBookDesktopTask::SALES_TAX_CODE,
                'object_id' => $qbdItem['SalesTaxCodeRef']['ListID'],
                'priority' => QuickBookDesktopTask::PRIORITY_ADD_SALESTAXCODE,
                'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
            ];

            $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_SALESTAXCODE, $this->task->qb_username, $taskMeta);

            $this->task->setParentTask($parentTask);

            $this->resubmitted = true;

            return false;
        }

        if ($qbdItem['SalesOrPurchase']['AccountRef']['ListID']) {

            $account = $this->qbdAccount->getAccountByQbdId($qbdItem['SalesOrPurchase']['AccountRef']['ListID']);

            if ($account) {
                $this->extra['sales_or_purchase_financial_account_id'] = $account->id;
            }

            if (!$account) {

                $taskMeta = [
                    'action' => QuickBookDesktopTask::CREATE,
                    'object' => QuickBookDesktopTask::ACCOUNT,
                    'object_id' => $qbdItem['SalesOrPurchase']['AccountRef']['ListID'],
                    'priority' => QuickBookDesktopTask::PRIORITY_ADD_ACCOUNT,
                    'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                    'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
                ];

                $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_ACCOUNT, $this->task->qb_username, $taskMeta);

                $this->task->setParentTask($parentTask);

                $this->resubmitted = true;

                return false;
            }
        }

        if ($qbdItem['SalesAndPurchase']['IncomeAccountRef']['ListID']) {

            $account = $this->qbdAccount->getAccountByQbdId($qbdItem['SalesAndPurchase']['IncomeAccountRef']['ListID']);

            if ($account) {
                $this->extra['sales_financial_account_id'] = $account->id;
            }

            if (!$account) {

                $taskMeta = [
                    'action' => QuickBookDesktopTask::CREATE,
                    'object' => QuickBookDesktopTask::ACCOUNT,
                    'object_id' => $qbdItem['SalesAndPurchase']['IncomeAccountRef']['ListID'],
                    'priority' => QuickBookDesktopTask::PRIORITY_ADD_ACCOUNT,
                    'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                    'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
                ];

                $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_ACCOUNT, $this->task->qb_username, $taskMeta);

                $this->task->setParentTask($parentTask);

                $this->resubmitted = true;

                return false;
            }
        }

        if ($qbdItem['SalesAndPurchase']['ExpenseAccountRef']['ListID']) {

            $account = $this->qbdAccount->getAccountByQbdId($qbdItem['SalesAndPurchase']['ExpenseAccountRef']['ListID']);

            if ($account) {
                $this->extra['purchase_financial_account_id'] = $account->id;
            }

            if (!$account) {

                $taskMeta = [
                    'action' => QuickBookDesktopTask::CREATE,
                    'object' => QuickBookDesktopTask::ACCOUNT,
                    'object_id' => $qbdItem['SalesAndPurchase']['ExpenseAccountRef']['ListID'],
                    'priority' => QuickBookDesktopTask::PRIORITY_ADD_ACCOUNT,
                    'origin' => QuickBookDesktopTask::ORIGIN_QBD,
                    'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
                ];

                $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_ACCOUNT, $this->task->qb_username, $taskMeta);

                $this->task->setParentTask($parentTask);

                $this->resubmitted = true;

                return false;
            }
        }

        return true;
    }
}