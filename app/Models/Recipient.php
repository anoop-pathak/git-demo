<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{

    protected $table = 'message_recipient';
    public $timestamps = false;

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', '=', $userId);
    }

    public function scopeByMessage($query, $messageId)
    {
        return $query->where('message_id', '=', $messageId);
    }
}
