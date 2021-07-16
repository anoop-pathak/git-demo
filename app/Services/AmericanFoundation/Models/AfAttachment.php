<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Models\Company;
use App\Services\AmericanFoundation\Models\AfCustomer;

class AfAttachment extends BaseModel
{

    protected $table = "af_attachments";

    protected $fillable = [
        'company_id', 'group_id', 'attachment_id', 'af_id', 'af_owner_id',
        'feed_item_id', 'parent_id', 'account_id', 'name', 'content_type', 'extension',
        'body_length', 'body_length_compressed', 'description', 'is_private',
        'options', 'created_by', 'updated_by', 'csv_filename'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function afCustomer()
    {
        return $this->belongsTo(AfCustomer::class, 'parent_id', 'af_id');
    }
}