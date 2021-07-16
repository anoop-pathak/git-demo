<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeOrderEntity extends Model
{
    protected $fillable = [
        'change_order_id','description','amount', 'quantity', 'product_id', 'tier1', 'tier2', 'tier3', 'trade_id', 'work_type_id',
        'is_chargeable', 'supplier_id', 'branch_code'
    ];

    public $timestamps = false;

    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = $value ?: null;
    }

    public function changeOrder()
    {
        return $this->belongsTo(ChangeOrder::class);
    }

    public function workType()
    {
        return $this->belongsTo(JobType::class, 'work_type_id')->select('id', 'name', 'color');
    }
    public function trade()
    {
        return $this->belongsTo(Trade::class, 'trade_id')->select('id', 'name');
    }
}
