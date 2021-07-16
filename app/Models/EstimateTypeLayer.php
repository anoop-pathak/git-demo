<?php
namespace App\Models;

use Request;

class EstimateTypeLayer extends BaseModel
{
	const TYPEID = 2;

    protected $fillable = ['company_id', 'layer_id', 'estimate_type_id', 'cost', 'cost_type'];

    protected $hidden = [];

	protected $updateRules = [
		'layer_id' => 'required|integer',
		'cost_type' => 'required|in:per_sq_feet,fixed',
		'cost' => 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected function getCreateRules()
	{
		$input = Request::all();

        if (ine($input,'layers')) {
			foreach ($input['layers'] as $key => $value) {
		        $rules['layers.' . $key . '.cost'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		        $rules['layers.' . $key . '.cost_type'] = 'required|in:per_sq_feet,fixed';
		        $rules['layers.' . $key . '.layer_id'] = 'required|integer';
			}
		}else{
			$rules['layers.0.cost'] = 'required';
		    $rules['layers.0.cost_type'] = 'required|in:per_sq_feet,fixed';
		    $rules['layers.0.layer_id'] = 'required|integer';
		}

        return $rules;
	}

    protected function getUpdateRules()
	{
		return $this->updateRules;
	}

    public function type(){
		return $this->belongsTo(EstimateType::class, 'type_id');
	}

    public function manufacturer(){
		return $this->belongsTo(Manufacturer::class);
	}
}