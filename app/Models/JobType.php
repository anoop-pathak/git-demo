<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobType extends BaseModel
{

    use SortableTrait, SoftDeletes;


    protected $fillable = ['name', 'company_id', 'type', 'trade_id', 'color', 'qb_id', 'qb_account_id'];

    const JOB_TYPES = 1;

    const WORK_TYPES = 2;

    const INSURANCE_CLAIM = 'Insurance Claim';

    protected $hidden = ['created_at', 'updated_at', 'company_id', 'trade'];

    protected $rules = [
        'name' => 'required',
        'type' => 'required|integer|between:1,2',
        'trade_id' => 'required_if:type,2',
    ];

    protected $saveOnQuickbookRules = [
        'work_type_id' => 'required',
        'account_id' => 'required|integer',
    ];

    protected $appends = ['insurance_claim'];

    protected function getRules($input, $id = null)
    {
        if (ine($input, 'trade_id')) {
            $this->rules = [
                'name' => 'required|unique:job_types,name,' . $id . ',id,trade_id,' . $input['trade_id'] . ',type,2,company_id,' . config('company_scope_id').',deleted_at,NULL',
                'qb_account_id' => 'required_with:sync_on_qb'
            ];
        }

        return $this->rules;
    }

    protected function getUpdateRules($input, $id)
    {
        $rules = $this->getRules($input, $id);
        unset($rules['type']);

        return $rules;
    }

    protected function getSaveOnQuickBookRules($input)
    {
        return $this->saveOnQuickbookRules;
    }

    // public function trade() {
    // 	return $this->belongsTo(Trade::class);
    // }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_work_types', 'job_type_id', 'job_id');
    }

    public function getInsuranceClaimAttribute()
    {
        if ($this->type == self::JOB_TYPES
            && $this->name == self::INSURANCE_CLAIM
            && is_null($this->company_id)
        ) {
            // set $insuranceClaim = true
            return true;
        }

        return false;
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
