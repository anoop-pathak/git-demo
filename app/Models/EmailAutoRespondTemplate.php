<?php

namespace App\Models;

class EmailAutoRespondTemplate extends BaseModel
{

    protected $fillable = ['user_id', 'subject', 'content', 'active'];
}
