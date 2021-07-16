<?php

namespace App\Models;

class NetworkMeta extends BaseModel
{

    /**
     * The database table used by the model
     *
     * @var string
     */
    protected $table = 'network_meta';

    protected $fillable = ['meta_key', 'meta_value', 'network_id'];

    public function setMetaValueAttribute($value)
    {
        return $this->attributes['meta_value'] = json_encode($value, true);
    }

    public function getMetaValueAttribute($value)
    {
        return json_decode($value, true);
    }

    public function Network()
    {
        return $this->belongsTo(CompanyNetwork::class, 'network_id');
    }
}
