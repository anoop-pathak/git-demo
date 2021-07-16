<?php

namespace App\Models;

class EVStatus extends BaseModel
{

    protected $table = 'ev_order_statuses';
    protected $fillable = ['id', 'name'];

    //status..
    const COMPLETED = '5';
}
