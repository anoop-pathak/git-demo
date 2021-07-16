<?php

namespace App\Repositories;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

class JobsListingRepository extends JobRepository
{

    public function getFilteredJobs($filters, $sortable = true, $eagerLoading = true)
    {
        $jobs = $this->getJobs($sortable, $filters, $eagerLoading);

        $this->withFinancials($jobs, $filters);

        // add awarded stage..
        $this->attachAwardedStage($jobs);

        $jobs->projectsCount($filters);

        $this->applyFilters($jobs, $filters);

        return $jobs;
    }

    public function getFilteredJobsCount($filters)
    {
        $jobs = $this->getJobs(false, $filters, false, true);

        $this->withFinancials($jobs, $filters, true);

        // add awarded stage..
        $this->attachAwardedStage($jobs);

        $this->applyFilters($jobs, $filters);

        return $jobs->get()->count();
    }

    public function getJobs($sortable = true, $params = [], $eagerLoading = true, $count = false)
    {
        $jobs = null;

        $jobs = $this->make();

        if ($sortable) {
            $jobs->Sortable();
        }

        if (!ine($params, 'name') && (!ine($params, 'upcoming_appointments')) && (!ine($params, 'upcoming_schedules'))) {
            $jobs->orderBy('jobs.created_date', 'DESC');
        }

        $companyId = getScopeId();

        $jobs->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('job_workflow as jw', 'jw.job_id', '=', 'jobs.id')
            ->leftJoin('addresses as customer_address', 'customer_address.id', '=', 'customers.address_id')
            ->groupBy('jobs.id');

        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $lat = $params['lat'];
            $long = $params['long'];
            $jobs->leftJoin(DB::raw("(select addresses.*,( 3959 * acos( cos( radians($lat) ) * cos( radians( addresses.lat ) ) 
					   * cos( radians(addresses.long) - radians($long)) + sin(radians($lat)) 
					   * sin( radians(addresses.lat)))) as distance from addresses where company_id={$companyId}) as addresses"), 'addresses.id', '=', 'jobs.address_id');
        } else {
            $jobs->leftJoin('addresses', 'addresses.id', '=', 'jobs.address_id');
        }

        // calculate distance if required..
        if ($count) {
            $jobs->select(DB::raw('jobs.id'));
        } else {
            $jobs->select(DB::raw('jobs.*, jw.stage_last_modified as stage_changed_date'));
        }

        if ((ine($params, 'lat') && ine($params, 'long'))
            && Address::isDistanceCalculationPossible()) {
            $jobs->addSelect(DB::raw('addresses.distance as distance'));
        }


        // exclude jobs without customer (customer may delete)
        $jobs->has('customer');

        if ($eagerLoading) {
            $this->eagerLoadingData($jobs, $params);
        }

        $jobs->addScheduleStatus($jobs);

        return $jobs;
    }

    private function eagerLoadingData($jobs, $filters = [])
    {
        // common eagerloads
        $jobs->with([
            'trades',
            'jobMeta',
            'address.state',
            'address.country',
            'jobWorkflow.job',
            // 'currentFollowUpStatus',
            'projects' => function ($projects) use ($filters) {

                if (ine($filters, 'sales_performance_for')) {
                    $filters['projects_only'] = true;
                    $this->attachAwardedStage($projects);
                    $this->applyFilters($projects, $filters);
                }

                if (ine($filters, 'project_number')) {
                    $projects->where('number', $filters['project_number']);
                }

                if (ine($filters, 'project_alt_id')) {
                    $projects->where('alt_id', $filters['project_alt_id']);
                }
            }
        ]);


        $includes = isset($filters['includes']) ? $filters['includes'] : [];

        if (in_array('production_boards', $includes)) {
            $jobs->with('productionBoards');
        }

        if(in_array('flags.color', $includes)) {
			$jobs->with(['flags.color']);
		}

        // only for optimized data..
        if (ine($filters, 'optimized')) {
            $jobs->with([
                'company.subscriberResource',
                'workflow',
            ]);

            return $jobs;
        }

        // all eagerloads
        $with = [
            'reps',
            'customer',
            'customer.phones',
            'currentFollowUpStatusOne',
            'currentFollowUpStatusOne.task',
            'schedules',
            'trades',
            'workTypes',
            'jobTypes',
            'todayAppointments',
            'upcomingAppointments',
            'flags'
        ];

        if(in_array('deleted_by', $includes)) {
			$with[] = 'deletedBy';
        }

        if(in_array('flags.color', $includes)) {
			$with[] = 'flags.color';
		}

        $jobs->with($with);
    }
}
