<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Models\TransactionUpdatedTime;
use App\Models\QuickBookDesktopTask;

abstract class BaseEntity
{
    protected $task = null;

    protected function linkEntity($entity, $qboEntity, $attachOrigin = false)
    {
        if(ine($qboEntity, 'ListID')) {
            $entity->qb_desktop_id = $qboEntity['ListID'];
        }

        // for transactions
        if (ine($qboEntity, 'TxnID')) {
            $entity->qb_desktop_txn_id = $qboEntity['TxnID'];
        }

        if($attachOrigin) {
            $entity->origin = $this->getOrigin();
        }

        $entity->qb_desktop_sequence_number = $qboEntity['EditSequence'];
        $entity->save();
    }

    /**
     * Save or Update transaction last updated time
     */
    protected function saveTransactionUpdatedTime($input)
    {
        $transactionUpdates = TransactionUpdatedTime::firstOrNew([
            'company_id' => $input['company_id'],
            'type' => $input['type'],
            'qb_desktop_txn_id' => $input['qb_desktop_txn_id'],
            'jp_object_id' => $input['jp_object_id']
        ]);
        $transactionUpdates->object_last_updated = $input['object_last_updated'];
        $transactionUpdates->qb_desktop_sequence_number = $input['qb_desktop_sequence_number'];
        $transactionUpdates->save();
    }

    public function setTask(QuickBookDesktopTask $task)
    {
        $this->task = $task;
    }

    public function getOrigin()
    {
        return QuickBookDesktopTask::ORIGIN_QBD;
    }
}