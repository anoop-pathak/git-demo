<?php
namespace App\Models;

class SubscriberStageAttribute extends Basemodel
{
	protected $fillable = ['name' , 'color_code'];

	public static function defaultCompanyAssignedAttribute()
	{
		return self::where('name', 'Blank')->first();
	}
}