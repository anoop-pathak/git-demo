<?php

namespace App\Http\CustomerWebPage\Middleware;

use App\Models\ApiResponse;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DB;

class CheckJobToken
{
    /**
     * Check the job exist with this job token
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try 
        {
            $jobToken = getJobToken($request);

            $job = DB::table('jobs')->where('share_token', $jobToken)->first();
          
            if ($job) {
                return $next($request);
            } 

           return response()->json(
                        [
                            'errors' => [
                                'status' => 401,
                                'message' => 'Unauthenticated',
                            ]
                        ], 401
                    );

        } catch (\Exception $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        }
    }
}
