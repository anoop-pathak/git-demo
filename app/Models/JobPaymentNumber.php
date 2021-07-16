<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class JobPaymentNumber extends BaseModel
{

    use SoftDeletes;

    protected $fillable = ['start_from', 'current_number', 'company_id'];
}
