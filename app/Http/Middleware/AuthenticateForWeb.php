<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Crypt;

class AuthenticateForWeb
{
    // *
    //  * This middleware is for non-API routes.
    //  * Handle an incoming request.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  \Closure  $next
    //  * @return mixed

    // public function handle($request, Closure $next)
    // {
    //     return $next($request);
    // }

   /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->cookie('access_token') && !$request->header('Authorization')) {
            $token = $request->cookie('access_token');
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        // if ($request->has('access_token')) {
        //     $request->headers->set('Authorization', 'Bearer ' . $request->get('access_token'));
        // }

        return $next($request);
    }
}
