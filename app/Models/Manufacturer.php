<?php
namespace App\Models;

class Manufacturer extends BaseModel
{
	protected $fillable = ['name', 'logo'];

    protected $hidden = [];
}