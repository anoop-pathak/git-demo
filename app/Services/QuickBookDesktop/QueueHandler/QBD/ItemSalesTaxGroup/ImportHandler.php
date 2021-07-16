<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\ItemSalesTaxGroup;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\ItemSalesTaxGroup;
use App\Services\QuickBookDesktop\Entity\Tax;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(ItemSalesTaxGroup $cdcEntity, Tax $taxes)
    {
        $this->cdcEntity = $cdcEntity;
        $this->taxes = $taxes;
    }

    function synch($task, $meta)
    {
        $taxes = $this->cdcEntity->parse($meta['xml']);

        return $this->taxes->saveItemSalesTaxGroups($taxes, $task);
    }
}