<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountManager extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'state_id',
        'contact',
        'email',
        'notes',
        'additional_emails',
        'additional_phones',
        'uuid',
        'social_security_number',
        'for_all_trades',
        'address',
        'address_line_1',
        'city',
        'country_id',
        'zip',
        'managing_state_id'
    ];

    protected $appends = ['full_name', 'full_name_mobile'];

    protected static $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'state_id' => 'required',
        'trades' => 'array|required_if:for_all_trades,0|required_without:for_all_trades',
        'email' => 'required|email|unique:account_managers,email',
        'social_security_number' => 'required',
        'address' => 'required',
        'city' => 'required',
        'country_id' => 'required',
        'zip' => 'required',
        'managing_state_id' => 'required'
    ];

    protected static $updateRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'state_id' => 'required',
        'trades' => 'array|required_if:for_all_trades,0|required_without:for_all_trades',
        'email' => 'required|email',
        'social_security_number' => 'required',
        'address' => 'required',
        'city' => 'required',
        'country_id' => 'required',
        'zip' => 'required',
        'managing_state_id' => 'required'
    ];

    protected static $uploadProfilePic = [
        'account_manager_id' => 'required',
        'image' => 'required|mimes:jpeg,png'
    ];

    public static function getRules()
    {
        return self::$rules;
    }

    public static function getUpdateRules()
    {
        return self::$updateRules;
    }

    public static function getUploadProPicRule()
    {
        return self::$uploadProfilePic;
    }

    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'account_manager_trade', 'account_manager_id', 'trade_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function managingState()
    {
        return $this->belongsTo(State::class, 'managing_state_id');
    }

    public function subscribers()
    {
        return $this->hasMany(Company::class, 'account_manager_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getAdditionalEmailsAttribute($value)
    {
        return json_decode($value);
    }

    public function setAdditionalEmailsAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['additional_emails'] = json_encode($value);
    }

    public function getAdditionalPhonesAttribute($value)
    {
        return json_decode($value);
    }

    public function setAdditionalPhonesAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['additional_phones'] = json_encode($value);
    }

    public function getFullNameAttribute()
    {
        if (empty($this->last_name)) {
            return $this->first_name;
        }

        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullNameMobileAttribute()
    {
        if (empty($this->last_name)) {
            return $this->first_name;
        }

        return $this->first_name . ' ' . $this->last_name;
    }

    public static function boot()
    {
        parent::boot();

        // We set the deleted_by attribute before deleted event so we doesn't get an error if Customer was deleted by force (without soft delete).
        static::deleting(function ($accountManager) {

            $accountManager->deleted_by = \Auth::user()->id;
            $accountManager->save();
        });

        // after save event
        static::created(function ($accountManager) {
            // create a 4 digit uuid..
            $accountManager->uuid = sprintf("%04d", $accountManager->id);
            $accountManager->save();
        });
    }
}
