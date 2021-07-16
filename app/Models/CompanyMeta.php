<?php

namespace App\Models;

class CompanyMeta extends BaseModel
{

    protected $table = 'company_meta';

    protected $fillable = ['company_id', 'value', 'key'];

    // keys.
    const SUBSCRIBER_RESOURCE_ID = 'subscriber_resource_id';
    const INSTANT_PHOTO_RESOURCE_ID = 'instant_photo_resource_id';
}
