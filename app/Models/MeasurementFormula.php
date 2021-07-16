<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Request;

class MeasurementFormula extends Model
{

    protected $fillable = [
        'trade_id', 'product_id', 'formula', 'company_id', 'options', 'active'
    ];

    protected $singleFormulaRule = [
        'trade_id' => 'required',
        'product_id' => 'required'
    ];

    protected $rules = [
        'product_id' => 'required',
        'trade_id' => 'required',
        'formula' => 'required',
    ];

    protected $removeFormulaRule = [
		'trade_id'   => 'required|exists:trades,id',
		'product_id' => 'required|exists:financial_products,id'
	];

    protected function getMultipleFormulaRules()
    {
        $input = Request::all();
        $rules['product_id'] = 'required';
        if (ine($input,'formulas')) {
            foreach ($input['formulas'] as $key => $value) {
                $rules['formulas.' . $key . '.trade_id'] = 'required';
                $rules['formulas.' . $key . '.formula'] = 'required';
            }
        }else{
            $rules['formulas.0.trade_id'] = 'required';
            $rules['formulas.0.formula'] = 'required';
        }

        return $rules;
    }

    protected function getSingleFormulaRule()
    {
        return $this->singleFormulaRule;
    }

    protected function getRules()
    {
        return $this->rules;
    }

    protected function removeFormulaRule()
	{
		return $this->removeFormulaRule;
	}

    public function setOptionsAttribute($value)
    {
        $value = ($value) ? json_encode($value) : null;

        $this->attributes['options'] = $value;
    }

    public function getOptionsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
