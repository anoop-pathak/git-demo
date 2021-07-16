<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSignature extends Model
{

    protected $fillable = ['user_id', 'signature'];

    public function user()
    {
        $this->belongsTo(User::class);
    }
}
