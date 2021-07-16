<?php

namespace App\Models;
use JobQueue;

use Illuminate\Database\Eloquent\SoftDeletes;

class CompanySupplier extends BaseModel
{
    use SoftDeletes;

    protected $table = 'company_supplier';

    protected $fillable = ['email', 'phone', 'branch_id', 'branch_address', 'manager_name', 'ship_to_address', 'branch_name', 'srs_customer_id', 'branch', 'srs_account_number', 'ship_to_sequence_number'];

    public $hidden = ['deleted_at', 'created_at', 'updated_at'];

    /********** Relations **********/
    public function suppliers()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierBranches()
    {
        return $this->hasMany(SupplierBranch::class)->where('supplier_branches.company_id', getScopeId());
    }
    public function srsShipToAddresses()
    {
        return $this->hasMany(SrsShipToAddress::class)->where('srs_ship_to_addresses.company_id', getScopeId());
    }

    public function queueStatus()
	{
		return $this->hasOne(QueueStatus::class, 'entity_id', 'id')
			->whereIn('queue_statuses.action', [JobQueue::CONNECT_SRS, JobQueue::SRS_SYNC_DETAILS])
			->latest('id');
	}

    /********** Relations End **********/

    public function setSrsBranchDetailAttribute($value)
    {
        $this->attributes['srs_branch_detail'] = json_encode($value);
    }

    public function getSrsBranchDetailAttribute($value)
    {
        return json_decode($value, true);
    }
}
