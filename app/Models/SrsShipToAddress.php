<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;

class SrsShipToAddress extends BaseModel
{
	use SoftDeletes;
	use PresentableTrait;
 	protected $presenter = 'App\Services\Presenter\SrsShipToAddressPresenter';
 	protected $fillable = [
		'company_id', 'company_supplier_id', 'ship_to_id', 'ship_to_sequence_id', 'city', 'state', 'zip_code',
		'address_line1', 'address_line2', 'address_line3', 'meta',
	];

 	public function setMetaAttribute($value)
	{
		$this->attributes['meta'] = json_encode($value);
	}

 	public function getMetaAttribute($value)
	{
		return json_decode($value, true);
	}

 	/********** Relation Section  **********/

 	public function supplierBranches()
	{
		return $this->belongsToMany(SupplierBranch::class, 'ship_to_address_branches', 'srs_ship_to_address_id', 'supplier_branch_id')
			->where('company_id', getScopeId());
	}

 	/********** Relation Section End **********/
} 