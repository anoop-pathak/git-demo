<?php

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\JobRepository;
use App\Repositories\WorkflowRepository;
use App\Transformers\ReportJobListingTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Sorskod\Larasponse\Larasponse;
use PDF;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;
use App\Transformers\JobsExportTransformer;
use Excel;

class JobListing extends AbstractReport
{
    protected $jobRepo;
    protected $scope;

    function __construct(JobRepository $jobRepo, Larasponse $response, WorkflowRepository $workflowRepo, Context $scope)
    {
        $this->workflowRepo = $workflowRepo;
        $this->jobRepo = $jobRepo;
        $this->response = $response;
        $this->scope = $scope;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Job Listing
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        //check JOB_AWARDED_STAGE is set
        $this->getJobAwardedStage();

        //set date filters
        $printFilters = $filters = $this->setDateFilter($filters);

        if (ine($filters, 'date_range_type')) {
            switch ($filters['date_range_type']) {
                case 'job_awarded_date':
                    $filters['awarded_jobs'] = true;
                    $filters['awarded_from'] = $filters['start_date'];
                    $filters['awarded_to'] = $filters['end_date'];
                    unset($filters['start_date']);
                    unset($filters['end_date']);
                    break;
                case 'job_lost_date':
                    $filters['follow_up_marks'][] = 'lost_job';
                    $filters['lost_job_from'] = $filters['start_date'];
                    $filters['lost_job_to'] = $filters['end_date'];
                    unset($filters['start_date']);
                    unset($filters['end_date']);
                    break;
            }
        }

        $join = [];

        // join customers table in case of referred_type filter
        if (ine($filters, 'referred_type')) {
            $join[] = 'customers';
        }

        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, $join);

        //get last stage of workflow
        if (ine($filters, 'closed_job')) {
            $workflowLastStage = $this->workflowRepo->getActiveWorkflow(getScopeId())
                ->stages
                ->last();
            $jobs->checkStageHistory($workflowLastStage->code);
        }

        $jobs->orderBy('id', 'desc');
        $jobs->select('jobs.*');
        $jobs->attachCurrentStage();
        $jobs->attachAwardedStage();
        $jobs->groupBy('jobs.id');

        $jobs->whereHas('financialCalculation', function ($query) use ($filters) {
            if (ine($filters, 'orig_contract_amount')) {
                $query->where('total_job_amount', '>', 0);
            }

            if (ine($filters, 'change_order_amount')) {
                $query->where('total_change_order_amount', '>', 0);
            }

            if (ine($filters, 'contract_amount')
                || ine($filters, 'closed_job')
                || ine($filters, 'total_referral_amount')) {
                $query->where('total_amount', '>', 0);
            }
        });

        if (ine($filters, 'pdf_print')) {
            return $this->pdfPrint($jobs, $printFilters);
        }

        //eager loading
        $jobs->with([
            'customer.phones',
            'trades',
            'jobMeta',
            'address.state',
            'address.country',
            'jobWorkflow.job',
            'address',
            'projectStatus',
            'projects' => function ($query) use ($filters) {

                $userIds = ine($filters, 'user_id') ? $filters['user_id'] : null;
                if (ine($filters, 'user_ids')) {
                    $userIds = $filters['user_ids'];
                }

                //for getting project count
                if (ine($filters, 'sales_performance_for')
                    && in_array('estimator', $filters['sales_performance_for'])
                    && in_array('customer_rep', $filters['sales_performance_for'])) {

                    $query->where(function ($query) use ($userIds) {
                        $query->whereIn('jobs.customer_id', function ($query) use ($userIds) {
                            $query->select('id')->from('customers')
                                ->whereIn('rep_id', (array)$userIds);
                        });
                        $query->orWhereIn('jobs.id', function ($query) use ($userIds) {
                            $query->select('job_id')->from('job_estimator')->whereIn('rep_id', (array)$userIds);
                        });
                    });
                } elseif (ine($filters, 'sales_performance_for')
                    && in_array('estimator', $filters['sales_performance_for'])
                    && !in_array('customer_rep', $filters['sales_performance_for'])) {
                    $query->whereIn('jobs.id', function ($query) use ($userIds) {
                        $query->select('job_id')
                            ->from('job_estimator')
                            ->whereIn('rep_id', (array)$userIds);
                    });
                }

                # for as deafult customer rep and estimator
                if(ine($filters, 'sales_performance_for')
                    && !in_array('estimator', $filters['sales_performance_for'])
                    && !in_array('customer_rep', $filters['sales_performance_for'])
                    || (!ine($filters, 'sales_performance_for'))
                ) {
                    $query->where(function($query) use($userIds) {
                        $query->whereIn('jobs.customer_id', function($query) use($userIds) {
                            $query->select('id')->from('customers')
                                  ->whereIn('rep_id', (array)$userIds);
                        });
                        $query->orWhereIn('jobs.id', function($query) use($userIds) {
                            $query->select('job_id')
                                ->from('job_estimator')
                                ->whereIn('rep_id', (array)$userIds);
                        });
                    });
                }

                $query->select('jobs.id', 'jobs.parent_id');
            },
            'financialCalculation' => function ($query) use ($filters) {
                $query->where('job_financial_calculations.multi_job', '=', 0);
            }
        ]);

        if(ine($filters, 'csv_export')) {
			return $this->csvExport($jobs->get(), $filters);
		}

        $data = [];

        //select job columns
        if (!$limit) {
            $jobs = $jobs->get();

           $data =  $this->response->collection($jobs, new ReportJobListingTransformer);
        }else{
            $jobs = $jobs->paginate($limit);
            $data = $this->response->paginatedCollection($jobs, new ReportJobListingTransformer);
        }

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    public function csvExport($data, $filters)
	{
		$report = $this->response->collection($data, new JobsExportTransformer);

		$sheet = Excel::create('Job_Listing_Report', function($excel) use($report, $filters) {
			$excel->sheet('sheet1', function($sheet) use($report, $filters) {
				$sheet->fromArray($report['data']);
			});
		});
		$sheet->export('csv');
	}


    public function pdfPrint($jobs, $filters)
    {
        $trades = Trade::pluck('name', 'id')->toArray();
        $company_id = getScopeId();
        $company = Company::find($company_id);
        $users = User::where('company_id', $company_id)->select('id', DB::raw("CONCAT(first_name,' ',last_name) as fname"))->pluck('fname', 'id')->toArray();
        $stages = $this->workflowRepo->getActiveWorkflow($company_id)->stages->pluck('name', 'code')->toArray();
        $jobs = $jobs->with([
            'customer',
            'customer.address',
            'customer.address.state',
            'customer.address.country',
            'customer.phones',
            'customer.flags',
            'customer.rep',
            'customer.secondaryNameContact',
            'address.state',
            'address.country',
            'trades',
            'workTypes',
            'jobWorkflow',
            'flags',
            'todayAppointments',
            'upcomingAppointments',
            'reps',
            // 'labours',
            'subContractors',
            'estimators',
            'projects' => function ($query) use ($filters) {

                $userIds = ine($filters, 'user_id') ? $filters['user_id'] : null;
                if (ine($filters, 'user_ids')) {
                    $userIds = $filters['user_ids'];
                }

                //for getting project count
                if (ine($filters, 'sales_performance_for')
                    && in_array('estimator', $filters['sales_performance_for'])
                    && in_array('customer_rep', $filters['sales_performance_for'])) {
                    $query->where(function ($query) use ($userIds) {
                        $query->whereIn('jobs.customer_id', function ($query) use ($userIds) {
                            $query->select('id')->from('customers')
                                ->whereIn('rep_id', (array)$userIds);
                        });
                        $query->orWhereIn('jobs.id', function ($query) use ($userIds) {
                            $query->select('job_id')->from('job_estimator')->whereIn('rep_id', (array)$userIds);
                        });
                    });
                } elseif (ine($filters, 'sales_performance_for')
                    && in_array('estimator', $filters['sales_performance_for'])
                    && !in_array('customer_rep', $filters['sales_performance_for'])) {
                    $query->whereIn('jobs.id', function ($query) use ($userIds) {
                        $query->select('job_id')->from('job_estimator')->whereIn('rep_id', (array)$userIds);
                    });
                }
            }
        ])->get();

        //get appointment count, by default is 0
        $appointmentCount = 0;
        if (ine($filters, 'appointment_count')) {
            $appointment = Appointment::recurring()->whereCompanyId($company_id);

            if(!ine($filters, 'user_id')) {
                $filters['user_id'] = (array) Auth::id();
            }

            if (ine($filters, 'user_id')) {
                $appointment->users((array)$filters['user_id']);
            }

            $appointment->dateRange($filters['start_date'], $filters['end_date']);
            $appointmentCount = $appointment->count();
        }

        $mode = 'landscape';
        $view = 'jobs.jobs_export_landscape';

        $contents = \view($view, [
            'jobs' => $jobs,
            'users' => $users,
            'trades' => $trades,
            'stages' => $stages,
            'filters' => $filters,
            'company' => $company,
            'appointment_count' => $appointmentCount,
            'company_country_code' => $company->country->code
        ])->render();
        $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation($mode);
        $pdf->setOption('dpi', 200);

        return $pdf->stream('jobs.pdf');
    }
}
