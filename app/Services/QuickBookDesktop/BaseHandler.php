<?php
namespace App\Services\QuickBookDesktop;

use App\Services\QuickBookDesktop\Setting\Settings;
use Illuminate\Support\Facades\App;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Services\QuickBookDesktop\TaskScheduler;

abstract class BaseHandler
{
    use TaskableTrait;

    public function __construct()
    {
        $this->settings = App::make(Settings::class);
        $this->taskScheduler = App::make(TaskScheduler::class);
    }

    protected function linkEntity($entity, $qboEntity, $attachOrigin = false, $attachSyncStatus = false)
    {
        if (ine($qboEntity, 'ListID')) {
            $entity->qb_desktop_id = $qboEntity['ListID'];
        }

        // for transactions
        if (ine($qboEntity, 'TxnID')) {
            $entity->qb_desktop_txn_id = $qboEntity['TxnID'];
        }

        if ($attachOrigin) {
            $entity->origin = QuickBookDesktopTask::ORIGIN_JP;
        }

        if ($attachSyncStatus) {
            $entity->quickbook_sync_status = 1;
        }

        $entity->qb_desktop_sequence_number = $qboEntity['EditSequence'];
        $entity->save();
    }

    public function getOrigin()
    {
        return QuickBookDesktopTask::ORIGIN_JP;
    }
}