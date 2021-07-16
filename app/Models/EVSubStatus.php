<?php

namespace App\Models;

class EVSubStatus extends BaseModel
{

    protected $table = 'ev_order_sub_statuses';
    protected $fillable = ['id', 'name', 'sub_status_id'];
}
