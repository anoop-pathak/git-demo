<?php

namespace App\Models;

use Laracasts\Presenter\PresentableTrait;

class UserProfile extends BaseModel
{

    use PresentableTrait;

    protected $presenter = \App\Presenters\UserExportPresenter::class;

    protected $fillable = [
        'user_id',
        'phone',
        'cell',
        'address',
        'address_line_1',
        'city',
        'state_id',
        'zip',
        'country_id',
        'position',
        'additional_phone',
        'profile_pic',
    ];

    protected $table = 'user_profile';

    protected $createRules = [
        'address' => 'required',
        'city' => 'required',
        'state_id' => 'required',
        'zip' => 'required',
        'country_id' => 'required',
        // 'phone'		 		=>	'required',
        'additional_phone' => 'array',
    ];

    protected $updateRules = [
        'address' => 'required',
        'city' => 'required',
        'state_id' => 'required',
        'zip' => 'required',
        'country_id' => 'required',
        // 'phone'		 		=>	'required',
        'additional_phone' => 'array',
    ];

    protected function getCreateRules()
    {
        return $this->createRules;
    }

    protected function getUpdateRules()
    {
        return $this->updateRules;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getAdditionalPhoneAttribute($value)
    {
        return json_decode($value);
    }

    public function setAdditionalPhoneAttribute($value)
    {
        $this->attributes['additional_phone'] = json_encode(array_filter((array)$value));
    }
}
