<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoverJobModel extends Model
{
	protected $table = 'hover_job_models';
	
    protected $fillable = ['job_id', 'company_id', 'image_url', 'url', 'hover_job_id'];
}
