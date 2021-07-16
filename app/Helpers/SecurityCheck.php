<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Settings;

class SecurityCheck
{
    public static $error;

    /**
     * Password varification for record delete.
     * @return bool
     */
    public static function verifyPassword()
    {
        $input = Request::onlyLegacy('password');
        $validator = Validator::make($input, ['password' => 'required']);
        if ($validator->fails()) {
            static::$error = ApiResponse::validation($validator);
            return false;
        }

        if (!\Hash::check($input['password'], \Auth::user()->password)) {
            static::$error = ApiResponse::errorGeneral(\Lang::get('response.error.incorrect_password'));
            return false;
        }

        return true;
    }

    /**
     * Check Max Customer and Job Edit limit per day for a normal user..
     * @return bool
     */
    public static function maxCustomerJobEditLimit()
    {
        $user = \Auth::user();

        // if user is admin then allow..
        if ($user->isAuthority()) {
            return true;
        }
        $today = Carbon::today()->toDateString();
        $customerIds = ActivityLog::where('created_by', $user->id)
            ->where('created_at', 'Like', '%' . $today . '%')
            ->where('event', '=', ActivityLog::CUSTOMER_UPDATED)
            ->pluck('customer_id')->toArray();
        $jobsIds = ActivityLog::where('created_by', $user->id)
            ->where('created_at', 'Like', '%' . $today . '%')
            ->where('event', '=', ActivityLog::JOB_UPDATED)
            ->pluck('job_id')->toArray();
        $count = count(array_merge(array_unique($customerIds), array_unique($jobsIds)));

        if ($count >= \config('jp.max_customer_job_edit_limit')) {
            static::$error = ApiResponse::errorForbidden(\Lang::get('response.error.max_edit_limit_exceeded'));
            return false;
        }
        return true;
    }

    /**
     * Prevent owner profile from other groups.
     * @return bool
     */
    public static function AccessOwner(User $requestedUser)
    {
        $loginUser = \Auth::user();
        if ($requestedUser->isOwner() && (!($loginUser->isOwner()) && !$loginUser->isSuperadmin())) {
            static::$error = ApiResponse::errorForbidden();
            return false;
        }
        return true;
    }

    /**
     * Check Restricted Workflow
     * @param User $user | User Object
     */
    public static function RestrictedWorkflow($user = null)
    {
        if(!\Auth::check() && !$user) return false;
        // restrict sub contractor to access own jobs/customers
        if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
            return true;
        }

        // set restricted access to false for open api user
        if(!$user && Auth::user()->isOpenAPIUser()) {
            return false;
        }
        
        if (is_null($user) || empty($user)) {
            $user = \Auth::user();
            $restricted = Settings::get('RESTRICTED_WORKFLOW');
        } else {
            $restricted = Settings::forUser($user->id, $user->company_id)->get('RESTRICTED_WORKFLOW');
        }

        if ($user->isAuthority()
            || $user->isSuperAdmin()
            || $user->isSubContractor()
            || ($restricted == "false")
            || ($restricted == "0")
            || ($restricted == false)
        ) {
            return false;
        }
        return true;
    }

    /**
     * check job is applicable to make payments, invoices and change orders
     * @param  Job | $job [job instance]
     * @return boolean
     */
    public static function isJobAwarded($job)
    {
        /* check job cross the awarded stage */
        if ($job->getSoldOutDate()) {
            return true;
        }

        /* check job have payments */
        if ($job->payments()->count()) {
            return true;
        }

        /* check job have invoices */
        if ($job->invoices()->count()) {
            return true;
        }

        return false;
    }

    public static function restrictedForMultiJobs(Job $job)
    {
        if ($job->isMultiJob()) {
            throw new \Exception("Not allowed for Multi Jobs", 412);
        }
    }

    public static function restrictedForProjects(Job $job)
    {
        if ($job->isProject()) {
            throw new Exception("Not allowed for Projects", 412);
        }
    }

    public static function checkApiMobileVersionCapatibility($appVersion)
    {
        if (!$appVersion) {
            return true;
        }

        $route = Route::currentRouteAction();
        $disableRoutes = config('mobile-versions-compatibility');
        foreach ($disableRoutes as $key => $api) {
            if (version_compare($key, $appVersion, '>') && in_array($route, $api)) {
                return false;
            }
        }

        return true;
    }

    public static function hasPermission($permission)
    {
        $user = User::find(\Auth::id());
        $allPermissions = $user->listPermissions();

        return in_array($permission, $allPermissions);
    }
}
