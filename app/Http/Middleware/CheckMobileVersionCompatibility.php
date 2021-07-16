<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;

class CheckMobileVersionCompatibility
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
        if(!SecurityCheck::checkApiMobileVersionCapatibility($request->header('app-version'))){
            return ApiResponse::errorGeneral(trans('response.error.update_mobile_app'));
        }

        return $next($request);
    }
}
