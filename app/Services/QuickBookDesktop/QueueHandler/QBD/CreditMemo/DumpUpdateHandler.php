<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\CreditMemo;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\CreditMemo as CreditMemoEntity;

class DumpUpdateHandler extends BaseTaskHandler
{
    public function __construct(CreditMemoEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $this->entity->updateDump($task, $meta);
        return true;
    }
}