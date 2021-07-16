<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorTypes extends Model
{
	protected $fillable = ['company_id','name','display_order'];

	const SUB_CONTRACTOR = 2;
	const OTHERS = 4;
	const MEASUREMENTS = 1;
	const SUPPLIERS = 3;
}