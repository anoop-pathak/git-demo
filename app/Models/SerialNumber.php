<?php

namespace App\Models;

class SerialNumber extends BaseModel
{
    const ESTIMATE = 'estimate';
    const PROPOSAL = 'proposal';
    const MATERIAL_LIST = 'material_list'; //worksheet
    const WORK_ORDER = 'work_order'; //worksheet
    const JOB_ALT_ID = 'job_alt_id';
	const JOB_LEAD_NUMBER = 'job_lead_number';

    protected $fillable = ['company_id', 'type', 'start_from', 'last_record_id', 'is_active', 'prefix', 'current_allocated_number'];

    // protected $appends = ['serial_number'];

    protected $hidden = ['id', 'company_id', 'last_record_id', 'is_active', 'created_at', 'updated_at'];

    protected $rules = [
        'type' => 'required|in:estimate,proposal,material_list,work_order,job_alt_id,job_lead_number'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    // protected function getSerialNumberAttribute()
    // {
    // 	$count = 0;

    // 	if($this->type == 'proposal') {
    // 		$count = Proposal::where('id', '>', $this->last_record_id)->whereCompanyId($this->company_id)->count();
    // 	}elseif($this->type == 'estimate') {
    // 		$count = Estimation::where('id', '>', $this->last_record_id)->whereCompanyId($this->company_id)->count();
    // 	}elseif ($this->type == 'material_list') {
    // 		$count = MaterialList::where('id', '>', $this->last_record_id)->whereCompanyId($this->company_id)->count();
    // 		// Log::info($count .' '.$this->last_record_id);
    // 	}
    // 	return $this->start_from + $count + 1;
    // }

    public function scopeActive($query)
    {
        $query->whereIsActive(true);
    }
}
