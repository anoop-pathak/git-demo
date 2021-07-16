<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Invoice;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Invoice as InvoiceEntity;

class DumpUpdateHandler extends BaseTaskHandler
{
    public function __construct(InvoiceEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $this->entity->updateDump($task, $meta);
        return true;
    }
}