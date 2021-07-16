<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends BaseModel
{

    use SoftDeletes;

    const SRS_SUPPLIER = 'SRS';
    const ABC_SUPPLIER = 'ABC Supply';

    const ABC_SUPPLIER_ID = 1;

    protected $fillable = ['name'];

    public $hidden = ['deleted_at', 'created_at', 'updated_at', 'pivot'];

    public function companySupplier()
    {
        return $this->hasOne(CompanySupplier::class)->where('company_id', getScopeId());
    }

    //this relation is used in command.
	public function company() {
		return $this->belongsTo(Company::class);
	}

    public function scopeSystem($query)
    {
        $query->where(function ($query) {
            $query->whereNull('company_id')
                ->orWhere('company_id', 0);
        });
    }

    public function isSystemSypplier()
    {
        if ($this->company_id == null) {
            return true;
        }
    }

    public function financialProducts()
    {
        return $this->hasMany(FinancialProduct::class);
    }

    protected function srs()
    {
        $supplier = self::whereName(self::SRS_SUPPLIER)
            ->whereNull('company_id')
            ->first();

        return $supplier;
    }
}
