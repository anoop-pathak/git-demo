<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Account;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Account as CDCAccount;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;
use QBDesktopQueue;

class SyncAllHandler extends BaseTaskHandler
{
    public function __construct(CDCAccount $cdcEntity, QBDAccount $qbdAccount, TaskRegistrar $taskRegistrar)
    {
        $this->cdcEntity = $cdcEntity;
        $this->qbdAccount = $qbdAccount;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $entities = $this->qbdAccount->syncParse($meta['xml']);
        foreach ($entities as $entity) {
            $this->qbdAccount->createOrUpdate($entity);
        }

        if(empty($meta['idents']['iteratorRemainingCount'])) {
            QBDesktopQueue::createAccounts();
        }
        return true;
    }
}