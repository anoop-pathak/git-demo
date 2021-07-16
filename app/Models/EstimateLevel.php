<?php
namespace App\Models;

use Request;

class EstimateLevel extends BaseModel
{
	const ESTIMATELEVEL = 'levels';

    protected $fillable = ['type_id', 'company_id', 'fixed_amount'];

	protected $hidden = [];

    protected $rules = [
		'level_id' 			=>	'required|integer',
		'fixed_amount' 		=> 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected function getCreateRules()
	{
		$input = Request::all();

		if (ine($input,'types')) {
            foreach ($input['types'] as $key => $value) {
		        $rules['types.' . $key . '.fixed_amount'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		        $rules['types.' . $key . '.level_id'] = 'required|integer';
			}
		}else{
		    $rules['types.0.fixed_amount'] = 'required|regex:/^\d+(\.\d{1,2})?$/';
		    $rules['types.0.level_id'] = 'required|integer';
		}

        return $rules;
	}

    protected function getRules()
	{
		return $this->rules;
	}

    public function shingles()
    {
        return $this->belongsToMany(FinancialProduct::class, 'estimate_shingles', 'level_id', 'product_id');
    }

    public function underlayments()
    {
        return $this->belongsToMany(FinancialProduct::class, 'estimate_underlayments', 'level_id', 'product_id');
    }

    public function levelType()
    {
        return $this->belongsTo(WaterproofingLevelType::class, 'type_id');
    }
}