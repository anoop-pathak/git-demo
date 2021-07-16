<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Item;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Item as CDCItem;
use App\Services\QuickBookDesktop\Entity\Item as QBDItem;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        CDCItem $entity,
        QBDItem $qbdItem,
        TaskRegistrar $taskRegistrar
    ) {
        $this->entity = $entity;
        $this->qbdItem = $qbdItem;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->entity->parse($meta['xml']);

        foreach ($enities as $entity) {

            $extraParam = [
                'object_id' => $entity['qb_desktop_id']
            ];

            $entity['object'] = QuickBookDesktopTask::ITEM;

            $item = $this->qbdItem->getItemByQbdId($entity['qb_desktop_id']);

            if ($item) {
                if ($item->qb_desktop_sequence_number == $entity['qb_desktop_sequence_number']) {
                    continue;
                }
                $entity['id'] = $item->id;
            }

            if (ine($entity, 'id')) {
                $extraParam['action'] = QuickBookDesktopTask::UPDATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_ITEM;
            } else {
                $extraParam['action'] = QuickBookDesktopTask::CREATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_ITEM;
            }

            $extraParam = array_merge($entity, $extraParam);

            $this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_ITEM, $meta['user'], $extraParam);
        }
    }
}