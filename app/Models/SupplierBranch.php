<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use JobQueue;

class SupplierBranch extends BaseModel
{
	use SoftDeletes;
 	protected $fillable = [
		'company_id', 'company_supplier_id', 'branch_id', 'branch_code', 'name', 'address', 'city', 'state', 'zip', 'email', 'phone', 'manager_name', 'logo', 'meta', 'default_company_branch'
	];

 	public function setMetaAttribute($value)
	{
		$this->attributes['meta'] = json_encode($value);
	}

 	public function getMetaAttribute($value)
	{
		return json_decode($value, true);
	}

 	/********** Relations **********/

 	public function srsShipToAddresses()
	{
		return $this->belongsToMany(SrsShipToAddress::class, 'ship_to_address_branches', 'supplier_branch_id', 'srs_ship_to_address_id')
			->where('company_id', getScopeId());
	}

 	public function financialProducts()
	{
		return $this->hasMany(FinancialProduct::class, 'branch_code', 'branch_code')->where('financial_products.company_id', getScopeId());
	}

	public function queueStatus()
	{
		return $this->hasOne(QueueStatus::class, 'entity_id', 'id')
			->where('queue_statuses.action', JobQueue::SRS_SAVE_BRANCH_PRODUCT)
			->latest('id');
	}

	public function divisions()
	{
		return $this->belongsToMany(Division::class, 'supplier_branch_division')
			->where('supplier_branch_division.company_id', getScopeId());
	}

 	/********** Relations End **********/
	protected function getAssignDivisionRules()
	{
		$rules = [
			'details' => 'required|array'
		];

		$details = array_filter((array)Request::get('details'));

		foreach ($details as $key => $detail) {
			$rules["details.{$key}.branch_id"]	 = 'required';
			$rules["details.{$key}.division_id"] = "required_without:details.{$key}.default_company_branch";
		}

		return $rules;
	}
}
