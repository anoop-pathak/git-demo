<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Vendor;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Vendor as CDCVendor;
use App\Services\QuickBookDesktop\Entity\Vendor as QBDVendor;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;
use QBDesktopQueue;

class SyncAllHandler extends BaseTaskHandler
{
    public function __construct(CDCVendor $cdcEntity, QBDVendor $qbdVendor, TaskRegistrar $taskRegistrar)
    {
        $this->cdcEntity = $cdcEntity;
        $this->qbdVendor = $qbdVendor;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->qbdVendor->parse($meta['xml']);
        foreach ($enities as $entity) {
            $this->qbdVendor->createOrUpdate($entity);
        }

        if(empty($meta['idents']['iteratorRemainingCount'])) {
            QBDesktopQueue::createVendors();
        }
        return true;
    }
}