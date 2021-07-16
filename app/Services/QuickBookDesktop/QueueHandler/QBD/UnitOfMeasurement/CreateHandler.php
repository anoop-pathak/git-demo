<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\UnitOfMeasurement;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\UnitofMeasurement as QBDUnitofMeasurement;

class CreateHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDUnitofMeasurement $qbdUnitofMeasurement)
    {
        $this->qbdUnitofMeasurement = $qbdUnitofMeasurement;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdUnitofMeasurement->getJpEntityByQbdId($qbdId);
        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdUnitofMeasurement->parse($xml);
    }

    function synch($task, $meta)
    {
        return $this->qbdUnitofMeasurement->create($this->qbdEntity);
    }
}