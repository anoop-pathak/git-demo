<?php

namespace App\Models;

class CustomerMeta extends BaseModel
{

    protected $fillable = ['customer_id', 'meta_value', 'meta_key', 'created_by'];

    protected $table = 'customer_meta';

    const META_KEY_DEFAULT_PHOTO_DIR = 'default_photo_dir';
    const SELECTED_JOB = 'selected_job';
    const META_KEY_RESOURCE_ID = 'resource_id';

    public $timestamps = false;

    protected $rule = [
        'job_ids' => 'array'
    ];

    protected function getRules()
    {
        return $this->rule;
    }

    public function setMetaValueAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['meta_value'] = json_encode($value);
        } else {
            $this->attributes['meta_value'] = $value;
        }
    }

    public function getMetaValueAttribute($value)
    {
        if (is_array(json_decode($value, true))) {
            return json_decode($value, true);
        } else {
            return $value;
        }
    }
}
