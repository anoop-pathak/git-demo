<?php
namespace App\Models;

use Request;

class EstimateStructure extends BaseModel
{
	const STRUCTURE = 'structure';
	const COMPLEXITY = 'complexity';

    protected $fillable = ['company_id', 'type_id', 'amount', 'amount_type'];

    protected $hidden = [];

    protected $updateRules = [
		'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
		'amount_type' => 'required|in:per_sq_feet,fixed'
	];

    protected function getRules()
	{
		$input = Request::all();

        if (ine($input,'structures')) {
			foreach ($input['structures'] as $key => $value) {
		        $rules['structures.' . $key . '.type_id'] = 'required|integer';
		        $rules['structures.' . $key . '.amount'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		        $rules['structures.' . $key . '.amount_type'] = 'required|in:per_sq_feet,fixed';
			}
		}else{
		    $rules['structures.0.type_id'] = 'required|integer';
		    $rules['structures.0.amount'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		    $rules['structures.0.amount_type'] = 'required|in:per_sq_feet,fixed';
		}

        return $rules;
	}

    protected function getUpdateRules()
	{
		return $this->updateRules;
	}
}