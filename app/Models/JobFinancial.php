<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobFinancial extends Model
{
    protected $table = 'job_financial';
    protected $fillable = ['job_id', 'selling_price', 'tax_rate'];
}
