<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Address;

class Division extends BaseModel
{

    use SoftDeletes;

    protected $table = 'divisions';

    protected $fillable = ['company_id', 'name', 'color', 'qb_id', 'email', 'phone', 'phone_ext', 'code', 'origin'];

    protected $hidden = ['created_at', 'updated_at'];

    protected function getRules()
    {
        $rules['name'] = 'required|unique:divisions,name,null,id,company_id,' . config('company_scope_id');
        $rules['code'] = 'AlphaNum|max:3';

        return $rules;
    }

    protected function getUpdateRules($id)
    {
        $rules['name'] = 'required|unique:divisions,name,' . $id . ',id,company_id,' . config('company_scope_id');
        $rules['code'] = 'AlphaNum|max:3';

        return $rules;
    }

    protected function jobs()
    {
        return $this->hasMany(Job::class);
    }

    protected function address()
    {
        return $this->belongsTo(Address::class);
    }

    protected function users()
    {
        return $this->belongsToMany(User::class, 'user_division', 'division_id', 'user_id');
    }

    protected function macros()
    {
        return $this->belongsToMany(FinancialMacro::class, 'macro_division', 'division_id', 'macro_id');
    }

    public function supplierBranches()
    {
        return $this->belongsToMany(SupplierBranch::class, 'supplier_branch_division')
            ->where('supplier_branches.company_id', getScopeId());
    }
}
