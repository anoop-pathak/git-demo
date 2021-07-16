<?php

namespace App\Http\OpenAPI\Middleware;

use App\Models\ApiResponse;
use Closure;

class CheckRecordsLimit
{
    /**
     * Check the records limit and then update it
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try 
        {
            if($request->has('limit') && $request->input('limit') > 100) {
                $request->merge(['limit' => 100]);
            }

            return $next($request);
        } catch (\Exception $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        }
    }
}