<?php

namespace App\Http\Middleware;

use App\Models\ApiResponse;
use App\Models\Subscription;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CheckCompanyStatus
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
        try {
            if (\Auth::check() && !\Auth::user()->isSuperAdmin()) {
                $user = \Auth::user();

                $status = null;

                $subscription = Subscription::whereCompanyId($user->company_id)->first();

                if ($subscription && $company = $subscription->company) {
                    $status = $subscription->status;
                }

                $currentRouteAction = Route::currentRouteAction();

                if ($status == Subscription::SUSPENDED) {
                    if (!in_array($currentRouteAction, [
                        'SubscribersController@save_billing',
                        'SessionController@logout'
                    ])) {
                        return ApiResponse::errorForbidden();
                    }
                } elseif (!in_array($status, [Subscription::TRIAL, Subscription::ACTIVE])) {
                    if (Route::currentRouteAction() != 'SessionController@logout') {
                        return ApiResponse::errorForbidden();
                    }
                }
            }
        } catch (\Exception $e) {
            // nothing to do.
        }

        return $next($request);
    }
}
