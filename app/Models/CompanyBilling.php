<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBilling extends Model
{

    protected $table = 'company_billing';

    protected $fillable = [
        'company_id',
        'address',
        'address_line_1',
        'city',
        'state_id',
        'zip',
        'country_id',
        'email'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getCardDetailsAttribute($value)
    {
        return json_decode($value);
    }

    public function setCardDetailsAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['card_details'] = json_encode($value);
    }
}
