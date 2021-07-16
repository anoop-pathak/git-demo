<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceMeta extends Model
{

    protected $table = 'resource_meta';

    protected $fillable = ['resource_id', 'key', 'value'];

    protected $hidden = ['created_at', 'updated_at'];
}
