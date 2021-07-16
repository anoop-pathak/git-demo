<?php
namespace App\Models;

class CumulativeInvoiceNote extends BaseModel
{

	protected $fillable = ['company_id', 'customer_id', 'job_id', 'note', 'created_by', 'updated_by'];

	protected $rules = [
        'note'   => 'max:4000'
    ];

    protected function getCreateRules()
    {
        $rules = $this->rules;

        return $rules;
    }

    public function job()
    {
		return $this->belongsTo(Job::class, 'job_id');
	}
}