<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Account;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;

class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDAccount $qbdAccount)
    {
        $this->qbdAccount = $qbdAccount;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdAccount->getAccountByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdAccount->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdAccount->update($this->qbdEntity, $this->entity);
    }

    public function checkPreConditions()
    {
        $qbdEntity = $this->getQBDEntity();

        $accountType = $this->qbdAccount->getAccountType($qbdEntity['AccountType']);

        if (!$accountType) {

            Log::info('QBD: Account type not found: ' . $qbdEntity['AccountType']);
            return false;
        }

        if(!$qbdEntity['ParentRef']['ListID']) {
            return true;
        }

        $parentAccount = $this->qbdAccount->getAccountByQbdId($qbdEntity['ParentRef']['ListID']);

        if ($parentAccount) {
            return true;
        }

        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::ACCOUNT,
            'object_id' => $qbdEntity['ParentRef']['ListID'],
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_ACCOUNT + 1,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
        ];

        $parentTask = TaskScheduler::addTask(QUICKBOOKS_IMPORT_ACCOUNT, $this->task->qb_username, $taskMeta);

        $this->task->setParentTask($parentTask);

        $this->resubmitted = true;

        return false;
    }
}