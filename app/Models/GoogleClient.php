<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleClient extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'email',
        'token',
        'next_sync_token',
        'channel_id',
        'channel_expiration_time',
        'resource_id',
        'calender_id',
        'scope_calendar_and_tasks',
        'scope_drive',
        'scope_google_sheet',
        'scope_gmail'
    ];

    protected $rules = [
        'user_id' => 'required',
        'email' => 'required|email|unique:google_clients,email',
        'token' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isCompanyAccount()
    {
        return ($this->company_id);
    }

    public function scopeCalendar($query)
    {
        $query->where('scope_calendar_and_tasks', true);
    }

    public function scopeDrive($query)
    {
        $query->where('scope_drive', true);
    }

    public function scopeGoogleSheet($query)
    {
        $query->where('scope_google_sheet', true);
    }

    public function isCalendarEnabled()
    {
        return ($this->scope_calendar_and_tasks);
    }

    public function isDriveEnabled()
    {
        return ($this->scope_drive);
    }

    public function isGoogleSheetsEnabled()
    {
        return ($this->scope_google_sheet);
    }
}
