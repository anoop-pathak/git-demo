<?php

namespace App\Models;

use Illuminate\Support\Facades\App;

class JobAwardedStage extends BaseModel
{
    protected $fillable = ['company_id', 'stage', 'active',];

    protected $hidden = ['company_id',];

    protected function getJobAwardedStage($companyId = null)
    {
        if (!$companyId) {
            $scope = App::make(\App\Services\Contexts\Context::class);
            $companyId = $scope->id();
        }

        if (!$companyId) {
            return null;
        }

        $awardedStage = self::whereCompanyId($companyId)
            ->whereActive(1)
            ->select('stage')
            ->first();
        if ($awardedStage) {
            return $awardedStage->stage;
        }

        return null;
    }
}
