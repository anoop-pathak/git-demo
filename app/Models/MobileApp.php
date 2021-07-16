<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class MobileApp extends BaseModel
{

    use SoftDeletes;

    protected $fillable = ['version', 'device', 'description', 'forced', 'url', 'approved'];

    // devices..
    const IOS = 'ios';
    const ANDROID = 'android';

    protected $dates = ['deleted_at'];

    protected $rules = [
        'device' => 'required|in:ios,android',
        'version' => 'required',
        'forced' => 'boolean',
        'approved' => 'boolean',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    /**
     * **
     * save Auth Id on mobile app soft delete
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($mobileApp) {
            $mobileApp->deleted_by = \Auth::user()->id;
            $mobileApp->save();
        });
    }
}
