<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\SalesTaxCode;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\SalesTaxCode;
use App\Services\QuickBookDesktop\Entity\Tax as QBDTax;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        SalesTaxCode $entity,
        QBDTax $qbdTax
    ) {

        $this->entity = $entity;
        $this->qbdTax = $qbdTax;
    }

    function synch($task, $meta)
    {
        $enities = $this->entity->parse($meta['xml']);
        return $this->qbdTax->storeTaxCodes($enities, $task->qb_username);
    }
}