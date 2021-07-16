<?php

namespace App\Services\Reports;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\JobType;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\JobRepository;
use App\Repositories\WorkflowRepository;
use App\Services\Contexts\Context;
use App\Transformers\MasterListsTransformer;
use Carbon\Carbon;
use Request;
use Sorskod\Larasponse\Larasponse;
use Excel;
use PDF;

class MasterListReport extends AbstractReport
{

    protected $scope;

    function __construct(Context $scope, JobRepository $jobRepo, Larasponse $response, WorkflowRepository $workflowRepo)
    {
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->response = $response;
        $this->workflowRepo = $workflowRepo;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Get Master List Report Data
     * @param  array $filters Array of filters
     * @return Response
     */
    public function get($filters = [])
    {
        $filters['exclude_parent'] = true;
        $filters['with_archived'] = true;

        //set date filters
        $filters = $this->setDateFilter($filters);

        // $jobs = $this->jobRepo->getFilteredJobs($filters);
        $jobs = $this->getJobsQuery($filters);

        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        if (ine($filters, 'pdf_print')) {
            if (!$limit) {
                $jobs = $jobs->get($limit);
            } else {
                $jobs = $jobs->paginate($limit);
            }

            return $this->pdfPrint($jobs, $filters);
        }

        $with = $this->includeData($filters);
        $jobs->with($with);

        // Export CSV file of Master List Report
        if (ine($filters, 'csv_export')) {
            return $this->csvExport($jobs->get(), $filters);
        }

        if(!$limit) {
            $jobs = $jobs->get();
            $data = $this->response->collection($jobs, new MasterListsTransformer);
        } else {
            $jobs = $jobs->paginate($limit);
            $data = $this->response->paginatedCollection($jobs, new MasterListsTransformer);
        }

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }
    /************ PRIVATE METHODS **************/

    /**
     * Pdf Print
     * @param  Collections $jobs Collections of jobs
     * @param  Array $filters Filters
     * @return Response
     */
    private function pdfPrint($jobs, $filters)
    {
        try {
            $companyId = getScopeId();
            $applyFilter = false;

            $stages = null;
            if (ine($filters, 'stages')) {
                $applyFilter = true;
                $stages = $this->workflowRepo->getActiveWorkflow(getScopeId())
                    ->stages()
                    ->whereIn('code', (array)$filters['stages'])
                    ->get();
            }

            $jobReps = null;
            if (ine($filters, 'job_rep_ids')) {
                $applyFilter = true;
                $jobReps = User::where('company_id', $companyId)
                    ->whereIn('id', (array)$filters['job_rep_ids'])
                    ->select('first_name', 'last_name')
                    ->get();
            }

            $labours = null;
            if (ine($filters, 'labor_ids')) {
                $applyFilter = true;
                $labours = User::where('company_id', $companyId)
                    ->whereIn('id', (array)$filters['labor_ids'])
                    ->select('first_name', 'last_name')
                    ->get();
            }

            $customerReps = null;
            if (ine($filters, 'rep_ids')) {
                $applyFilter = true;
                $customerRep = User::where('company_id', $companyId)
                    ->whereIn('id', (array)$filters['rep_ids'])
                    ->select('first_name', 'last_name')
                    ->get();
            }

            $subContractors = null;
            if (ine($filters, 'sub_ids')) {
                $applyFilter = true;
                $subContractors = User::where('company_id', $companyId)
                    ->whereIn('id', (array)$filters['sub_ids'])
                    ->select('first_name', 'last_name')
                    ->get();
            }

            $estimators = null;
            if (ine($filters, 'estimator_ids')) {
                $applyFilter = true;
                $estimators = User::where('company_id', $companyId)
                    ->select('first_name', 'last_name')
                    ->whereIn('id', (array)$filters['estimator_ids'])
                    ->get();
            }

            $trades = null;
            if (ine($filters, 'trades')) {
                $trades = Trade::whereIn('id', (array)$filters['trades'])
                    ->select('name')
                    ->get();
            }

            $workTypes = null;
            if (ine($filters, 'work_types')) {
                $applyFilter = true;
                $workTypes = JobType::whereIn('id', (array)$filters['work_types'])
                    ->where('company_id', $companyId)
                    ->whereType(\JobType::WORK_TYPES)
                    ->select('name')
                    ->get();
            }

            $company = Company::find($this->scope->id());
            $contents = \view('jobs.master-list', [
                'jobs' => $jobs,
                'filters' => $filters,
                'company' => $company,
                'jobReps' => $jobReps,
                'labours' => $labours,
                'customerReps' => $customerReps,
                'subContractors' => $subContractors,
                'estimators' => $estimators,
                'trades' => $trades,
                'workTypes' => $workTypes,
                'stages' => $stages,
                'applyFilter' => $applyFilter
            ])->render();

            $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation('landscape');
            $pdf->setOption('dpi', 200);

            return $pdf->stream('master-list.pdf');
        } catch (\Exception $e) {
            return \view('error-page', [
                'errorDetail' => getErrorDetail($e),
                'message' => trans('response.error.error_page'),
            ]);
        }
    }

    /**
     * CSV export
     * @param  Collections $jobs Collections of jobs
     * @return Response
     */
    private function csvExport($jobs, $filters)
    {
        $masterListReport = $this->response->collection($jobs, function ($job) use ($filters) {
            $currentStage = $job->getCurrentStage();
            $data = [
                'Customer Name' => $job->customer->full_name,
                'Job Id' => $job->number,
                'Trades' => implode(', ', $job->trades->pluck('name')->toArray()),
                'Job Price' => $job->amount,
                'Stage' => $currentStage['name'],
                'Priority' => ucfirst($job->priority),
                'Note' => $job->note,
                'Note Date' => isset($job->note_date) ? Carbon::parse($job->note_date)->format(config('jp.date_format')) : "",
                'Job Completion Date'  => ($cDate = $job->completion_date) ? Carbon::parse($cDate)->format(config('jp.date_format')) : "",
            ];

            if (ine($filters, 'contract_signed_date')) {
                $data['Contract Signed Date'] = ($csDate = $job->cs_date) ? Carbon::parse($csDate)->format(config('jp.date_format')) : "";
            }

            return $data;
        });

        Excel::create('Mater_List_Report', function ($excel) use ($masterListReport) {
            $excel->sheet('sheet1', function ($sheet) use ($masterListReport) {
                $sheet->fromArray($masterListReport['data']);
            });
        })->export('csv');
    }

    private function includeData($input)
    {
        $with = ['trades'];

        if (!isset($input['includes'])) {
            return $with;
        }

        $includes = (array)$input['includes'];

        if (in_array('customer.rep', $includes)) {
            $with[] = 'customer.rep.profile';
        }

        if (in_array('estimators', $includes)) {
            $with[] = 'estimators.profile';
        }

        return $with;
    }

    /**
	 * get jobs query builder
	 * @param  $filters
	 * @return $jobs
	 */
	private function getJobsQuery($filters)
	{
		$joins = [];

		if(ine($filters, 'job_cities')) {
			$joins[] = 'address';
		}

		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins)->sortable();
		$jobs->select('jobs.*');
		$jobs->attachCurrentStage();

		if(!ine($filters, 'name') && (!ine($filters, 'upcoming_appointments')) && (!ine($filters, 'upcoming_schedules'))){
			$jobs->orderBy('jobs.created_date','DESC');
		}

		return $jobs;
	}
}
