<?php
namespace App\Models;

class JobPriceRequest extends BaseModel {

	protected $fillable = ['job_id', 'company_id', 'amount', 'custom_tax_id', 'tax_rate', 'taxable', 'requested_by', 'approved_by', 'rejected_by', 'is_active'];

	protected $rules = [
		'job_id'   => 'required',
		'taxable'  => 'boolean',
		'tax_rate' => 'numeric',
		'amount'   => 'required|numeric|regex:/^\d{0,10}(\.\d{1,2})?$/',
	];

	protected $changeStatus = [
		'approve' => 'boolean'
	];

	protected function getRules() {

        return $this->rules;
    }

    protected function getChangeStatusRule() {

    	return $this->changeStatus;
    }

    public function scopeActive($query)
    {
    	$query->where('is_active', true);
    }

    public function approvedBy() {
		return $this->belongsTo(User::class, 'approved_by');
	}

	public function rejectedBy() {
		return $this->belongsTo(User::class, 'rejected_by');
	}

	public function requestedBy() {
		return $this->belongsTo(User::class, 'requested_by');
	}

	public function job()
	{
		return $this->belongsTo(Job::class);
	}

	public function customTax()
    {
        return $this->belongsTo(CustomTax::class);
    }
}