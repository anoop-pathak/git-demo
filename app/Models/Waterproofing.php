<?php
namespace App\Models;
use Request;

class Waterproofing extends BaseModel
{
	protected $table = 'waterproofing';

    protected $fillable = ['type_id', 'company_id', 'cost', 'cost_type'];

    protected $hidden = [];

	protected $rules = [
		'waterproofing_id' => 'required|integer',
		'cost_type' => 'required|in:per_sq_feet,fixed',
		'cost' 		=> 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected function getCreateRules()
	{
		$input = Request::all();

		if (ine($input,'types')) {
			foreach ($input['types'] as $key => $value) {
		        $rules['types.' . $key . '.cost'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		        $rules['types.' . $key . '.cost_type'] = 'required|in:per_sq_feet,fixed';
		        $rules['types.' . $key . '.waterproofing_id'] = 'required|integer';
			}
		}else{
			$rules['types.0.cost'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		    $rules['types.0.cost_type'] = 'required|in:per_sq_feet,fixed';
		    $rules['types.0.waterproofing_id'] = 'required|integer';
		}
		return $rules;
	}

    protected function getRules()
	{
		return $this->rules;
	}

    public function waterproofingType()
    {
        return $this->belongsTo(WaterproofingLevelType::class, 'type_id');
    }
}