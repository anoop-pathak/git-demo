<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use App\Repositories\JobsListingRepository;
use App\Repositories\UserRepository;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use App\Exceptions\InvalidDivisionException;

class UserWithCountController extends ApiController
{
    protected $model;
    protected $response;
    protected $repo;
    protected $scope;
    protected $jobRepo;

    public function __construct(
        User $model,
        UserRepository $repo,
        Context $scope,
        JobsListingRepository $jobRepo
    ) {

        parent::__construct();
        $this->model = $model;
        $this->scope = $scope;
        $this->repo = $repo;
        $this->jobRepo = $jobRepo;
    }

    /**
     * Get Users list with job counts
     * Get /users/with_count
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::onlyLegacy('type');
        try{
            $data = $this->getUsers($input);

            return ApiResponse::success($data);
        } catch(InvalidDivisionException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /*** Private Functions ***/

    /**
     * Get Users or Labours list with job count as a specific role
     *
     * @return
     */
    private function getUsers($filters)
    {
        if (!ine($filters, 'type')) {
            return ['data' => []];
        }

        if ($filters['type'] === 'customer_count_as_cr') {
            return $this->getUserWithCustomerCountAsCR();
        }

        if ($filters['type'] === 'job_count_as_user') {
            return $this->getUsersWithJobCountAsUserPermission();
        }

        if ($filters['type'] === 'job_count_as_cr') {
            return $this->getUsersWithJobCountAsCustomerRep();
        }

        if ($filters['type'] === 'job_count_as_jr') {
            return $this->getUsersWithJobCountAsJobRep();
        }


        if ($filters['type'] === 'job_count_as_je') {
            return $this->getUsersWithJobCountAsJobEstimator();
        }

        if ($filters['type'] === 'job_count_as_labor') {
            return $this->getLaboursWithJobCount();
        }

        if ($filters['type'] === 'job_count_as_sub') {
            return $this->getSubContractorsWithJobCount();
        }
    }

    private function getUsersWithJobCountAsUserPermission($filters=[])
    {
        $data = [];

        $jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $companyId = $this->scope->id();
        $users = User::activeLoggableCompanyUsers()
            ->division()
            ->leftJoin(
                DB::raw(
                    "(SELECT coalesce(jobs.parent_id, job_id) as job_id, rep_id AS user_id FROM job_rep INNER JOIN jobs ON jobs.id = job_rep.job_id WHERE rep_id != 0 AND job_id != 0
						UNION
							SELECT coalesce(jobs.parent_id, job_id), rep_id AS user_id FROM job_estimator INNER JOIN jobs ON jobs.id = job_estimator.job_id WHERE rep_id != 0 AND job_id != 0
						UNION
							SELECT jobs.id AS job_id, user_id FROM customer_user LEFT JOIN jobs ON jobs.customer_id = customer_user.customer_id WHERE jobs.id IS NOT NULL AND user_id != 0 AND customer_user.customer_id != 0 
								AND jobs.company_id = $companyId
						UNION
							SELECT jobs.id AS job_id, rep_id AS user_id FROM customers LEFT JOIN jobs ON jobs.customer_id = customers.id WHERE customers.rep_id IS NOT NULL AND customers.rep_id != 0 AND jobs.id IS NOT NULL 
								AND customers.company_id = $companyId
					) as job_access"
                ),
                'job_access.user_id',
                '=',
                'users.id'
            )
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_access.job_id')
            ->selectRaw("users.id,users.first_name, users.last_name, COUNT( Distinct jobs.id) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $filters['users'] = 'unassigned';
        $unAssignedJobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers'])
            ->count();

        $data['data'] = $users;

        $data['meta'] = [
            'unassigned_count' => $unAssignedJobs,
        ];

        return $data;
    }

    /**
     * Get Users list with Jobs Count as Customer Rep
     *
     * @return
     */
    private function getUsersWithJobCountAsCustomerRep($filters=[])
    {
        $data = [];

        $jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $users = User::activeLoggableCompanyUsers()
            ->division()
            ->leftJoin('customers', 'customers.rep_id', '=', 'users.id')
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'customers.id', '=', 'jobs.customer_id')
            ->selectRaw("users.id,users.first_name, users.last_name, COUNT( Distinct jobs.id) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $filters['rep_ids'] = 'unassigned';
        $unAssignedJobs = $this->jobRepo->getJobsQueryBuilder($filters)
            ->count();

        $data['data'] = $users;

        $data['meta'] = [
            'unassigned_count' => $unAssignedJobs,
        ];

        return $data;
    }

    /**
     * Get Users list with Jobs Count as Job Rep
     *
     * @return
     */
    private function getUsersWithJobCountAsJobRep($filters = [])
    {
        $data = [];

        $filters['include_projects'] = true;
        $jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $users = User::activeLoggableCompanyUsers()
            ->division()
            ->leftJoin(DB::raw("((select coalesce(jobs.parent_id, job_id) as job_id, rep_id from job_rep join jobs on jobs.id=job_rep.job_id)) AS job_rep"), 'job_rep.rep_id', '=', 'users.id')
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_rep.job_id')
            ->selectRaw("users.id, users.first_name, users.last_name, COUNT( Distinct COALESCE(jobs.parent_id,jobs.id)) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $filters['job_rep_ids'] = 'unassigned';
        $unAssignedJobs = $this->jobRepo->getJobsQueryBuilder($filters)
            ->count();

        $data['data'] = $users;

        $data['meta'] = [
            'unassigned_count' => $unAssignedJobs,
        ];

        return $data;
    }

    /**
     * Get Users list with Jobs Count as Job Estimator
     *
     * @return
     */
    private function getUsersWithJobCountAsJobEstimator($filters=[])
    {
        $data = [];

        $jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $users = User::activeLoggableCompanyUsers()
            ->division()
            ->leftJoin(DB::raw("(select coalesce(jobs.parent_id, job_id) as job_id, rep_id from job_estimator left join jobs on jobs.id=job_estimator.job_id) as job_estimator"), 'job_estimator.rep_id', '=', 'users.id')
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_estimator.job_id')
            ->selectRaw("users.id, users.first_name, users.last_name, COUNT( Distinct jobs.id) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $filters['estimator_ids'] = 'unassigned';
        $unAssignedJobs = $this->jobRepo->getJobsQueryBuilder($filters)->count();

        $data['data'] = $users;

        $data['meta'] = [
            'unassigned_count' => $unAssignedJobs,
        ];

        return $data;
    }

    /**
     * Get Labours list with Jobs Count as Labour
     *
     * @return
     */
    private function getLaboursWithJobCount($filters = [])
    {
        $data = [];

        $jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $users = User::onlyLabors()
            ->division()
            ->leftJoin('job_labour', 'job_labour.labour_id', '=', 'users.id')
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_labour.job_id')
            ->selectRaw("users.id, users.first_name, users.last_name, COUNT( Distinct jobs.id) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $filters['labor_ids'] = 'unassigned';
        $unAssignedJobs = $this->jobRepo->getJobsQueryBuilder($filters)->count();

        $data['data'] = $users;

        $data['meta'] = [
            'unassigned_count' => $unAssignedJobs,
        ];

        return $data;
    }

    /**
     * Get Labours list with Jobs Count as Sub Contractor
     *
     * @return
     */
    private function getSubContractorsWithJobCount($filters=[])
    {
        $data = [];

        $jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $users = User::onlySubContractors()
            ->division()
            ->leftJoin(DB::raw("(select coalesce(jobs.parent_id, job_id) as job_id, sub_contractor_id from job_sub_contractor join jobs on jobs.id=job_sub_contractor.job_id) as job_sub_contractor"), 'job_sub_contractor.sub_contractor_id', '=', 'users.id')
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_sub_contractor.job_id')
            ->selectRaw("users.id, users.first_name, users.last_name, users.company_name, COUNT( Distinct jobs.id) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $filters['sub_ids'] = 'unassigned';
        $unAssignedJobs = $this->jobRepo->getJobsQueryBuilder($filters)->count();

        $data['data'] = $users;

        $data['meta'] = [
            'unassigned_count' => $unAssignedJobs,
        ];

        return $data;
    }

    private function getUserWithCustomerCountAsCR($filters = [])
    {
        $data = [];

        $customerRepo = App::make(\App\Repositories\CustomerListingRepository::class);
        $customersQueryBuilder = $customerRepo->getCustomerQeuryBuilder($filters);
        $customersJoinQuery = generateQueryWithBindings($customersQueryBuilder);
        // dd($customersJoinQuery);
        $users = User::activeLoggableCompanyUsers()
            ->division()
            ->leftJoin(DB::raw("($customersJoinQuery) as customers"), 'customers.rep_id', '=', 'users.id')
            ->selectRaw("users.id, users.first_name, users.last_name, COUNT(customers.id) as count, users.group_id")
            ->groupBy('users.id')
            ->where('users.company_id', $this->scope->id())
            ->get();

        $data['data'] = $users;

        // add unsigned count..
        $filters['rep_ids'] = 'unassigned';
        $unAssignedCustomers = $customerRepo->getCustomerQeuryBuilder($filters)->count();

        $data['meta'] = [
            'unassigned_count' => $unAssignedCustomers,
        ];

        return $data;
    }

    private function getUserList()
    {
        $users = $this->model->activeLoggableCompanyUsers()
            ->division()
            ->whereCompanyId($this->scope->id())
            ->select('id', 'first_name', 'last_name')
            ->get();

        return $users;
    }
}
