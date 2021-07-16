<?php
namespace  App\Repositories;

use App\Services\Contexts\Context;
use App\Models\ClickThruEstimate;

Class ClickThruEstimateRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    function __construct(ClickThruEstimate $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}
	public function save(
		$name,
		$jobId,
		$customerId,
		$manufacturerId,
		$level,
		$type,
		$waterproofing,
		$shingle,
		$underlayment,
		$warranty,
		$roofSize,
		$pitch,
		$meta
	){
		$estimate = new ClickThruEstimate;
		$data = [
			'name' => $name,
			'job_id' => $jobId,
			'company_id' => getScopeId(),
			'customer_id' => $customerId,
			'manufacturer_id' => $manufacturerId,
			'level' => $level,
			'type' => $type,
			'waterproofing' => $waterproofing,
			'shingle' => $shingle,
			'underlayment' => $underlayment,
			'warranty' => $warranty,
			'roof_size' => $roofSize,
			'structure' =>$meta['structure'],
			'complexity' => $meta['complexity'],
			'pitch' => $pitch,
			'chimney' => $meta['chimney'],
			'skylight' => $meta['skylight'],
			'others' => $meta['others'],
			'access_to_home' => $meta['access_to_home'],
			'gutter' => $meta['gutter'],
			'notes' => $meta['notes'],
			'adjustable_amount' => $meta['adjustable_amount'],
			'adjustable_note' => $meta['adjustable_note'],
			'amount' => $meta['amount']
		];

        if(ine($meta, 'id')){
			$estimate = $this->model->find($meta['id']);
			$estimate->update($data);
			$users = isset($meta['users']) ? $meta['users']  :[];
			$estimate->users()->sync(arry_fu($users));
		}else{
			$estimate = $this->model->create($data);
        }

		if(ine($meta, 'users')){
			$estimate->users()->sync(arry_fu($meta['users']));
        }

		return $estimate;
	}
	public function update()
	{
	}
	public function getFilteredEstimates($filters = [], $sortable = true)
	{
        $includeData = $this->includeData($filters);

		if($sortable){
			$estimates = $this->make($includeData)->Sortable();
		}else{
			$estimates = $this->make($includeData);
		}
		$this->applyFilters($estimates, $filters);

        return $estimates;
	}
	/*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    	if(ine($filters, 'job_ids')){
    		$query->whereIn('job_id', (array) $filters['job_ids']);
    	}
    	if(ine($filters, 'manufacturer_ids')){
    		$query->whereIn('manufacturer_id', (array) $filters['manufacturer_ids']);
    	}
    	if(ine($filters, 'customer_ids')){
    		$query->whereIn('customer_id', (array) $filters['customer_ids']);
    	}
	}
    private function includeData($input)
    {
    	$with = [];
		$includes = isset($input['includes']) ? $input['includes'] : [];
		if(!is_array($includes) || empty($includes)) return $with;
		if(in_array('job', $includes)) {
			$with[] = 'job';
		}
		if(in_array('users', $includes)) {
			$with[] = 'users';
		}
		return $with;
    }
}