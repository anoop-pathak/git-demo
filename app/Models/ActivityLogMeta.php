<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLogMeta extends Model
{
    protected $fillable = ['activity_id', 'key', 'value'];

    protected $table = 'activity_log_meta';
    public $timestamps = false;
}
