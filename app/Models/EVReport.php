<?php

namespace App\Models;

class EVReport extends BaseModel
{

    protected $table = 'ev_reports';
    protected $fillable = ['report_id', 'file_type_id', 'file_name', 'file_path', 'file_mime_type', 'file_size'];

    //file types.
    const PDF  = 'application/pdf';
    const JSON = 'text/x-json';
    const QUICK_SQUARE_REPORT_ID = 105;

    protected $fileDeliveryRule = [
        'ReportId' => 'required',
        'FileFormatId' => 'required',
        'FileTypeId' => 'required',
    ];

    protected function getFileDeliveryRules()
    {
        return $this->fileDeliveryRule;
    }

    public function order()
    {
        return $this->belongsTo(EVOrder::class, 'report_id', 'report_id');
    }

    public function fileType()
    {
        return $this->belongsTo(EVFileType::class, 'file_type_id');
    }
}
