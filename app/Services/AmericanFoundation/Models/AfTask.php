<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Services\AmericanFoundation\Models\AfUser;

class AfTask extends BaseModel
{

    protected $table = "af_tasks";

    protected $fillable = [
        'af_id', 'company_id', 'af_owner_id', 'who_id', 'what_id', 'task_id',
        'subject', 'status', 'priority', 'description', 'options', 'csv_filename',
        'created_by', 'updated_by', 'group_id'
    ];

    public function user()
    {
        return $this->belongsTo(AfUser::class, 'af_owner_id', 'af_id');
    }
}