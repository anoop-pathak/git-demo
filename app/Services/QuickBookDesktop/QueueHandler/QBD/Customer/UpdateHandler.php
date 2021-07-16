<?php

namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Customer;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;


class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDCustomer $qbdCustomer)
    {
        $this->qbdCustomer = $qbdCustomer;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdCustomer->getCustomerByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdCustomer->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdCustomer->update($this->qbdEntity, $this->entity);
    }

    public function checkPreConditions()
    {
        if($this->entity) {
            return true;
        }

        return false;
    }
}