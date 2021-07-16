<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Job;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Job as JobEntity;

class DumpUpdateHandler extends BaseTaskHandler
{
    public function __construct(JobEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $this->entity->updateDump($task, $meta);
        return true;
    }
}