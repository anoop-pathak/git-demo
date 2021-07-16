<?php

namespace App\Models;

class ProjectStatusManager extends BaseModel
{

    protected $table = 'project_status_manager';

    protected $fillable = ['name', 'company_id'];
}
