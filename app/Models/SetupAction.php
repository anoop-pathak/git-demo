<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetupAction extends Model
{
    protected $fillable = ['action', 'product_id', 'required'];
    protected $hidden = ['created_at', 'updated_at'];

    const COMPANY_SETUP = 'Company Setup';
    const BILLING_DETAILS = 'Billing Details';
    const TRADE_TYPES = 'Trade Types';
    const STATES = 'States';
    const USERS = 'Users';
    const WORKFLOW = 'Workflow';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeProductId($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeActionName($query, $name)
    {
        return $query->where('action', $name);
    }

    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }
}
