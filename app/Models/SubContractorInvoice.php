<?php

namespace App\Models;

use FlySystem;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubContractorInvoice extends BaseModel
{

    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'job_id',
        'job_schedule_id',
        'file_name',
        'file_path',
        'thumb',
        'mime_type',
        'size'
    ];

    protected static $createInvoiceRules = [
        'job_id' => 'required',
        'job_schedule_id' => 'required',
    ];

    protected static $uploadFileRules = [
        'job_id' => 'required',
    ];


    public static function createInvoiceRules()
    {
        $createInvoiceRules = self::$createInvoiceRules;
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');

        $createInvoiceRules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;

        return $createInvoiceRules;
    }

    public static function uploadFileRules()
    {
        $uploadFileRules = self::$uploadFileRules;
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');

        $uploadFileRules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;

        return $uploadFileRules;
    }

    public function getFilePath()
    {
        if (!$this->file_path) {
            return null;
        }

        return FlySystem::publicUrl(config('jp.BASE_PATH') . $this->file_path);
    }

    public function getThumb()
    {
        if (!$this->thumb) {
            return null;
        }

        return FlySystem::publicUrl(config('jp.BASE_PATH') . $this->thumb);
    }
}
