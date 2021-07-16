<?php

namespace App\Http\OpenAPI\Middleware;

use App\Models\ApiResponse;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckOpenAPIAcess
{
    /**
     * Check if the user is open API User
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try 
        {
            if (Auth::check() && Auth::user()->isOpenAPIUser()) {
                return $next($request);
            } 

            throw new \Exception('Unauthorized');

        } catch (\Exception $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        }
    }
}
