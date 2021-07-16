<?php

namespace App\Models;

use App\Services\Masking\DataMasking;

class Phone extends BaseModel
{

    public $fillable = ['number', 'label', 'customer_id', 'ext'];

    protected $hidden = ['created_at', 'updated_at'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getNumberAttribute($value)
	{
		$dataMasking = new DataMasking;
		$number = $dataMasking->maskPhone($value);
 		return $this->attributes['number'] = $number;
	}
 	public function getExtAttribute($value)
	{
		$dataMasking = new DataMasking;
		$ext = $dataMasking->maskPhoneExtention($value);
 		return $this->attributes['ext'] = $ext;
	}

	public function contacts() {
		return $this->belongsToMany(Contact::class, 'contact_phone', 'contact_id', 'phone_id')
			->withPivot('is_primary')
			->withTimestamps();
	}
}
