<?php

namespace App\Models;

class CompanyNetwork extends BaseModel
{

    /**
     * The database table used by the model
     *
     * @var string
     */
    protected $table = 'company_networks';

    protected $rule = [
        'network' => 'required',
        'message' => 'required',
        'attachments' => 'array|nullable'
    ];

    const FACEBOOK = 'facebook';
    const TWITTER = 'twitter';
    const LINKEDIN = 'linkedin';
    const PAGES = 'pages';

    protected $connectRule = [
        'network' => 'required|in:twitter,facebook,linkedin',
        'token' => 'required|array'
    ];

    protected function getConnectRule()
    {
        return $this->connectRule;
    }

    protected function getRule()
    {
        return $this->rule;
    }

    protected function getPostRule()
    {
        return $this->postRule;
    }

    public function getTokenAttribute()
    {

        return (array)json_decode($this->attributes['token']);
    }

    public function setTokenAttribute($value)
    {
        return $this->attributes['token'] = json_encode($value);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function networkMeta()
    {
        return $this->hasOne(NetworkMeta::class, 'network_id');
    }
}
