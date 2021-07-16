<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Customer;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;
use App\Models\QuickbookSyncCustomer;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Models\QuickBookDesktopTask;

class MapHandler extends BaseTaskHandler
{
    public $task = null;

    public $entity = null;

    private $qbdEntity = null;

    public function __construct(QBDCustomer $qbdCustomer)
    {
        $this->qbdCustomer = $qbdCustomer;
    }

    public function getEntity($qbdId)
    {
        $this->entity = $this->qbdCustomer->getCustomerByQbdId($qbdId);

        return $this->entity;
    }

    function getQBDEntity()
    {
        return $this->qbdEntity;
    }

    function setQBDEntity($xml)
    {
        $this->qbdEntity = $this->qbdCustomer->parse($xml);
    }

    function synch($task, $meta)
    {
        $extra = $task->extra;
        $updateMeta = [];

        $syncCustomer = QuickbookSyncCustomer::where('batch_id', $extra['batch_id'])
            ->where('qb_id', $task->object_id)
            ->whereNotNull('customer_id')
            ->first();

        $customer = $this->qbdCustomer->mapCustomerInQuickBooks($syncCustomer->customer_id, $this->qbdEntity);

        $updateMeta['batch_id'] = ine($extra, 'batch_id') ? $extra['batch_id'] : null;
        $updateMeta['group_id'] = ine($extra, 'group_id') ? $extra['group_id'] : null;
        $updateMeta['created_source'] = ine($extra, 'created_source') ? $extra['created_source'] : null;
        TaskScheduler::addJpCustomerTask(QuickBookDesktopTask::UPDATE, $customer->id, null, $task->qb_username, $updateMeta);

        return $customer;
    }
}