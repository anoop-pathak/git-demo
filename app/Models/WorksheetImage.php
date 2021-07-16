<?php

namespace App\Models;

class WorksheetImage extends BaseModel
{

    protected $fillable = ['worksheet_id', 'name', 'path', 'size', 'thumb'];

    public function workOrderAttachments()
    {
        return $this->belongsTo(MaterialList::class);
    }
}
