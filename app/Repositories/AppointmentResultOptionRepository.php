<?php
namespace App\Repositories;

use App\Models\AppointmentResultOption;
use App\Services\Contexts\Context;

class AppointmentResultOptionRepository extends ScopedRepository {

 	function __construct(AppointmentResultOption $model, Context $scope){
		$this->model = $model;
		$this->scope = $scope;
	}

	public function getBuilder($filters = [])
	{
		$query = $this->make();
		$this->applyFilters($query, $filters);

		return $query;
	}

 	/**
	* save or update appointment result options
	*
	* @param $name
	* @param array of $fields
	* @return $resOption
	*/
	public function saveOrUpdate($name, $fields, $resOption = null)
	{
		if($resOption) {
			$resOption->name = $name;
			$resOption->fields = $fields;
			$resOption->save();
		}else {
			$resOption = AppointmentResultOption::create([
				'company_id' => getScopeId(),
				'name'		 => $name,
				'created_by' => \Auth::id(),
				'fields'	 => $fields,
				'active'	 => true,
			]);
		}

 		return $resOption;
	}
 	/**
	* get appointment listing with appointments count
	*
	* @return collection
	*/
	public function getFilteredResults($filters = [])
	{
		$companyId = getScopeId();
 		$query = $this->make()
			->leftJoin('appointments', function($join) {
				$join->on('appointment_result_options.id','=', 'appointments.result_option_id')
					->whereNull('appointments.deleted_at');
			})
			->sortable()
			->selectRaw('appointment_result_options.*, count(appointments.id) as appointment_count')
			->where('appointment_result_options.company_id',$companyId)
			->groupBy('appointment_result_options.id');
 		$this->applyFilters($query, $filters);
 		return $query;
	}
 	/*************** Private Methods ***************/
 	private function applyFilters($query, $filters)
	{
		if(isset($filters['active'])) {
			$query->where('active', (bool) $filters['active']);
		}

		if(!isset($filters['active']) && ine($filters, 'exclude_inactive')) {
			$query->where('active', true);
		}

 		if(ine($filters, 'ids')) {
			$query->whereIn('appointment_result_options.id', (array) $filters['ids']);
		}
	}
}