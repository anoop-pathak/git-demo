<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Vendor;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Vendor as QBDVendor;

class UpdateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDVendor $qbdVendor)
    {
        $this->qbdVendor = $qbdVendor;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdVendor->getVendorByQbdId($qbdId);
        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $parseEntity = $this->qbdVendor->parse($xml);
        $this->qbdEntity = $parseEntity[0];
    }

    function synch($task, $meta)
    {
        return $this->qbdVendor->update($this->qbdEntity, $this->entity);
    }
}