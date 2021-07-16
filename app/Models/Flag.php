<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Support\Facades\App;

class Flag extends BaseModel
{

    use SortableTrait;

    const BAD_LEAD = 25;
    const PROSPECT = 4212;

    protected $fillable = ['title', 'for', 'company_id'];

    protected $rule = [
        'flag_for' => 'required|in:customer,job'
    ];

    protected $saveRule = [
        'title'    => 'required',
        'flag_for' => 'required|in:customer,job',
		'color'	   => 'color_code'
    ];

    protected $applyRule = [
        'flag_for' => 'required|in:customer,job',
        'id' => 'required|integer',
        'status' => 'required|Boolean',
        'flag_id' => 'required|integer|exists:flags,id',
    ];

    protected $applyMultipelFlagRule = [
        'flag_for' => 'required|in:customer,job',
        'id' => 'required|integer',
        'flag_ids' => 'exists:flags,id|array',
    ];

    protected function getRule()
    {
        return $this->rule;
    }

    protected function getApplyRule()
    {
        return $this->applyRule;
    }

    protected function getApplyMutipleFlagRule()
    {
        return $this->applyMultipelFlagRule;
    }

    protected function getSaveFlagRule()
    {
        return $this->saveRule;
    }

    /***** Relations Start *****/

    public function customers()
    {
        $scope = App::make(\App\Services\Contexts\Context::class);
        $customer = $this->belongsToMany(Customer::class, 'customer_flags', 'flag_id', 'customer_id');
        if ($scope->has()) {
            $customer->whereCompanyId($scope->id());
        }
        return $customer;
    }

    public function jobs()
    {
        $scope = App::make(\App\Services\Contexts\Context::class);
        $job = $this->belongsToMany(Job::class, 'job_flags', 'flag_id', 'job_id');
        if ($scope->has()) {
            $job->whereCompanyId($scope->id());
        }

        $job->withoutArchived();

        return $job;
    }

    public function companyDeletedFlags()
    {
        return $this->belongsToMany(Company::class, 'comapny_deleted_flags', 'flag_id', 'company_id');
    }

    public function color()
	{
		return $this->hasOne(FlagColor::class)->where('company_id', getScopeId());
    }

    /***** Relations End *****/

    public function scopeCompany($query, $companyId)
    {
        return $query->where(function ($query) use ($companyId) {
            $query->whereNull('company_id')->orWhere('company_id', $companyId);
        });
    }

    public function scopeExcludeDeletedFlags($query)
    {
        $query->whereNotIn('id', function ($query) {
            $query->select('flag_id')
                ->from('comapny_deleted_flags')
                ->where('company_id', getScopeId());
        });
    }

    public function isSystemFlag()
    {
        return is_null($this->company_id);
    }

    public function isReservedFlag()
    {
        return in_array($this->id, [self::BAD_LEAD, self::PROSPECT]);
    }

    public function getColorForPrintAttribute()
	{
		if($this->color) {
			return $this->color->color;
		}
	}
}
