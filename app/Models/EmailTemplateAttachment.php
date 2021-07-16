<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateAttachment extends Model
{

    public $timestamps = false;

    protected $table = 'template_attachments';

    protected $fillable = ['template_id', 'type', 'value'];

    public function scopeAttachment($query, $value, $templateId)
    {
        return $query->whereValue($value)->whereTemplateId($templateId);
    }

    public function resource()
    {
        return $this->hasOne(resource::class, 'id', 'value');
    }
}
