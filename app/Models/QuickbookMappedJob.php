<?php
namespace App\Models;

class QuickbookMappedJob extends BaseModel
{
	protected $table = 'quickbook_mapped_jobs';

	protected $fillable = [
		'company_id', 'created_by', 'qb_customer_id', 'customer_id', 'batch_id', 'qb_job_id', 'job_id', 'action_required_job'
	];

	/***** Protected Section *****/

	protected function getRules()
	{
		$rules = [
			'customer_id' => 'required',
			'qb_customer_id' => 'required',
			'details' => 'required|array'
		];
		return $rules;
	}

	public function job() {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function qbJob() {
		return $this->belongsTo(QBOCustomer::class, 'qb_job_id')->where('qbo_customers.company_id', getScopeId());
	}
}