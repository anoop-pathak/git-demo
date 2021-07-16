<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Note : Not using this model for labour module, labours are bind with user repository
 */
class Labour extends BaseModel
{

    use SoftDeletes;

    protected $table = 'labours';

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'phones',
        'address',
        'address_line_1',
        'city',
        'state_id',
        'country_id',
        'zip',
        'type',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // types
    const LABOUR = 'labor';
    const SUB_CONTRACTOR = 'sub_contractor';

    protected $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'required|email|unique:users,email',
        'address' => 'required',
        'city' => 'required',
        'state_id' => 'required',
        'zip' => 'required',
        'country_id' => 'required',
        'additional_phone' => 'array',
        'type' => 'in:labor,sub_contractor'
    ];

    protected $updateRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'type' => 'in:labor,sub_contractor'
    ];

    protected $fileRules = [
        'file' => 'required|mime_types:text/plain,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/csv,application/octet-stream'
    ];

    protected $profilePicRules = [
        'labour_id' => 'required',
        'image' => 'required|mimes:jpeg,png'
    ];

    protected $activateDeactivateRules = [
        'labour_ids' => 'required',
        'is_active' => 'required|boolean'

    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getUpdateRules()
    {
        return $this->updateRules;
    }

    protected function getFileRules()
    {
        return $this->fileRules;
    }

    protected function getProfilePicRules()
    {
        return $this->profilePicRules;
    }

    protected function getActivateDeactivateRules()
    {
        return $this->activateDeactivateRules;
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function setPhonesAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['phones'] = json_encode($value);
    }

    public function getPhonesAttribute($value)
    {
        return json_decode($value);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullNameMobileAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
