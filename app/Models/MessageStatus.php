<?php

namespace App\Models;

class MessageStatus extends BaseModel
{

    protected $table = 'message_status';

    public $timestamps = false;

    protected $fillable = ['thread_id', 'message_id', 'user_id'];
}
