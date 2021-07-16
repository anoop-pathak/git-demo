<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class IncompleteSignup extends Model
{

    protected $fillable = ['token', 'first_name', 'last_name', 'email', 'phone'];

    protected $hidden = ['token'];

    protected $rules = [
        'token' => 'required',
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'required',
        'phone' => 'required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
