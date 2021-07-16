<?php

namespace App\Models;

use Illuminate\Support\Facades\App;

class State extends BaseModel
{

    protected $fillable = ['name', 'country_id', 'code'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $rules = [
        'name' => 'required',
        'country_id' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getStateId($countryId, $code = null)
    {
        $state = self::where('code', $code)->orWhere('name', $code)->where('country_id', $countryId)->first();

        if($state) {
            return $state->id;
        }

        return null;
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function companies()
    {
        // 1st parameter is eloquent class name
        // 2nd parameter is pivot table name
        // 3rd parameter is associated key
        // 4th parameter is associated key
        return $this->belongsToMany(Company::class, 'company_state', 'state_id', 'company_id');
    }

    public function tax()
    {
        $company = App::make(\App\Services\Contexts\Context::class);

        return $this->hasOne(CompanyState::class)->whereCompanyId($company->id());
    }

    public function scopeNameOrCode($query, $keyword)
    {
        return $query->where('name', $keyword)
            ->orWhere('code', $keyword);
    }
}
