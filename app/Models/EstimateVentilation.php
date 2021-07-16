<?php
namespace App\Models;

use Request;

class EstimateVentilation extends BaseModel
{

    const ADDITION = 'addition';
	const SUBTRACTION = 'subtraction';

    protected $fillable = ['company_id', 'type_id', 'fixed_amount', 'arithmetic_operation'];

    protected $hidden = [];

    protected $updateRules = [
		'fixed_amount'  => 'required|regex:/^\d+(\.\d{1,2})?$/',
		'arithmetic_operation'  => 'required|in:addition,subtraction'
	];

    protected function getRules()
	{
		$input = Request::all();

		if (ine($input,'ventilations')) {
			foreach ($input['ventilations'] as $key => $value) {
		        $rules['ventilations.' . $key . '.type_id'] = 'required|integer';
		        $rules['ventilations.' . $key . '.fixed_amount'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		        $rules['ventilations.' . $key . '.arithmetic_operation'] = 'required|in:addition,subtraction';
			}
		}else{
		    $rules['ventilations.0.type_id'] = 'required|integer';
		    $rules['ventilations.0.fixed_amount'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		    $rules['ventilations.0.arithmetic_operation'] = 'required|in:addition,subtraction';
		}

        return $rules;
	}

    protected function getUpdateRules()
	{
		return $this->updateRules;
	}
}