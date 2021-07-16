<?php

namespace App\Models;

class EmailTemplateRecipient extends BaseModel
{

    protected $table = 'email_template_recipient';
    public $timestamps = false;
    protected $fillable = ['email_template_id', 'email', 'type'];
}
