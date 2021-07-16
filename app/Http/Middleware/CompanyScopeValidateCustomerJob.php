<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Request;

class CompanyScopeValidateCustomerJob
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
        if (\Auth::check()) {
            $input = Request::onlyLegacy('customer_id', 'job_id');
            if (ine($input, 'job_id') && !is_array($input['job_id'])) {
                App::make(\App\Repositories\JobRepository::class)->findById($input['job_id']);
            }

            if (ine($input, 'customer_id') && !is_array($input['customer_id'])) {
                $repo = App::make(\App\Repositories\CustomerRepository::class);
                $repo->getByIdWithTrashed($input['customer_id']);
            }
        }

        return $next($request);
    }
}
