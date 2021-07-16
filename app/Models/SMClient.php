<?php

namespace App\Models;

class SMClient extends BaseModel
{

    protected $table = 'sm_clients';

    protected $fillable = ['username', 'token'];
}
