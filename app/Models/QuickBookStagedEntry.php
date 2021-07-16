<?php
namespace App\Models;

class QuickBookStagedEntry extends BaseModel
{
    protected $table = 'quickbook_staged_entries';

    protected $fillable = [
        'object_type', 'company_id', 'object_id', 'type', 'meta', 'status',
    ];


    public function setMetaAttribute($value)
    {

        if (is_array($value)) {

            $this->attributes['meta'] = json_encode($value);
        } else {

            $this->attributes['meta'] = $value;
        }
    }

    public function getMetaAttribute($value)
    {

        if (is_array(json_decode($value, true))) {

            return json_decode($value, true);
        } else {

            return $value;
        }
    }
}