<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Customer;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;

class DumpUpdateHandler extends BaseTaskHandler
{
    public function __construct(CustomerEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $this->entity->updateDump($task, $meta);
        return true;
    }
}