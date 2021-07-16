<?php

namespace App\Services\Reports;

use Settings;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\JobType;
use App\Models\Trade;
use App\Models\Division;
use App\Models\User;

abstract class AbstractReport
{
    abstract function get();

    /**
     * check JOB_AWARDED_STAGE is set or not
     *
     * @return JOB_AWARDED_STAGE
     */
    protected function getJobAwardedStage()
    {
        $stage = config('awarded_stage');
        if (!$stage) {
            throw new \Exception(trans('response.error.set_job_awarded_stage'), 412);
        }

        return $stage;
    }

    /**
     * Set Date Filter
     *
     * @return $filters
     */
    public function setDateFilter($filters)
    {
        $timezone = Settings::get('TIME_ZONE');
        // Year till date (YTD)
        if (ine($filters, 'duration') && ($filters['duration'] == 'YTD')) {
            $filters['start_date'] = Carbon::now($timezone)->startOfYear()->toDateString();
            $filters['end_date'] = Carbon::now($timezone)->toDateString();

            return $filters;
        }

        // Month till date (MTD)
        if (ine($filters, 'duration') && ($filters['duration'] == 'MTD')) {
            $filters['start_date'] = Carbon::now($timezone)->startOfMonth()->toDateString();
            $filters['end_date'] = Carbon::now($timezone)->toDateString();

            return $filters;
        }

        // Week till date (WTD)
        if (ine($filters, 'duration') && ($filters['duration'] == 'WTD')) {
            $filters['start_date'] = Carbon::now($timezone)->startOfWeek()->toDateString();
            $filters['end_date'] = Carbon::now($timezone)->toDateString();

            return $filters;
        }

        //Since Inception
        if( ine($filters, 'duration')
            && ($filters['duration'] == 'since_inception')
            && !empty($companyData = $this->getCompanyData($filters))
        ) {

            $filters['start_date'] = null;
            $filters['end_date'] = null;
            return $filters;
        }

        // last month
        if( ine($filters,'duration') && ($filters['duration'] == 'last_month') ) {
            $filters['start_date'] = Carbon::now($timezone)->startOfMonth()->subMonth()->toDateString();
            $filters['end_date'] = Carbon::now($timezone)->subMonth()->endOfMonth()->toDateString();
            return $filters;
        }

        // set readable date format if both defined
        if (ine($filters, 'start_date') && ine($filters, 'end_date')) {
            $filters['start_date'] = Carbon::parse($filters['start_date'], $timezone)->toDateString();
            $filters['end_date'] = Carbon::parse($filters['end_date'], $timezone)->toDateString();

            return $filters;
        }

        //set start date if not exist
        if (!ine($filters, 'start_date') && ine($filters, 'end_date')) {
            $filters['start_date'] = Carbon::now($timezone)->startOfYear()->toDateString();
            $filters['end_date'] = Carbon::parse($filters['end_date'], $timezone)->toDateString();

            return $filters;
        }

        //set end date if not exist
        if (ine($filters, 'start_date') && !ine($filters, 'end_date')) {
            $filters['start_date'] = Carbon::parse($filters['start_date'], $timezone)->toDateString();
            $filters['end_date'] = Carbon::now($timezone)->toDateString();

            return $filters;
        }

        //set date if both not exist (default filter - YTD)
        if (!ine($filters, 'duration') || $filters['duration'] != 'since_inception') {
            $filters['start_date'] = Carbon::now($timezone)->startOfYear()->toDateString();
            $filters['end_date'] = Carbon::now($timezone)->toDateString();

            return $filters;
        }


        return $filters;
    }

    public function getCompanyData($filters)
    {
        $data = [];
        if(ine($filters,'duration') && ($filters['duration'] == 'since_inception')) {
            $company = Company::find(getScopeId());
            $companySubsciption= $company->subscription;
            $data = [
                'created_at' => $company->created_at,
            ];
        }
        return $data;
    }

    public function getCsvFilters($inputs){
		$filters = [];
		$dateRangeType = ine($inputs, 'date_range_type') ? $inputs['date_range_type']: null;
		$startDate = ine($inputs, 'start_date') ? $inputs['start_date']: null;
		$endDate = ine($inputs, 'end_date') ? $inputs['end_date']: null;

		if ($dateRangeType) {
			$filters[] = 'Date Range Type: '.ucwords(str_replace("_", " ", $dateRangeType));;
		}

		if ($startDate || $endDate){
			$filters[] = 'Duration: '.$startDate.' - '.$endDate;
		}

		if(ine($inputs, 'group')){
			$filters[] = 'View By: '.$inputs['group'];
		}

		if(ine($inputs, 'division_id')){
			$names = Division::where('company_id', getScopeId())
				->whereIn('id', (array)$inputs['division_id'])
				->pluck('name')
                ->toArray();

			if(!empty($names)){
				$names = implode(', ', $names);
				$filters[] = 'Division: '.$names;
			}
		}

		if(ine($inputs, 'user_id')){
			$userNames = User::where('company_id', getScopeId())
				->whereIn('id', (array)$inputs['user_id'])
				->selectRaw("CONCAT(first_name, ' ', last_name) AS full_name")
				->pluck('users.full_name')
                ->toArray();

			if(!empty($userNames)){
				$userNames = implode(', ', $userNames);
				$filters[] = 'Users: '.$userNames;
			}
		}

		if(ine($inputs, 'trades')){
			$trades = Trade::whereIn('id', (array)$inputs['trades'])->pluck('name')->toArray();

			if(!empty($trades)){
				$trades = implode(', ', $trades);
				$filters[] = 'Trades: '.$trades;
			}
		}

		if(ine($inputs, 'work_types')){
			$workTypes = JobType::whereIn('id', (array)$inputs['work_types'])->pluck('name')->toArray();

			if(!empty($workTypes)){
				$workTypes = implode(', ', $workTypes);
				$filters[] = 'Work Types: '.$workTypes;
			}
		}

		return implode('| ', $filters);
	}
}
