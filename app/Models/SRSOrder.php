<?php

namespace App\Models;

class SRSOrder extends BaseModel
{
    protected $table = 'srs_orders';

    protected $fillable = ['material_list_id', 'order_id', 'order_status', 'order_details'];

    const ORDER_PLACED = 'order_placed';

    public function materialList()
    {
        return $this->belongsTo(MaterialList::class);
    }

    public function setOrderDetailsAttribute($value)
    {
        $this->attributes['order_details'] = json_encode($value);
    }

    public function getOrderDetailsAttribute($value)
    {
        return json_decode($value, true);
    }
}
