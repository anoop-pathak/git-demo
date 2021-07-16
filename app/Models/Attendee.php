<?php

namespace App\Models;

class Attendee extends BaseModel
{

    protected $fillable = ['appointment_id', 'user_id'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
