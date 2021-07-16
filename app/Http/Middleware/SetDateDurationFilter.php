<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Request;
use App\Services\Contexts\Context;

class SetDateDurationFilter
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
        if((!Auth::check()) || (Auth::user()->isSuperAdmin())) {

            return;
        }

        $filters = $request->all();
        $context = app(Context::class);
        $company = $context->get();

        try {
            $timezone = \Settings::get('TIME_ZONE');
            $dateFilters = [];

            // Year till date (YTD)
            if( ine($filters,'duration') && ($filters['duration'] == 'YTD') ) {
                $dateFilters['start_date'] = Carbon::now($timezone)->startOfYear()->toDateString();
                $dateFilters['end_date'] = Carbon::now($timezone)->toDateString();
            }elseif( ine($filters,'duration') && ($filters['duration'] == 'MTD') ) {	// Month till date (MTD)
                $dateFilters['start_date'] = Carbon::now($timezone)->startOfMonth()->toDateString();
                $dateFilters['end_date'] = Carbon::now($timezone)->toDateString();
            }elseif( ine($filters,'duration') && ($filters['duration'] == 'WTD') ) {	// Week till date (WTD)
                $dateFilters['start_date'] = Carbon::now($timezone)->startOfWeek()->toDateString();
                $dateFilters['end_date'] = Carbon::now($timezone)->toDateString();
            }elseif( ine($filters, 'duration')
                && ($filters['duration'] == 'since_inception')
                && $company) {	//Since Inception
                $dateFilters['start_date'] = null;
                $dateFilters['end_date'] = null;
            }elseif( ine($filters,'duration') && ($filters['duration'] == 'last_month') ) {		// last month
                $dateFilters['start_date'] = Carbon::now($timezone)->startOfMonth()->subMonth()->toDateString();
                $dateFilters['end_date'] = Carbon::now($timezone)->subMonth()->endOfMonth()->toDateString();
            }elseif( ine($filters,'start_date') && ine($filters,'end_date')) {	// set carbon date format if both defined
                $dateFilters['start_date'] = Carbon::parse($dateFilters['start_date'], $timezone)->toDateString();
                $dateFilters['end_date'] = Carbon::parse($dateFilters['end_date'], $timezone)->toDateString();
            }elseif( !ine($filters,'start_date') && ine($filters,'end_date')) {		//set start date if not exist
                $dateFilters['start_date'] = Carbon::now($timezone)->startOfYear()->toDateString();
                $dateFilters['end_date'] = Carbon::parse($dateFilters['end_date'], $timezone)->toDateString();
            }elseif( ine($filters,'start_date') && !ine($filters,'end_date')) {		//set end date if not exist
                $dateFilters['start_date'] = Carbon::parse($dateFilters['start_date'], $timezone)->toDateString();
                $dateFilters['end_date'] = Carbon::now($timezone)->toDateString();
            }

            Request::merge($dateFilters);

        }catch(\Exception $e) {
            // nothing to do.
        }

        return $next($request);

    }
}
