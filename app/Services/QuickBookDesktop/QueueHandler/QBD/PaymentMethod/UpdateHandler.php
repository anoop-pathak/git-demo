<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\PaymentMethod;

use App\Services\QuickBookDesktop\Entity\PaymentMethod as QBDPaymentMethod;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;

class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDPaymentMethod $qbdPaymentMethod)
    {
        $this->qbdPaymentMethod = $qbdPaymentMethod;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdPaymentMethod->getPaymentMethodByQbdId($qbdId);
        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdPaymentMethod->parse($xml);
    }

    function synch($task, $meta)
    {
        $entity = $this->qbdPaymentMethod->update($this->qbdEntity, $this->entity);
        return $entity;
    }
}
