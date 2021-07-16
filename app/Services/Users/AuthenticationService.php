<?php

namespace App\Services\Users;

use App\Exceptions\InactiveAccountException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InvalidClientSecretException;
use App\Exceptions\LoginNotAllowedException;
use App\Exceptions\SuspendedAccountException;
use App\Exceptions\TerminatedAccountException;
use App\Models\ConnectedSite;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use App\Exceptions\AccessTokenNotFoundException;
use Illuminate\Support\Facades\Config;

class AuthenticationService
{
    /**
     * verify user
     * @param  [array] $token [description]
     * @param  [user] $user  [description]
     * @param  array $input [description]
     * @return [array]        [token]
     */
    public function verify($token, $user, $input = [], $existingTokenIds = [])
    {
        $token = $this->setExpireTime($user,$token);
        $this->saveDomain($user, $input);
        $this->checkDevice($user, $token, $input);
        $this->checkStatus($user, $token);//check whether user active or not..

        if($user->multiple_account) return $token;

        $this->checkPluginClient($input);
        $this->checkPluginUser($user, $existingTokenIds, $input);

        return $token;
    }

    /**
     * check client and secret of plugin.
     * @param  [object] $user  [current user]
     * @param  [array] $token [oauth token]
     * @param  [array] $input [description]
     * @return [exception]        [throw exception]
     */
    public function checkPluginClient($input)
    {
        $clientId = ine($input, 'client_id') ? $input['client_id'] : null;
        $clientSecret = ine($input, 'client_secret') ? $input['client_secret'] : null;
        $redirectUrl = ine($input, 'redirect_uri') ? $input['redirect_uri'] : null;
        if (ine($input, 'redirect_uri')
            && ($clientId != config('jp.wordpress_client_id')
                || $clientSecret != config('jp.wordpress_client_secret'))
        ) {
            throw new InvalidClientSecretException("Invalid client id or client secret.");
        }
    }

    /**
     * logout user
     * @param  [type] $input [description]
     * @return [type]        [description]
     */
    public function destroy($input)
    {
        // remove access token from cookie
        Request::user()->token()->delete();
        $this->deleteConnectedSite($input);
        // TODO - need to verify on qa
        $domain = config('cloud-front.COOKIES_DOMAIN');
        header("Set-Cookie: access_token=; path=/; domain=$domain; secure; httpOnly", false);
    }

    /**
     * logout from all devices
     * @param  $userId
     * @return
     */
    public function logoutFromAllDevices($userId)
    {
        $users = User::where('email', function($query) use($userId) {
            $query->select('email')
                ->from('users')
                ->where('id', $userId);
        })->get();

        foreach($users as $user) {
            $user->tokens()->delete();
        }

        DB::table('user_devices')
            ->whereIn('user_id', $users->pluck('id')->toArray())
            ->delete();

        return true;
    }

    /**
     * logout from other devices except current device
     * @param  $userId
     * @return
     */
    public function logoutFromOtherDevices($user)
    {
        if(!$user || (!$user->token())) return false;

        $users = User::where('email', function($query) use($user) {
            $query->select('email')
                ->from('users')
                ->where('id', $user->id);
        })->get();

        foreach($users as $multiUser) {
            if($user->id == $multiUser->id) continue;
            $user->tokens()->delete();
        }

        return $user->tokens()
            ->where('id', '<>', $user->token()->id)
            ->delete();
    }

    /**
     * switch user account by chanaging user id of an access token
     * @param  User     | $user  | object of a user record
     * @param  string   | $token | access token of a user
     * @return boolean
     */
    public function switchUserAccount($user, $token)
    {
        $this->checkMultiUserStatus($user);
        $token = trim(substr(trim($token), strpos(trim($token), ' ')));
        $ouathToken = DB::table('oauth_access_tokens')
            ->where('id', '=', "{$token}")
            ->where('revoked', '=', false)
            ->first();
        if(!$ouathToken) {
            throw new AccessTokenNotFoundException("Your session has expired please login again.");
        }

        return DB::table('oauth_access_tokens')
            ->where('id', '=', "{$token}")
            ->update(['user_id' => $user->id]);
    }


    /************************** Private Section ********************************/

    /**
     * Logout the user from other browsers
     * @param  User $user | User Object
     * @param  Json $token | Authorization token object
     * @return void
     */
    private function checkForOneWebLogin(User $user, $token)
    {
        $clientId = Request::get('client_id');
        $webClientId = config('jp.web_client_id');
        if ($clientId != $webClientId) {
            return;
        }
        $currentSessionId = $this->getSessionIdFromAccessToken($token);

        // logout from other browsers..
        DB::table('oauth_sessions')->where('id', '!=', $currentSessionId)
            ->where('owner_id', $user->id)
            ->where('client_id', $webClientId)
            ->delete();
    }

    /**
     * Get Session Id from Access Token
     * @param  Json $token | Authorization token object
     * @return Integer | Session id
     */
    public function getSessionIdFromAccessToken($token)
    {
        $currentSession = DB::table('oauth_access_tokens')
            ->where('id', $token['access_token'])
            ->select('session_id')
            ->first();
        if (!$currentSession) {
            return null;
        }
        return $currentSession->session_id;
    }

    /**
     * Logout the user form mobile device delete device info.
     * @param  Integer $deviceId | Device Id
     * @return void
     */
    private function deviceLogout($deviceId = null)
    {
        if (!$deviceId) {
            return;
        }
        $device = UserDevice::find($deviceId);

        if (!$device) {
            return;
        }
        $device->delete();
    }

    /**
     * Destroy Session
     * @param  Json $token | Authorization token object
     * @return bool
     */
    private function destroySession($user, $existingTokenIds)
    {
        if(!$user) return false;

        return $user->tokens()
            ->whereNotIn('id', $existingTokenIds)
            ->delete();
    }

    /**
     * Check User and Company Status befor login
     * @param  User $user | User Object
     * @return void
     */
    private function checkStatus(User $user, $existingTokenIds)
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $company = $user->company;

        if (!$company) {
            $this->destroySession($user, $existingTokenIds);
            throw new InActiveUserException(Lang::get('response.error.invalid_email_password'));
        }

        $subscription = $company->subscription;
        if ($subscription->status == Subscription::INACTIVE) {
            $this->destroySession($user, $existingTokenIds);
            throw new InactiveAccountException(Lang::get('response.error.company_not_activated'));
        }

        if (!$user->active) {
            $this->destroySession($user, $existingTokenIds);
            throw new InActiveUserException(Lang::get('response.error.invalid_email_password'));
        }

        if ($subscription->status == Subscription::TERMINATED) {
            $this->destroySession($user, $existingTokenIds);
            throw new TerminatedAccountException(Lang::get('response.error.invalid_email_password'));
        }

        if ($user->isAuthority()) {
            if ($subscription->status == Subscription::MANUALLY_SUSPENDED) {
                $this->destroySession($user, $existingTokenIds);
                throw new SuspendedAccountException(Lang::get('response.error.manual_suspended_company_admin'));
            } elseif ($subscription->status == Subscription::UNSUBSCRIBED) {
                $this->destroySession($user, $existingTokenIds);
                throw new SuspendedAccountException(Lang::get('response.error.unsubscribed_company_admin'));
            }
        } else {
            if ($subscription->status == Subscription::MANUALLY_SUSPENDED) {
                $this->destroySession($user, $existingTokenIds);
                throw new SuspendedAccountException(Lang::get('response.error.manual_suspended_company_user'));
            } elseif ($subscription->status == Subscription::SUSPENDED) {
                $this->destroySession($user, $existingTokenIds);
                throw new SuspendedAccountException(Lang::get('response.error.suspended_company_user'));
            } elseif ($subscription->status == Subscription::UNSUBSCRIBED) {
                $this->destroySession($user, $existingTokenIds);
                throw new SuspendedAccountException(Lang::get('response.error.unsubscribed_company_user'));
            }
        }
    }

    private function checkDevice($user, $existingTokenIds, $input)
    {
        $clientId = ine($input, 'client_id') ? Request::get('client_id') : null;
        $mobileClientId = config('jp.mobile_client_id');

        if($clientId == $mobileClientId) {
            Config::set('is_mobile', true);
        }

        if (config('is_mobile') && ($user->isSuperAdmin())  ) {
            $this->destroySession($user, $existingTokenIds);
            throw new LoginNotAllowedException(Lang::get('response.error.login_not_allowed'));
        }
        // $this->checkForOneWebLogin($token,$user); // Logout the user from other browsers
    }


    private function setExpireTime($user, $token)
    {
        if (($user->company_id != config('jp.demo_subscriber_id')) || (!$user->isStandardUser())) {
            return $token;
        }
        $expireTime = Carbon::now()->addSeconds(config('jp.demo_expire_time'))->timestamp;
        DB::table('oauth_access_tokens')->where('id', $token['access_token'])->update(['expire_time' => $expireTime]);
        $token['expires_in'] = config('jp.demo_expire_time');
        return $token;
    }

    /**
     * save website domain save
     * @param  [object] $user  [description]
     * @param  [array] $input [description]
     * @return [type]        [description]
     */
    private function saveDomain($user, $input)
    {
        if (!ine($input, 'domain')) {
            return false;
        }
        $connectedSite = ConnectedSite::firstOrNew([
            'company_id' => $user->company_id,
            'domain' => $input['domain']
        ]);
        $connectedSite->user_id = $user->id;
        $connectedSite->save();
    }

    /**
     * Delete connected site
     * @param  [array] $input [description]
     * @return [boolean]      [description]
     */
    private function deleteConnectedSite($input)
    {
        if (!ine($input, 'domain')) {
            return false;
        }
        return ConnectedSite::whereDomain($input['domain'])
            ->whereCompanyId(Auth::user()->company_id)
            ->whereUserId(Auth::id())
            ->delete();
    }

    private function checkPluginUser($user, $existingTokenIds, $input)
    {
        $clientId = ine($input, 'client_id') ? $input['client_id'] : null;
        $clientSecret = ine($input, 'client_secret') ? $input['client_secret'] : null;
        $redirectUrl = ine($input, 'redirect_uri') ? $input['redirect_uri'] : null;

        if (ine($input, 'redirect_uri')
            && ($clientId === config('jp.wordpress_client_id')
                || $clientSecret === config('jp.wordpress_client_secret'))
            && ($user->isSuperAdmin())
        ) {
            $this->destroySession($user, $existingTokenIds);

            throw new LoginNotAllowedException(Lang::get('response.error.login_not_allowed'));
        }
    }

    /**
     * check current status of a user with multiple account on company switching
     * @param  User     | $user     | User with multiple company accounts
     * @return void
     */
    private function checkMultiUserStatus($user)
    {
        if($user->isSuperAdmin()) return;
        $company = $user->company;
        if(!$company) {
            throw new InActiveUserException(trans('response.error.not_found', ['attribute' => 'Company']));
        }

        $subscription = $company->subscription;
        if($subscription->status == Subscription::INACTIVE) {
            throw new InactiveAccountException(trans('response.error.company_not_activated'));
        }

        if(!$user->active) {
            throw new InActiveUserException(trans('response.error.company_not_activated'));
        }
        if($subscription->status == Subscription::TERMINATED) {
            throw new TerminatedAccountException(trans('response.error.terminated_company'));
        }
        if($user->isAuthority()) {
            if($subscription->status == Subscription::MANUALLY_SUSPENDED) {
                throw new SuspendedAccountException(trans('response.error.manual_suspended_company_admin'));
            } elseif($subscription->status == Subscription::UNSUBSCRIBED) {
                throw new SuspendedAccountException(trans('response.error.unsubscribed_company_admin'));
            }
        }else {
            if($subscription->status == Subscription::MANUALLY_SUSPENDED) {
                throw new SuspendedAccountException(trans('response.error.manual_suspended_company_user'));
            } elseif($subscription->status == Subscription::SUSPENDED) {
                throw new SuspendedAccountException(trans('response.error.suspended_company_user'));
            } elseif($subscription->status == Subscription::UNSUBSCRIBED) {
                throw new SuspendedAccountException(trans('response.error.unsubscribed_company_user'));
            }
        }
    }
}
