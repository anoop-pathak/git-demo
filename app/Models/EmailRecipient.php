<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailRecipient extends Model
{

    protected $table = 'email_recipient';
    public $timestamps = false;
    protected $fillable = ['email_id', 'email', 'type', 'delivery_date_time', 'bounce_date_time'];
}
