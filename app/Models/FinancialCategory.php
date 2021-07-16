<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends BaseModel
{
    use SoftDeletes;

    const MATERIALS = 'MATERIALS';
    const LABOR     = 'LABOR';
    const NO_CHARGE = 'NO CHARGE';
    const MISC = 'MISC';
    const ACTIVITY = 'ACTIVITY';
    const INSURANCE = 'INSURANCE';

    protected $fillable = ['company_id', 'name', 'default', 'order'];

    protected $hidden = ['company_id', 'created_at', 'updated_at'];

    protected $rules = [
        'name' => 'required|unique:financial_categories,name',
        'default' => 'boolean|nullable',
    ];

    protected $append = ['locked'];

    public static function boot()
    {
        parent::boot();
        static::creating(function($model){
            $model->slug = strtolower(str_replace(' ', '_', $model->name));
        });

        static::deleting(function($model){
			if(\Auth::check()) {
				$model->deleted_by = \Auth::id();
				$model->save();
			}
		});
    }

    protected function getRules($id = null)
    {
        $rules = $this->rules;
        $rules['name'] = 'required|unique:financial_categories,name,' . $id . ',id,company_id,' . config('company_scope_id');

        return $rules;
    }

    public function details()
    {
        return $this->hasMany(FinancialDetail::class, 'category_id');
    }

    public function products()
    {
        return $this->hasMany(FinancialProduct::class, 'category_id')->where('financial_products.active', true);
    }

    public function macroDetails()
    {
        return $this->hasMany(FinancialProduct::class, 'category_id');
    }

    public function getLockedAttribute()
    {
        return in_array(strtoupper($this->name), ['LABOR','MATERIALS', 'MISC', self::NO_CHARGE, self::INSURANCE]);
    }

    public function financialAccount()
	{
		return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
	}
}
