<?php
namespace App\Models;
use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Vendor extends BaseModel
{
    use SortableTrait;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'first_name', 'last_name', 'display_name', 'address_id', 'created_by', 'updated_by', 'company_id', 'origin',
        'type_id', 'sub_contractor_id', 'ref_id',
        'qb_desktop_id', 'qb_desktop_sequence_number', 'qb_desktop_delete'
    ];

    protected $rules = [
        'first_name'              => 'max:100',
        'last_name'               => 'max:100',
        'address.address'         => 'max:2000',
        'address.address_line_1'  => 'max:2000',
        'address.city'            => 'max:255',
        'address.zip'             => 'max:30',
    ];

    protected $searchable = [];

    protected function getUpdateRules($id)
    {
        $updateRules = $this->rules;
        $companyId = getScopeId();
        $updateRules['display_name'] = "required|max:500|unique:vendors,display_name,{$id},id,company_id,{$companyId},deleted_at,NULL";

        return $updateRules;

    }

    protected function getCreateRules()
    {
        $rules = $this->rules;

        $rules['display_name'] = 'required|max:500|unique:vendors,display_name,null,id,company_id,'. getScopeId().',deleted_at,NULL';
        $rules['type_id'] = 'required|exists:vendor_types,id';

        return $rules;
    }

    /**
     * Get Quickbook Log Display Name
     * @return Display Name
     */
    public function getLogDisplayName()
    {
        return  $this->display_name;
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getDeletedAtAttribute($value) {
    	if (!$value) return null;
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setFirstNameAttribute($value){
        return $this->attributes['first_name'] = ucfirst(trim($value));
    }

    public function setLastNameAttribute($value){
        return $this->attributes['last_name'] = ucfirst(trim($value));
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function vendorbill()
    {
        return $this->hasMany(VendorBill::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function setUpdatedAtAttribute($value)
    {
        if(empty($this->updated_by)) {
            $this->attributes['updated_by'] = null;
        }
    }

    public function setDisplayNameAttribute($value)
    {
        return $this->attributes['display_name'] = $value;
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function type()
    {
        return $this->belongsTo(VendorTypes::class, 'type_id');
    }

    public function scopeDisplayNameSearch($query, $keyword)
    {
        $query->where('display_name', 'like', $keyword.'%');
    }
}