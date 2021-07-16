<?php

namespace App\Models;

class CustomerCustomField extends BaseModel
{
	protected $fillable = ['customer_id', 'name', 'value', 'type'];
 	const STRING_TYPE = 'string';
 	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}
} 