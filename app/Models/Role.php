<?php

namespace App\Models;

use Zizaco\Entrust\EntrustRole;

class Role extends EntrustRole
{

    protected $fillable = ['name'];

    protected $createRules = [
        'name' => 'required|min:4'
    ];

    protected function getRules()
    {
        return $this->createRules;
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', $name)->first();
    }
}
