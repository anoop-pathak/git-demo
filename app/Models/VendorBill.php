<?php
namespace App\Models;

use App\Models\Vendor;
use App\Models\VendorBillLine;
use App\Models\BaseModel;
use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class VendorBill extends BaseModel
{
	use SortableTrait;

	protected $dates = ['deleted_at'];

	protected $fillable = ['vendor_id', 'company_id', 'job_id', 'customer_id', 'bill_date',
		'due_date', 'file_path', 'bill_number', 'address', 'note', 'tax_amount', 'total_amount',
		'created_by', 'updated_by', 'deleted_by', 'origin','qb_desktop_txn_id', 'qb_desktop_sequence_number','qb_desktop_delete'
	];

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

	public function getTotalAmountAttribute($value)
	{
		return numberFormat($value);
	}

	public function setUpdatedAtAttribute($value)
	{
		if(empty($this->updated_by)) {
			$this->attributes['updated_by'] = null;
		}
	}

	public function setDueDateAttribute($value)
	{
		if(!isValidDate($value)) {
			$value = null;
		}

		return $this->attributes['due_date'] = $value;
	}

	public function deletedBy()
	{
		return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
	}

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function vendor()
	{
		return $this->belongsTo(Vendor::class)->withTrashed();
	}

	public function lines()
	{
		return $this->hasMany(VendorBillLine::class, 'vendor_bill_id');
	}

	public function job()
	{
		return $this->belongsTo(Job::class, 'job_id')->withTrashed();
	}

	public function customer()
	{
		return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
	}

	public function updatedBy()
	{
		return $this->belongsTo(User::class, 'updated_by');
	}

	public function address()
    {
        return $this->belongsTo(Address::class, 'bill_address_id');
    }

	public function attachments()
	{
		return $this->belongsTomany(Resource::class, 'vendor_bill_attachments', 'vendor_bill_id', 'value');
	}

	public function getQBDId()
	{
		return $this->qb_desktop_txn_id;
	}

	public function getSignedUrlAttribute()
    {
        if(!$path = $this->file_path) return;

        return \FlySystem::getAwss3SignedUrl($path);
    }
}