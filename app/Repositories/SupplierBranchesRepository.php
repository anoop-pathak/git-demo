<?php namespace App\Repositories;

use App\Services\Contexts\Context;
use APp\Models\SupplierBranch;
use DB;

class SupplierBranchesRepository extends ScopedRepository
{
    protected $model;
    protected $scope;

	public function __construct(SupplierBranch $model, Context $scope)
	{
		$this->model = $model;
		$this->scope = $scope;
	}

	public function getFilteredBranches($companySupplier, $input = [])
	{
		$with = $this->getIncludes($input);
		$query = $companySupplier->supplierBranches()->with($with);

		$query->select('supplier_branches.*');

		if(ine($input, 'lat') && ine($input, 'long')) {
			$lat = $input['lat'];
			$long = $input['long'];

			$query->addSelect(DB::raw("( 3959 * acos( cos( radians({$lat}) ) * cos( radians( supplier_branches.lat ) )
				* cos( radians(supplier_branches.long) - radians({$long})) + sin(radians({$lat}))
				* sin( radians(supplier_branches.lat)))) as distance"));

			$query->orderBy('distance');
		}

		$this->applyFilters($query, $input);

		return $query;
	}

	/**
	 * assign divisions to branches
	 * @param  Array | $details | Array of details contains respective division branch and company default branch info
	 * @return boolean
	 */
	public function assignDivisions($details)
	{
		DB::table('supplier_branch_division')
			->where('company_id', getScopeId())
			->delete();

		SupplierBranch::where('company_id', getScopeId())
			->update(['default_company_branch' => false]);

		if(empty($details)) return true;

		$data = [];

		foreach ($details as $key => $value) {

			if(ine($value, 'default_company_branch')) {
				SupplierBranch::where('id', $value['branch_id'])
					->where('company_id', getScopeId())
					->update(['default_company_branch' => true]);

				continue;
			}

			$data[] = [
				'company_id' => getScopeId(),
				'division_id' => $value['division_id'],
				'supplier_branch_id' => $value['branch_id'],
			];
		}

		if(empty($data)) return true;

		DB::table('supplier_branch_division')->insert($data);

		return true;
	}

	/********** Private Section **********/

	private function applyFilters($query, $filters)
	{
		if(ine($filters, 'ship_to_id')) {
			$query->whereIn('id', function($query) use($filters) {
				$query->select('supplier_branch_id')
					->from('ship_to_address_branches')
					->where('srs_ship_to_address_id', $filters['ship_to_id']);
			});
		}

		if(ine($filters, 'division_id')) {
			$query->whereIn('id', function($query) use($filters) {
				$query->select('supplier_branch_division.supplier_branch_id')
					->from('supplier_branch_division')
					->where('supplier_branch_division.division_id', (array)$filters['division_id']);
			});
		}

		if(ine($filters, 'default_company_branch')) {
			$query->where('default_company_branch', true);
		}
	}

	private function getIncludes($input)
	{
		$with = [];

		if(!ine($input, 'includes')) return $with;

		$includes = (array)$input['includes'];

		if(in_array('divisions', $includes)) {
			$with[] = 'divisions';
		}

		if(in_array('ship_to_address', $includes)) {
			$with[] = 'srsShipToAddresses';
		}

		return $with;
	}
}