<?php
namespace App\Models;

class JobFinancialNote extends BaseModel
{
	protected $fillable = ['id','job_id','note','company_id','created_by','updated_by'];

	protected $rules = [
		'note' => 'max:2000',
	];

	protected function getRules()
	{
		return $this->rules;
	}

	/***** Relationships *****/

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function updatedBy()
	{
		return $this->belongsTo(User::class, 'updated_by');
	}
}