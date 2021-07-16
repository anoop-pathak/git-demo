<?php

namespace App\Models;

class DatabasePool extends BaseModel
{

    protected $table = 'database_pool';

    public function scopeGetFreeDatabase($query)
    {
        return $query->where('company_id', null)->first();
    }
}
