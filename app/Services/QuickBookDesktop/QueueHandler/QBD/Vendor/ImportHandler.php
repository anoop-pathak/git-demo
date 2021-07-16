<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Vendor;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Vendor as CDCVendor;
use App\Services\QuickBookDesktop\Entity\Vendor as QBDVendor;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(CDCVendor $cdcEntity, QBDVendor $qbdVendor, TaskRegistrar $taskRegistrar)
    {
        $this->cdcEntity = $cdcEntity;
        $this->qbdVendor = $qbdVendor;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->cdcEntity->parse($meta['xml']);
        foreach ($enities as $entity) {

            $extraParam = [
                'object_id' => $entity['qb_desktop_id'],
                'object' => QuickBookDesktopTask::VENDOR
            ];

            $vendor = $this->qbdVendor->getVendorByQbdId($extraParam['object_id']);

            if ($vendor) {

                if ($vendor->qb_desktop_sequence_number == $entity['qb_desktop_sequence_number']) {
                    // Log::warning('Vendor Already Updated', [$entity['qb_desktop_id']]);
                    continue;
                }

                $entity['id'] = $vendor->id;
            }

            if (ine($entity, 'id')) {

                $extraParam['action'] = QuickBookDesktopTask::UPDATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_VENDOR;
            } else {

                $extraParam['action'] = QuickBookDesktopTask::CREATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_VENDOR;
            }

            $extraParam = array_merge($entity, $extraParam);

            $this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_VENDOR, $meta['user'], $extraParam);
        }
    }
}