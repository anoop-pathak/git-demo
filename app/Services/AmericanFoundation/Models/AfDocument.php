<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Models\Company;

class AfDocument extends BaseModel
{

    protected $table = "af_documents";

    protected $fillable = [
        'company_id', 'group_id', 'document_id', 'af_id',
        'folder_id', 'name', 'content_type', 'type', 'is_public',
        'body_length', 'body_length_compressed', 'description', 'keywords',
        'is_internal_use_only', 'author_id', 'options', 'created_by',
        'updated_by', 'csv_filename'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}