<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContact extends Model
{


    protected $table = 'customer_contacts';

    protected $appends = ['full_name', 'full_name_mobile'];

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'additional_emails',
        'phones',
        'address',
        'address_line_1',
        'city',
        'state_id',
        'country_id',
        'zip'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function setAdditionalEmailsAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['additional_emails'] = json_encode($value);
    }

    public function setPhonesAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['phones'] = json_encode($value);
    }

    public function getAdditionalEmailsAttribute($value)
    {
        return json_decode($value);
    }

    public function getPhonesAttribute($value)
    {
        return json_decode($value);
    }

    //get full name
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    //get full name for mobile
    public function getFullNameMobileAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
