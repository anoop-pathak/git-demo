<?php


namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        // \App\Http\Middleware\TrimStrings::class,
        // \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        \Barryvdh\Cors\HandleCors::class,
        \App\Http\Middleware\CheckMobileVersionCompatibility::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth_web' => \App\Http\Middleware\AuthenticateForWeb::class,
        'auth' => \App\Http\Middleware\Authenticate::class,
        'allow_cross_origin' => \App\Http\Middleware\AllowCrossOrigin::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasic\Auth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        'set_user_in_auth' => \App\Http\Middleware\SetUser::class,
        'check_permissions' => \App\Http\Middleware\CheckPermissions::class,
        'check_company_status' => \App\Http\Middleware\CheckCompanyStatus::class,
        'company_scope.apply' => \App\Http\Middleware\CompanyScopeApply::class,
        'company_scope.ensure' => \App\Http\Middleware\CompanyScopeEnsure::class,
        'company_scope.setHeaders' => \App\Http\Middleware\CompanyScopeSetHeaders::class,
        'company_scope.validateCustomerJob' => \App\Http\Middleware\CompanyScopeValidateCustomerJob::class,
        'check_open_api_access' => \App\Http\OpenAPI\Middleware\CheckOpenAPIAcess::class,
        'check_records_limit' => \App\Http\OpenAPI\Middleware\CheckRecordsLimit::class,
        'manage_full_job_workflow' => \App\Http\Middleware\ManageFullJobWorkflow::class,
        'set_date_duration_filter' => \App\Http\Middleware\SetDateDurationFilter::class,
        'check_job_token' => \App\Http\CustomerWebPage\Middleware\CheckJobToken::class,
        'validate_resource_permission' => \App\Http\CustomerWebPage\Middleware\ValidateResourcePermission::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\AuthenticateForWeb::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
