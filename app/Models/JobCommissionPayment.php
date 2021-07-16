<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;

class JobCommissionPayment extends BaseModel
{
	use SortableTrait;

	protected $fillable = ['company_id', 'job_id', 'commission_id', 'paid_by', 'paid_on', 'amount', 'canceled_at'];
	protected $rules = [
		'commission_id'	=> 'required',
		'amount'	=> 'required|ten_digit_allow',
		'paid_on'		=> 'date|date_format:Y-m-d',
	];
	protected function getRules()
	{
		return $this->rules;
	}
	public function jobCommission()
	{
		return $this->belongsTo(JobCommission::class, 'commission_id', 'id');
	}
	public function job()
	{
		return $this->belongsTo(Job::class);
	}

	public static function boot()
	{
		parent::boot();
		static::saved(function($model){
			//  update financial calculations..
            JobFinancialCalculation::updateFinancials($model->job->id);

            if($model->job->isProject() || $model->job->isMultiJob()) {
                //update parent job financial
                JobFinancialCalculation::calculateSumForMultiJob($model->job);
            }
			$model->job->touch();
		});
		static::deleted(function($model){
			//  update financial calculations..
			JobFinancialCalculation::updateFinancials($model->job->id);

			 if($model->job->isProject() || $model->job->isMultiJob()) {
				//update parent job financial
				JobFinancialCalculation::calculateSumForMultiJob($model->job);
			 };

			$model->job->touch();
		});
	}
}