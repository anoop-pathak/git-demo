<?php

namespace App\Http\Middleware;

use App\Models\ApiResponse;
use Closure;
use Illuminate\Support\Facades\Route;

class CheckPermissions
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
        $requiredPermission = config('permissions-map.' . Route::currentRouteAction());

        $standardUserPermission = [
            'manage_full_job_workflow'
        ];

        if($request->user()->isStandardUser() && (bool)array_intersect((array)$requiredPermission, $standardUserPermission)) {
            return;
        }

        if($requiredPermission && !(bool)array_intersect($requiredPermission, $request->user()->listPermissions())) {
            return ApiResponse::errorForbidden();
        }


        // Restrict the open API user to access non open API resources.
        if( !$request->is('api/v3/*') && $request->user()->isOpenAPIUser()) {
            return ApiResponse::errorForbidden();
        }

        return $next($request);
    }
}
