<?php namespace App\Services\JobCommission;

use Illuminate\Support\Facades\DB;
use App\Repositories\JobRepository;
use App\Repositories\CommissionsRepository;
use App\Services\Reports\CommissionsReport;
use App\Models\JobCommissionPayment;
use App\Models\JobCommission;
use Carbon\Carbon;

class JobCommissionService
{

    public function __construct(CommissionsReport $comReport, CommissionsRepository $repo, JobRepository $jobRepo)
    {
        $this->comReport = $comReport;
        $this->repo = $repo;
        $this->jobRepo = $jobRepo;
    }

    public function userCommissions($filters)
    {
        $filters['include_projects'] = true;
        // set date range filters
        $filters = $this->comReport->setDateFilter($filters);
        $commissions = $this->repo->getFiltredCommissions($filters);

        $jobFilters = $filters;
		if(ine($jobFilters, 'user_ids')){
			unset($jobFilters['user_ids']);
		}
        $jobQuery = $this->jobRepo->getJobsQueryBuilder($filters)->select('jobs.id', 'jobs.deleted_at');
        $jobQuery = generateQueryWithBindings($jobQuery);
        $commissions->join(DB::raw("({$jobQuery}) as jobs"), function($join) {
            $join->on('jobs.id', '=', 'job_commissions.job_id')
                ->whereNull('jobs.deleted_at');
        });

        //for awarded job (sales performance report)
        if (ine($filters, 'for_awarded_job')) {
            $startDate = ine($filters, 'start_date') ? $filters['start_date'] : null;
            $endDate = ine($filters, 'end_date') ? $filters['end_date'] : null;

            $commissions->whereHas('job', function ($query) use ($filters, $startDate, $endDate) {
                $query->closedJobs($startDate, $endDate);
                $this->applyJobFilters($query, $filters);
                $query->excludeLostJobs();
                $query->excludeProjects();
            });
        }

        // exclude canceled commissions..
        $commissions->excludeCanceled();

        $commissions->select('job_commissions.*');

        return $commissions;
    }

    public function addCommissionPayment($commission, $jobId, $amount, $paidBy, $paidOn)
    {
        $dueAmount = $commission->due_amount - $amount;
        $status = JobCommission::UNPAID;
        if($dueAmount <= 0) {
            $dueAmount = 0;
            $status = JobCommission::PAID;
        }
        $commission->due_amount = $dueAmount;
        $commission->status = $status;
        $commission->save();
        $data = [
            'company_id'    => getScopeId(),
            'job_id'        => $jobId,
            'commission_id' => $commission->id,
            'amount'        => $amount,
            'paid_by'       => $paidBy,
            'paid_on'       => $paidOn,
        ];
        
        return JobCommissionPayment::create($data);
    }
    
    public function cancelPayment($payment, $canceledAt)
    {
        $payment->update([
            'canceled_at' => $canceledAt,
        ]);
        return $payment;
    }
    
    public function getCommissionPayment($commissionId)
    {
        $commission = JobCommission::findOrFail($commissionId);
        $payments = $commission->commissionPayment();
        return \ApiResponse::success(
            $this->response->collection($payments, new JobCommissionPaymentTransformer)
        );
    }


    /***** Private Functions *****/

    /**
     * jobs filters
     *
     * @param $query | $filters(array)
     * @return $query
     */
    protected function applyJobFilters($query, $filters = [])
    {
        if (ine($filters, 'user_ids')) {
            $userId = $filters['user_ids'];
        } else {
            $userId = $filters['user_id'];
        }

        // get both customer reps and estimators
        if (ine($filters, 'sales_performance_for')
            && in_array('customer_rep', (array)$filters['sales_performance_for'])
            && in_array('estimator', (array)$filters['sales_performance_for'])
        ) {
            $query->where(function ($query) use ($filters, $userId) {
                $query->whereIn('jobs.customer_id', function ($query) use ($userId) {
                    $query->select('id')->from('customers')
                        ->whereIn('rep_id', (array)$userId);
                })->orWhereIn('jobs.id', function ($query) use ($userId) {
                    $query->select('job_id')->from('job_estimator')
                        ->whereIn('rep_id', (array)$userId);
                });
            });
        }

        //get only customer reps (default filter)
        if ((ine($filters, 'sales_performance_for')
            && in_array('customer_rep', (array)$filters['sales_performance_for'])
            && !in_array('estimator', (array)$filters['sales_performance_for']))) {
            $query->whereIn('jobs.customer_id', function ($query) use ($userId) {
                $query->select('id')->from('customers')
                    ->whereIn('rep_id', (array)$userId);
            });
        }

        //get only estimators
        if ((ine($filters, 'sales_performance_for')
            && !in_array('customer_rep', (array)$filters['sales_performance_for'])
            && in_array('estimator', (array)$filters['sales_performance_for']))) {
            $query->whereIn('jobs.id', function ($query) use ($userId) {
                $query->select('job_id')->from('job_estimator')
                    ->whereIn('rep_id', (array)$userId);
            });
        }

        // set deafult (only customer_reps)
        if ((ine($filters, 'sales_performance_for')
                && !in_array('customer_rep', (array)$filters['sales_performance_for'])
                && !in_array('estimator', (array)$filters['sales_performance_for']))
            || (!ine($filters, 'sales_performance_for'))
        ) {
            $query->whereIn('jobs.customer_id', function ($query) use ($userId) {
                $query->select('id')->from('customers')
                    ->whereIn('rep_id', (array)$userId);
            });
        }

        //exclude job types
        if (ine($filters, 'exclude_job_types')) {
            $query->excludeWorkTypes($filters['exclude_job_types']);
        }

        if (ine($filters, 'with_archived')) {
            $query->withArchived();
        } elseif (ine($filters, 'only_archived')) {
            $query->onlyArchived();
        } else {
            $query->withoutArchived();
        }
    }
}
