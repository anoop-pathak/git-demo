<?php

namespace App\Http\Middleware;

use Closure;
use CloudFront;
use Illuminate\Support\Facades\Auth;

class CompanyScopeSetHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (\Auth::check()) {
            // set headers for cloud front cookies..
            CloudFront::setCookies();
        }

        return $next($request);
    }
}
