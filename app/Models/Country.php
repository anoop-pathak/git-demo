<?php

namespace App\Models;

class Country extends BaseModel
{

    protected $fillable = ['name', 'code', 'currency_name', 'currency_symbol'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $appends = ['phone_code'];

    protected $rules = [
        'name' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getCountryId($code = null)
    {
        $country = self::where('code', $code)->first();
        if(!$code || !$country) {
            $country = self::where('name', $code)->first();
        }

        if(!$code || !$country) {
            $country = self::where('code', 'US')->first();
        }

        if($country) {
            return $country->id;
        }

        return null;
    }

    protected function getPhoneCodeAttribute()
    {
        return config("mobile-message.country_code.{$this->code}");
    }

    public function state()
    {
        return $this->hasMany(State::class);
    }

    public function timezone()
    {
        return $this->hasMany(Timezone::class);
    }
}
