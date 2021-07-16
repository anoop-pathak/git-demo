<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Account;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Account as CDCAccount;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        CDCAccount $cdcEntity,
        QBDAccount $qbdAccount,
        TaskRegistrar $taskRegistrar
    ) {
        $this->cdcEntity = $cdcEntity;
        $this->qbdAccount = $qbdAccount;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->cdcEntity->parse($meta['xml']);

        foreach ($enities as $entity) {

            $extraParam['object_id'] = $entity['qb_desktop_id'];
            $entity['object'] = QuickBookDesktopTask::ACCOUNT;

            $account = $this->qbdAccount->getAccountByQbdId($extraParam['object_id']);

            if ($account) {

                if ($account->qb_desktop_sequence_number
                    == $entity['qb_desktop_sequence_number']) {
                    //Log::warning('QBD:Account already udpdated:', [$entity['qb_desktop_id']]);
                    continue;
                }

                $entity['id'] = $account->id;
            }

            if (ine($entity, 'id')) {

                $extraParam['action'] = QuickBookDesktopTask::UPDATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_ACCOUNT;
            } else {

                $extraParam['action'] = QuickBookDesktopTask::CREATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_ACCOUNT;
            }

            $extraParam = array_merge($entity, $extraParam);

            $this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_ACCOUNT, $meta['user'], $extraParam);
        }
    }
}