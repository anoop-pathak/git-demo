<?php

namespace App\Models;

class SMReportFile extends BaseModel
{

    //file types.
    const PDF = 'application/pdf';

    protected $table = 'sm_report_files';

    protected $fillable = ['order_id', 'file_id', 'name', 'path', 'mime_type', 'size'];
}
