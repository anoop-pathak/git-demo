<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\JobAwardedStage;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class CompanyScopeApply
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $company_scope = null;

            if ($user->isSuperAdmin()) {
                $company_scope = $request->header('Company-Scope');

                // Tempory fix to allow both Company-Scope and company-scope
                if (empty($company_scope)) {
                    $company_scope = $request->header('company-scope');
                }

                if($request->input('company_scope')) {
                    $company_scope = $request->input('company_scope');
                }
            } else {
                if ($user->company_id) {
                    $company_scope = $user->company_id;
                }
            }

            if (!is_null($company_scope)) {
                $company = Company::find($company_scope);
                Config::set('company_scope_id', $company_scope);
                $context = App::make(\App\Services\Contexts\Context::class);
                $context->set($company);

                $awardedStage = JobAwardedStage::getJobAwardedStage();
                Config::set('awarded_stage', $awardedStage);
                Config::set('company_country_currency_symbol', $company->country->currency_symbol);
                Config::set('company_country_code', $company->country->code);
			  	Config::set('is_qbd_connected', (bool)$company->quickbookDesktop()->exists());

                //  	$workflow = $this->workRepo->getActiveWorkflow($context->id());

                // if($workflow){
                // 	$mappedStages = [];
                // 	foreach($workflow->stages as $stage){
                // 		$mappedStages[$stage->code] = array('name' => $stage->name, 'color' => $stage->color, 'code' => $stage->code, 'resource_id' => $stage->resource_id);
                // 	}
                // 	$workflow->mappedStages = $mappedStages;
                // 	Config::set('workflow', $workflow);
                // }
            }
        }

        return $next($request);
    }
}
