<?php

namespace App\Models;

class SubscriptionPlan extends BaseModel
{
    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = ['title', 'code', 'min', 'max', 'amount', 'product_id', 'setup_fee', 'cycles'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
