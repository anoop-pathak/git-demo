<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;

class QBDItem extends BaseModel implements SynchEntityInterface
{
    use SoftDeletes;
    use QbdSynchableTrait;
    use QboSynchableTrait;

	protected $table = 'qbd_items';

    protected $fillable = [
        'company_id', 'name', 'qb_desktop_id', 'qb_desktop_sequence_number'
    ];
}