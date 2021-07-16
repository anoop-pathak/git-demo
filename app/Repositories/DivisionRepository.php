<?php

namespace App\Repositories;

use App\Models\Division;
use App\Services\Contexts\Context;
use App\Services\QuickBooks\QuickBookDivision;
use App\Services\QuickBooks\QuickBookService;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use App\Services\QuickBooks\Facades\Department as QBDepartment;

class DivisionRepository extends ScopedRepository
{
    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    protected $quickService;
    protected $qbDivision;

    function __construct(Division $model, Context $scope, QuickBookDivision $qbDivision, QuickBookService $quickService)
    {
        $this->scope = $scope;
        $this->model = $model;
        $this->qbDivision = $qbDivision;
        $this->quickService = $quickService;
    }


    /**
     * @ return all labour or sub_contractor
     */
    public function getDivisions(array $filters = [])
    {
        $with = $this->getIncludes($filters);
		$divisions =  $this->make($with);

        $this->applyFilters($divisions, $filters);

        return $divisions;
    }

    /**
     * @ save/update labour or sub_contractor
     */
    public function save($input)
    {
        $input['company_id'] = $this->scope->id();
        $division = '';
        if (ine($input, 'id')) {
            $division = $this->make()->whereId($input['id'])->first();
        }
        if ($division) {
            //update division
            $division->name = $input['name'];
            $division->color = $input['color'];

            if(isset($input['qb_id'])) {
				$division->qb_id = $input['qb_id'];
			}

			if(ine($input, 'unlink_qb')) {
				$division->qb_id = null;
			}

            if(isset($input['email'])) {
                $division->email = issetRetrun($input, 'email') ?: null;
            }
            if(isset($input['phone'])) {
                $division->phone = issetRetrun($input, 'phone') ?: null;
            }

            if(isset($input['phone_ext'])) {
                $division->phone_ext = issetRetrun($input, 'phone_ext') ?: null;
            }

            if(isset($input['code'])) {
				$division->code = issetRetrun($input, 'code') ?: null;
			}

            $division->save();
        } else {
            $count = $this->make()->withTrashed()->count();

            // get default color
            $input['color'] = config('default-colors.' . $count);

            if(isset($input['code'])) {
				$input['code'] = issetRetrun($input, 'code') ?: null;
			}

            //create new division
            $division = Division::create($input);
        }

        //sync on quickbook
        if (ine($input, 'sync_on_qb') && !ine($input, 'unlink_qb')) {
            $token = $this->quickService->getToken();
            QBDepartment::createOrUpdateDivision($division);
            // $this->qbDivision->createOrUpdateDivision($token, $division);
        }

        if(ine($input, 'address')) {
            $address = $this->saveOrUpdateAddress($division, $input);
            $division->setRelation('address', $address);
        }

        return $division;
    }

    /*************** Private Methods ***************/
    private function saveOrUpdateAddress($division, $input)
    {
        if($address = $division->address) {
            $address->update($input);
        }else {
            $address = Address::create($input);
            $division->address_id = $address->id;
            $division->save();
        }
        return $address;
    }

    private function applyFilters($query, $filters)
    {
        if(!ine($filters,'division_ids')){
          	$user = Auth::user();
			if(!$user->all_divisions_access && !$user->isAuthorisedUser()){
				return $query->whereIn('divisions.id', (array) getDivisions());
			}
	    }
         if(ine($filters,'division_ids')){
         	return $query->whereIn('divisions.id', (array) $filters['division_ids']);
        }
         if(ine($filters,'company_ids')){
         	return $query->whereIn('divisions.company_id', (array) $filters['company_ids']);
        }
    }


    /**
     * get includes for eagerloading
     * @param  Array  | $input | Array of inputs
     * @return Array $with
     */
    private function getIncludes($input)
    {
    	$with = [];

    	if(!ine($input, 'includes')) return $with;

    	$includes = (array)$input['includes'];

    	if(in_array('supplier_branches', $includes)) {
    		$with[] = 'supplierBranches';
    	}

    	return $with;
    }

	/**
	 * Create or Update Department from QuickBooks
	 */
	public function createOrUpdateDivision($meta)
	{
		$qbId = $meta['Id'];

		if($meta['Active'] == 'false') {
			$this->qbDeleteDivision($qbId);
			return;
		}

		$division = Division::where('qb_id', $qbId)
			->where('company_id', getScopeId())
			->first();

		if($division) {

			$division->name = $meta['Name'];
			$division->qb_id = $qbId;
			$division->save();
			return;
		}

		$division = Division::firstOrNew([
			'company_id' => getScopeId(),
			'name' => $meta['Name']
		]);

		$division->name = $meta['Name'];
		$division->qb_id = $qbId;
		$division->save();

	}

	/**
	 * Handle delete operation from QuickBooks
	 */
	public function qbDeleteDivision($qbId)
	{
		$companyId = getScopeId();

		$division = Division::where('qb_id', $qbId)
			->where('company_id', $companyId)
			->first();

		// Instead of deleting the divisions We will just remove link of QuickBooks
		$division->qb_id = null;

		$division->save();
	}
}
