<?php

namespace App\Models;

use FlySystem;

class WorksheetAttachment extends BaseModel
{

    protected $fillable = ['company_id', 'name', 'mime_type', 'path', 'size',];

    protected $hidden = ['created_at', 'updated_at', 'company_id',];

    protected $rules = [
        'financial_id' => 'required|integer',
        'file' => 'required|',
    ];

    protected function getFileRules()
    {
        $validFiles = implode(',', array_merge(config('resources.image_types'), \config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');

        $rules = [
            'file' => 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize,
        ];

        return $rules;
    }

    public function getPathAttribute($value)
    {
        return FlySystem::publicUrl(config('jp.BASE_PATH') . $value);
    }
}
