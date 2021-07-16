<?php
namespace App\Models;

class TransactionUpdatedTime extends BaseModel
{
    protected $table = 'qbd_transaction_updated_time';

    protected $fillable = [
        'company_id', 'qb_username', 'type',
        'object_last_updated', 'jp_object_id',
        'qb_desktop_txn_id', 'qb_desktop_sequence_number',
        'txn_date'
    ];
}

