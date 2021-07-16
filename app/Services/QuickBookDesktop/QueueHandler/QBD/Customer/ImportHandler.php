<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Customer;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Customer as CDCCustomer;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        CDCCustomer $entity,
        QBDCustomer $qbdCustomer,
        QBDJob $qbdJob,
        TaskRegistrar $taskRegistrar
    ) {
        $this->entity = $entity;
        $this->qbdCustomer = $qbdCustomer;
        $this->qbdJob = $qbdJob;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->entity->parse($meta['xml']);

        $this->entity->updateDump($meta['xml']);

        foreach ($enities as $entity) {

            $extraParam = [
                'object_id' => $entity['qb_desktop_id']
            ];

            if ($entity['sub_level'] == 0) {

                $entity['object'] = QuickBookDesktopTask::CUSTOMER;

                $customer = $this->qbdCustomer->getCustomerByQbdId($entity['qb_desktop_id']);

                if ($customer) {

                    if ($customer->qb_desktop_sequence_number == $entity['qb_desktop_sequence_number']) {
                        // $this->addDumpTask(QUICKBOOKS_IMPORT_CUSTOMER, $meta['user'],QuickBookDesktopTask::CUSTOMER, $entity['qb_desktop_id'], QuickBookDesktopTask::PRIORITY_CUSTOMER_DUMP_UPDATE);
                        // Log::warning('Customer already updated', [$customer->id]);
                        continue;
                    }

                    $entity['id'] = $customer->id;
                }
            }

            if ($entity['sub_level'] == 1 || $entity['sub_level'] == 2) {

                $entity['object'] = QuickBookDesktopTask::JOB;

                $job = $this->qbdJob->getJobByQbdId($entity['qb_desktop_id']);

                if ($job) {

                    $entity['id'] = $job->id;

                    if ($job->qb_desktop_sequence_number == $entity['qb_desktop_sequence_number']) {
                        // Log::warning('Job already updated', [$job->id]);
                        // $this->addDumpTask(QUICKBOOKS_IMPORT_JOB, $meta['user'],QuickBookDesktopTask::JOB, $entity['qb_desktop_id']);
                        continue;
                    }
                }
            }

            if (ine($entity, 'id')) {

                $extraParam['action'] = QuickBookDesktopTask::UPDATE;

                if ($entity['object'] == QuickBookDesktopTask::JOB) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_JOB;
                } else {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_MOD_CUSTOMER;
                }

            } else {

                $extraParam['action'] = QuickBookDesktopTask::CREATE;

                if ($entity['object'] == QuickBookDesktopTask::JOB) {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_JOB;
                } else {
                    $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_ADD_CUSTOMER;
                }
            }

            $extraParam = array_merge($entity, $extraParam);

            $action = QUICKBOOKS_IMPORT_CUSTOMER;

           	if($entity['object'] == QuickBookDesktopTask::JOB) {
                $action = QUICKBOOKS_IMPORT_JOB;
            }

            $this->taskRegistrar->addTask($action, $meta['user'], $extraParam);
        }
    }

    private function addDumpTask($action, $user, $object, $objectId){
        $metaData =  [
            'action' => QuickBookDesktopTask::DUMP_UPDATE,
            'object' => $object,
            'object_id' => $objectId,
            'priority' => QuickBookDesktopTask::PRIORITY_CUSTOMER_DUMP_UPDATE,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
        ];

        TaskScheduler::addTask($action, $user, $metaData);
    }
}