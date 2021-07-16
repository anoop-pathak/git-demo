<?php
namespace App\Models;

class VendorBillAttachment extends BaseModel {

	public $timestamps = false;
	protected $fillable = ['vendor_bill_id','type','value'];
}