<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\UnitOfMeasurement;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\UnitofMeasurement as CDCUnitofMeasurement;
use App\Services\QuickBookDesktop\Entity\UnitofMeasurement as QBDUnitofMeasurement;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        CDCUnitofMeasurement $cdcEntity,
        QBDUnitofMeasurement $qbdUnitofMeasurement,
        TaskRegistrar $taskRegistrar
    )
    {
        $this->cdcEntity = $cdcEntity;
        $this->qbdUnitofMeasurement = $qbdUnitofMeasurement;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->cdcEntity->parse($meta['xml']);

        foreach ($enities as $entity) {

            $extraParam['object_id'] = $entity['qb_desktop_id'];
            $entity['object'] = QuickBookDesktopTask::UNIT_OF_MEASUREMENT;

            $unit = $this->qbdUnitofMeasurement->getJpEntityByQbdId($extraParam['object_id']);

            if ($unit) {

                if ($unit->qb_desktop_sequence_number
                    == $entity['qb_desktop_sequence_number']) {

                    // Log::warning('UnitOfMeasurement:Account already udpdated:', [$entity['qb_desktop_id']]);
                    continue;
                }

                $entity['id'] = $unit->id;
            }

            if (ine($entity, 'id')) {
                $extraParam['action'] = QuickBookDesktopTask::UPDATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_UNIT_MEASUREMENT;
            } else {
                $extraParam['action'] = QuickBookDesktopTask::CREATE;
                $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_UNIT_MEASUREMENT;
            }

            $extraParam = array_merge($entity, $extraParam);

            $this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_UNITOFMEASURESET, $meta['user'], $extraParam);
        }
    }
}