<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobViewHistory extends Model
{

    protected $table = 'job_view_history';

    protected $fillable = ['job_id', 'user_id'];
}
