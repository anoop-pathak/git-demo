<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\ItemSalesTax;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\ItemSalesTax;
use App\Services\QuickBookDesktop\Entity\Tax;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(ItemSalesTax $cdcEntity, Tax $taxes)
    {
        $this->cdcEntity = $cdcEntity;
        $this->taxes = $taxes;
    }

    function synch($task, $meta)
    {
        $taxes = $this->cdcEntity->parse($meta['xml']);

        $this->taxes->saveItemSalesTax($taxes, $task);
    }
}