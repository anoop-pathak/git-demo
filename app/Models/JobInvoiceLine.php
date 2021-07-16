<?php

namespace App\Models;

class JobInvoiceLine extends BaseModel
{

    protected $fillable = [
        'invoice_id',
        'description',
        'amount',
        'quantity',
        'product_id',
        'tier1',
        'tier2',
        'tier3',
        'work_type_id',
        'trade_id',
        'is_chargeable',
        'supplier_id',
        'branch_code',
        'quickbook_id',
        'id',
        'is_taxable',
        'qb_txn_line_id',
        'qb_item_id'
    ];

    public $timestamps = false;

    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = $value ?: null;
    }

    public function getTotalAmount()
    {
        return $this->amount * $this->quantity;
    }

    public function workType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
