<?php

namespace App\Services\Reports;

use App\Services\Contexts\Context;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Transformers\Optimized\UsersTransformer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Excel;

class CommissionsReport extends AbstractReport
{

    protected $scope;
    protected $userRepo;
    protected $jobRepo;

    /* Larasponse class Instance */
    protected $response;

    function __construct(Context $scope, UserRepository $userRepo, Larasponse $response, JobRepository $jobRepo)
    {
        $this->scope = $scope;
        $this->userRepo = $userRepo;
        $this->jobRepo  = $jobRepo;
        $this->response = $response;
    }

    /**
     * return data for sales performace report
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        $user = Auth::user();
        $canAccess = in_array('view_commission_report', $user->listPermissions());
        if($user->isStandardUser() && !$canAccess) {
            $filters['user_ids'] = [$user->id];
        }

        // //check JOB_AWARDED_STAGE is set
        // $jobAwardedStage = $this->getJobAwardedStage();

        //set date filters
        $filters = $this->setDateFilter($filters);

        //get users
        $users = $this->userRepo->getFilteredUsers($filters);

        $filters['include_projects'] = true;

        $users = $users->with([
            'commissions' => function ($query) use ($filters) {

                $startDate = ine($filters, 'start_date') ? $filters['start_date'] : null;
                $endDate = ine($filters, 'end_date') ? $filters['end_date'] : null;

                if(isset($filters['date_range_type'])){
                    $jobQuery = $this->jobRepo->getJobsQueryBuilder($filters)->select('jobs.id');
                    $jobsJoinQuery = generateQueryWithBindings($jobQuery);
                    $query->join(DB::raw("({$jobsJoinQuery}) as jobs"), 'jobs.id', '=', 'job_commissions.job_id')
                        ->select('job_commissions.*');
                }

                # date range
                if($startDate && $endDate && !isset($filters['date_range_type'])) {
                    $query->dateRange($startDate, $endDate);
                }

                $jobQuery = $this->jobRepo->getJobsQueryBuilder()
                    ->select('jobs.id', 'jobs.deleted_at');
                $jobQuery = generateQueryWithBindings($jobQuery);
                $query->join(DB::raw("({$jobQuery}) as commission_jobs"), function($join) {
                    $join->on('commission_jobs.id', '=', 'job_commissions.job_id')
                        ->whereNull('commission_jobs.deleted_at');
                });
                $query->excludeCanceled();

                if(ine($filters,'unpaid_commissions')){
                    $query->where('job_commissions.due_amount','>',0);
                }

                $query->select('job_commissions.*');
            }
        ]);

        if(ine($filters,'unpaid_commissions')){
            $jobCommissionQuery = $this->jobRepo->getJobsQueryBuilder($filters)
                ->join('job_commissions','job_commissions.job_id','=','jobs.id')
                ->where('job_commissions.due_amount', '>', 0)
                ->whereNull('job_commissions.canceled_at')
                ->select('job_commissions.user_id');
            $jobCommissionQuery = generateQueryWithBindings($jobCommissionQuery);
            $users->join(DB::raw("({$jobCommissionQuery}) as job_commission"), function($join) {
                $join->on('job_commission.user_id', '=', 'users.id');
            })->groupBy('users.id');
        }

        $limit = isset($filters['limit']) ? $filters['limit'] : \config('jp.pagination_limit');

        if(ine($filters, 'csv_export')) {
            return $this->getCommissionCsvExport($users, $filters);
       }

        // get users
        if (!$limit) {
            $users = $users->get();
        } else {
            $users = $users->paginate($limit);
        }

        $users->each(function ($user) {
            $totalCommission    = $user->commissions->sum('amount');
            $unpaidCommission   = $user->commissions->sum('due_amount');
            $paidCommission     = $totalCommission - $unpaidCommission ;
            $user->total_commission     =   $totalCommission;
            $user->paid_commission      =   $paidCommission;
            $user->unpaid_commission    =   $unpaidCommission;
        });


        if(!$limit) {
            $data = $this->response->collection($users, (new UsersTransformer));
        } else {
            $data = $this->response->paginatedCollection($users, (new UsersTransformer));
        }

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    public function getCsvFilters($input)
	{
		$commonFilters = parent::getCsvFilters($input);

		$filters[] = $commonFilters;

		$commissionFilter = "Both paid and unpaid";
		if(ine($input, 'unpaid_commissions')) {
			$commissionFilter = "Unpaid only";
		}

		$inactiveUserFilter = 'Included';
		if(ine($input, 'active')) {
			$inactiveUserFilter = 'Excluded';
		}

		$filters[] = 'Commissions: '.$commissionFilter;
		$filters[] = 'Inactive Users: '.$inactiveUserFilter;
		$allFilters = implode('| ', $filters);

		return array("Filters: ".$allFilters);
	}

	private function getCommissionCsvExport($users, $filters)
	{
		$users = $this->response->collection($users, function($user) {
			return [
				'User'				=> $user->full_name,
				'Total Commission'	=> $user->total_commission,
				'Unpaid Commission'	=> $user->unpaid_commission,
			];
		});

		if(empty($users['data'])) {
			$users['data'][] = $this->getDefaultColumns();
		}

		$csvFilters = $this->getCsvFilters($filters);
		Excel::create('Commissions Report', function($excel) use($users, $csvFilters){
			$excel->sheet('sheet1', function($sheet) use($users, $csvFilters){
				$sheet->mergeCells('A1:C1');
				$sheet->cells('A1:C1', function($cells) {
					$cells->setAlignment('center');
				});
				$sheet->getStyle('A1:C1')->getAlignment()->setWrapText(true);
				$sheet->setHeight(1, 60);
				$sheet->row(1, $csvFilters);
				$sheet->prependRow(2, array_keys($users['data'][0]));
				$sheet->setHeight(2, 15);
				$sheet->rows($users['data']);
			});
		})->export('xlsx');
	}

	private function getDefaultColumns()
	{
		return [
			'User',
			'Total Commission',
			'Unpaid Commission',
		];
	}
}
