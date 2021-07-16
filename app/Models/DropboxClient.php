<?php

namespace App\Models;

class dropboxClient extends BaseModel
{

    protected $table = 'dropbox_clients';

    protected $fillable = ['company_id', 'user_id', 'token', 'uid', 'account_id'];
}
