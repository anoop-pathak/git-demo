<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Bill;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Bill as BillEntity;

class DumpUpdateHandler extends BaseTaskHandler
{
    public function __construct(BillEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $this->entity->updateDump($task, $meta);
        return true;
    }
}