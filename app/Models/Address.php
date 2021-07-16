<?php

namespace App\Models;

use Laracasts\Presenter\PresentableTrait;

class Address extends BaseModel
{

    use PresentableTrait;

    protected $presenter = \App\Presenters\AddressPresenter::class;

    protected $fillable = ['company_id', 'address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip', 'geocoding_error', 'lat', 'long'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function customerBilling()
    {
        return $this->hasOne(Customer::class, 'billing_address_id');
    }

    public function setZipAttribute($value)
    {
        if(!empty($value) && isset($this->attributes['country_id'])) {
            $this->attributes['zip'] = zipCodeFormat($value, $this->attributes['country_id']);
        } else {
            $this->attributes['zip'] = null;
        }
    }

    public function setLatAttribute($value)
    {
        $this->attributes['lat'] = ($value) ?: null;
    }
     public function setLongAttribute($value)
    {
        $this->attributes['long'] = ($value) ?: null;
    }

    public static function getCitiesAndStates()
    {
        return self::leftJoin('states as state', 'state.id', '=', 'state_id')
            ->groupBy('city')
            ->where('city', '!=', '')
            ->select('city', 'state.name as state');
    }

    public static function isDistanceCalculationPossible()
    {

        return true;
        // $scope = App::make(\App\Services\Contexts\Context::class);
        // $addressWithoutCoordinates = self::where('company_id', $scope->id())
        //     ->where(function($query){
        //         $query->whereNull('lat')
        //         ->orWhereNull('long');
        //     })
        //     ->whereGeocodingError(false)
        //     ->count();
        // if($addressWithoutCoordinates < \config('jp.without_distance_record_limit'))
        //     return true;
        // else
        //     return false;
    }
}
