<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * @todo Need to authenticate web requests(non JSON); and handle the Auth Exception in that case.
     */
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    // public function handle($request, Closure $next, ...$guards)
    // {
    //     // if(!$request->expectsJson()) {
    //     //     parent::handle($request, $next, $guards);
    //     // }
    // }
}
