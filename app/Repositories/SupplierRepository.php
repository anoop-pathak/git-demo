<?php namespace App\Repositories;

use App\Models\CompanySupplier;
use App\Models\Supplier;
use App\Services\Contexts\Context;
use App\Models\SupplierBranch;


class SupplierRepository extends ScopedRepository
{

    protected $model;
    protected $scope;

    function __construct(Supplier $model, Context $scope)
    {

        $this->model = $model;
        $this->scope = $scope;
    }

    public function getFilteredSuppliers($filters = [])
    {
        $suppliers = $this->model->with('companySupplier');

        $this->applyFilters($suppliers, $filters);

        return $suppliers;
    }

    /**
     * create supplier
     * @param  array $input
     * @return $supplier
     */
    public function create($input)
    {
        $supplier = $this->model;
        $supplier->name = $input['name'];
        $supplier->company_id = $this->scope->id();
        $supplier->save();

        $companySupplier                = new CompanySupplier;
        $companySupplier->company_id    = $supplier->company_id;
        $companySupplier->supplier_id   = $supplier->id;
        $companySupplier->save();

        // $this->saveBranch($companySupplier, $input);

        return $supplier;
    }

    /**
     * update supplier info
     * @param  $supplier
     * @param  $input
     * @return $supplier
     */
    public function update($supplier, $input)
    {
        if (ine($input, 'name') && $supplier->company_id) {
            $supplier->name = $input['name'];
        }

        $supplier->save();

        if ($supplier->companySupplier) {
            $supplier->companySupplier->update($input);
        }

        return $supplier;
    }

    /**
     * check supplier exists
     * @param  $id
     * @return boolean
     */
    public function isSupplierExist($id)
    {
        $filter['supplier_id'] = $id;

        $supplier = $this->getFilteredSuppliers($filter)->exists();

        return $supplier;
    }

    /**
     * check system supplier
     * @param  $id
     * @return boolean
     */
    public function isSystemSupplier($id)
    {
        $filter['system'] = true;
        $filter['supplier_id'] = $id;

        $supplier = $this->getFilteredSuppliers($filter)->exists();

        return $supplier;
    }

    /**
     * check custom supplier
     * @param  $id
     * @return boolean
     */
    public function isCustomSupplier($id)
    {
        $filter['custom'] = true;
        $filter['supplier_id'] = $id;

        $supplier = $this->getFilteredSuppliers($filter)->exists();

        return $supplier;
    }

    /**
     * get branch list of a supplier
     * @param  Object $companySupplier
     * @param  Array $input
     * @return Object $branchList
     */
    public function getbranchList($companySupplier, $input)
    {
        $with = $this->getBranchIncludes($input);

		$query = $companySupplier->supplierBranches()->with($with);
        if(ine($input, 'ship_to_id')) {
            $query->whereIn('id', function($query) use($input) {
                $query->select('supplier_branch_id')
                    ->from('ship_to_address_branches')
                    ->where('srs_ship_to_address_id', $input['ship_to_id']);
            });
        }
        $query->select('supplier_branches.*');
        if(ine($input, 'lat') && ine($input, 'long')) {
            $lat = $input['lat'];
            $long = $input['long'];
            $query->addSelect(\DB::raw("( 3959 * acos( cos( radians({$lat}) ) * cos( radians( supplier_branches.lat ) )
                        * cos( radians(supplier_branches.long) - radians({$long})) + sin(radians({$lat}))
                        * sin( radians(supplier_branches.lat)))) as distance"));
            $query->orderBy('distance');
        }
        return $query;
    }

    /************** Protected Section *****************/

    protected function applyFilters($query, $filters = [])
    {
        if (ine($filters, 'system')) {
            $query->system();
        } elseif (ine($filters, 'custom')) {
            $query->where('company_id', $this->scope->id());
        } else {
            $query->where(function ($query) {
                $query->where('company_id', $this->scope->id())
                    ->orWhere('company_id', null);
            });
        }

        if (ine($filters, 'supplier_id')) {
            $query->where('id', $filters['supplier_id']);
        }

        if (ine($filters, 'name')) {
            $query->where('name', $filters['name']);
        }
    }

    /************** Protected Section End *****************/

    /************** Private Section *****************/

    private function saveBranch($companySupplier, $branchDetail)
    {
        $branch = new SupplierBranch;
        $branch->company_id          = $companySupplier->company_id;
        $branch->company_supplier_id = $companySupplier->id;
        $branch->branch_id           = ine($branchDetail, 'branch_id') ? $branchDetail['branch_id'] : null;
        $branch->branch_code         = ine($branchDetail, 'branch_code') ? $branchDetail['branch_code'] : null;
        $branch->name                = ine($branchDetail, 'name') ? $branchDetail['name'] : null;
        $branch->address             = ine($branchDetail, 'address') ? $branchDetail['address'] : null;
        $branch->city                = ine($branchDetail, 'city') ? $branchDetail['city'] : null;
        $branch->state               = ine($branchDetail, 'state') ? $branchDetail['state'] : null;
        $branch->zip                 = ine($branchDetail, 'zip') ? $branchDetail['zip'] : null;
        $branch->email               = ine($branchDetail, 'email') ? $branchDetail['email'] : null;
        $branch->phone               = ine($branchDetail, 'phone') ? $branchDetail['phone'] : null;
        $branch->manager_name        = ine($branchDetail, 'manager_name') ? $branchDetail['manager_name'] : null;
        $branch->save();

        return $companySupplier;
    }

    private function getBranchIncludes($input)
	{
		$includes = (array)issetRetrun($input, 'includes') ?: [];

		$with = [];
		if(in_array('queue_status', $includes)) {
			$with[] = 'queueStatus';
		}

		return $with;
	}
}
