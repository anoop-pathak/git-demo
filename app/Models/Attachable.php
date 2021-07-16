<?php
namespace App\Models;

use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachable extends BaseModel implements SynchEntityInterface {

    use QboSynchableTrait;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
	protected $fillable = [
        'company_id', 'job_id', 'customer_id',
        'jp_object_id', 'object_type','quickbook_id',
        'jp_attachment_id'
    ];
}