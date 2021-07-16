<?php
namespace App\Models;

use Carbon\Carbon;
// use Request;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;

class PaymentMethod extends BaseModel implements SynchEntityInterface {

    use QboSynchableTrait;

    use QbdSynchableTrait;

	protected $fillable = [
        'label', 'method', 'company_id', 'quickbook_id',
        'quickbook_sync_token', 'origin', 'qb_desktop_id',
        'qb_desktop_sequence_number', 'qb_desktop_delete',
        'type', 'created_at', 'updated_at'
    ];
}