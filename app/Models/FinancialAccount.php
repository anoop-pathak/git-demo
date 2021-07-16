<?php
namespace App\Models;
use Carbon\Carbon;
use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends BaseModel
{
	use SortableTrait;
    use SoftDeletes;

	protected $fillable = ['name', 'parent_id', 'created_by', 'company_id', 'classification', 'account_type', 'account_sub_type', 'level', 'description', 'origin', 'qb_desktop_id', 'qb_desktop_sequence_number', 'qb_desktop_delete'];

	protected function getUpdateRules($id)
    {
        $companyId = getScopeId();
        $updateRules['name'] = "required|max:100|unique:financial_accounts,name,{$id},id,company_id,{$companyId}";
        $updateRules['account_type']     = 'required';
        $updateRules['description']     = 'max:100';
        $updateRules['account_sub_type'] = 'required';

        return $updateRules;
    }

    protected function getCreateRules()
    {
        $companyId = getScopeId();
        $rules['name'] = "required|max:100|unique:financial_accounts,name,null,id,company_id,{$companyId}";
        $rules['account_type']     = 'required';
        $rules['description']      = 'max:100';
        $rules['account_sub_type'] = 'required';

        return $rules;
    }

	public function getCreatedAtAttribute($value)
	{
		return Carbon::parse($value)->format('Y-m-d H:i:s');
	}

	public function getUpdatedAtAttribute($value)
	{
		return Carbon::parse($value)->format('Y-m-d H:i:s');
	}

    public function subAccounts()
    {
        return $this->hasMany(FinancialAccount::class, 'parent_id');
    }

    public function parent() {
		return $this->belongsTo(FinancialAccount::class, 'parent_id', 'id')->withTrashed();
	}

    public function scopeRefundAccounts($query)
    {
        $query->whereIn('account_type', ['Bank', 'Other Current Asset']);
    }

    /**
     * Implememtion of SynchEntityInterface
     *
     * @return void
     */
    public function getCustomerId(){
        return null;
    }

    /**
     * Get Quickbook Log Display Name
     * @return Display Name
     */
    public function getLogDisplayName()
    {
        return  $this->name;
    }

    public function getDeletedAtAttribute($value) {
        if($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    public function scopevendorBillAccounts($query)
    {
        $query->whereIn('account_type', ['Expense']);
    }

    public static function boot()
    {
        parent::boot();

      static::deleting(function($financialAccount) {
         foreach ($financialAccount->subAccounts  as $financialAccount) {
            $financialAccount->delete();
         }
      });
    }
}