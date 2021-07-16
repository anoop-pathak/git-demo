<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'device_token',
        'uuid',
        'app_version',
        'platform',
        'manufacturer',
        'os_version',
        'model',
        'session_id',
        'is_primary_device'
    ];

    protected $hidden = ['created_at', 'updated_at', 'company_id', 'device_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $rules = [
        'uuid' => 'required',
        'app_version' => 'required',
        'platform' => 'required',
        'manufacturer' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function setPlatformAttribute($value)
    {
        $this->attributes['platform'] = strtolower($value);
    }

    public function scopeWhereUsersIn($query, $userIds = [])
	{
		if(!$userIds) {
			return $query;
		}

		$query->whereIn('user_id', $userIds);
	}
}
