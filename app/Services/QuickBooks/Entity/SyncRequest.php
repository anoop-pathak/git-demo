<?php
namespace App\Services\QuickBooks\Entity;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QBOQueue;

class SyncRequest
{
    public function submitted($batch, $meta = [])
    {
        QBOQueue::addTask(QuickBookTask::SYNC_REQUEST.' '.QuickBookTask::QUICKBOOKS_ANALYZING_REQUEST, [
            'id' => $batch->id,
            'input' => $meta
        ], [
            'object_id' => $batch->id,
            'object' => QuickBookTask::SYNC_REQUEST,
            'action' => QuickBookTask::SYNC_REQUEST_ANALYZING,
            'origin' => 0,
            'created_source' => QuickBookTask::SYNC_MANAGER
        ]);
    }
}