<?php

namespace App\Http\Middleware;

use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\User;
use App\Models\UserDevice;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use DataMasking;

class SetUser
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
        if(in_array(\Route::currentRouteAction(), [
            '\App\Http\OpenAPI\Controllers\SessionController@getToken',
            '\App\Http\OpenAPI\Controllers\SessionController@getTokenList',
            '\App\Http\OpenAPI\Controllers\SessionController@revokeToken',

        ])) {
            $requestData = $request->all();
            if(ine($requestData, 'user_id')) {
                $user = User::find($requestData['user_id']);
                if(!$user) {

                     return response()->json(
                        [
                            'errors' => [
                                'status' => 401,
                                'message' => 'Unauthenticated',
                            ]
                        ], 401
                    );
                }

                Auth::guard('web')->login($user);
                if($user) {
                    setScopeId($user->company_id);
                }
            }
        }

        // Disable Proposals for some compnies
        Config::set('disable_proposal_for_company', []);

        // set email/phone masking
        if(Auth::user()->isSubContractorPrime() && Auth::user()->dataMaskingEnabled()) {
            DataMasking::enable();
        }

        // is mobile request
        if (\Auth::user()->token() && (\Auth::user()->token()->client_id == config('jp.mobile_client_id'))) {
            Config::set('is_mobile', true);
            // Config::set('srs_disabled_for_mobile', true);

            //check mobile app version compatibility
            if (!($appVersion = $request->header('app-version'))) {
                $sessionId = \Auth::user()->token()->id;
                $device = UserDevice::whereSessionId($sessionId)
                    ->select('app_version')
                    ->first();

                if (!$device) {
                    $appVersion = null;
                    Log::warning('Unkown Device. Session:' . $sessionId);
                } else {
                    $appVersion = $device->app_version;
                }
            }

            if (!SecurityCheck::checkApiMobileVersionCapatibility($appVersion)) {
                return ApiResponse::errorGeneral(trans('response.error.update_mobile_app'));
            }
        }

        return $next($request);
    }
}
