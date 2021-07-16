<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;

class PhoneCall extends BaseModel {

 	use SortableTrait;

 	protected $fillable = ['sid', 'company_id', 'from_number', 'to_number', 'call_by', 'duration', 'status','customer_id'];

 	public function customer(){
		return $this->belongsTo(Customer::class, 'customer_id');
	}

}