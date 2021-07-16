<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\ReceivePayment;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as ReceivePaymentEntity;

class DumpUpdateHandler extends BaseTaskHandler
{
    public function __construct(ReceivePaymentEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $this->entity->updateDump($task, $meta);
        return true;
    }
}