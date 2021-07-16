<?php

namespace App\Models;

class ConnectedSite extends BaseModel
{

    protected $fillable = ['company_id', 'user_id', 'domain'];
}
