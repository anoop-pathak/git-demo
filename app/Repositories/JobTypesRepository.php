<?php

namespace App\Repositories;

use App\Models\JobType;
use App\Services\Contexts\Context;
use App\Services\QuickBooks\QuickBookProducts;
use App\Services\QuickBooks\QuickBookService;
use Illuminate\Support\Facades\App;
use App\Services\QuickBooks\Facades\Item as QBItem;

class JobTypesRepository extends ScopedRepository
{
    /**
     * The base eloquent JobType
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(JobType $model, Context $scope, QuickBookProducts $qbProduct, QuickBookService $quickService)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->qbProduct = $qbProduct;
        $this->quickService = $quickService;
    }

    /**
     * Add JobType
     * @param $input : [array] : user inputs
     */
    public function addJobType($input)
    {
        $jobType = $this->model;
        $jobType->name = $input['name'];

        if ($this->scope->has()) {
            $jobType->company_id = $this->scope->id();
        }

        if ((int)$input['type'] === JobType::WORK_TYPES) {
            // get default color for work types..
            $count = $this->make()->whereType(JobType::WORK_TYPES)
                ->withTrashed()
                ->count();

            $jobType->color = config('default-colors.' . $count);
        }

        if (ine($input, 'qb_id')) {
            $jobType->qb_id = $input['qb_id'];
        }

        if (ine($input, 'qb_account_id')) {
            $jobType->qb_account_id = $input['qb_account_id'];
        }

        $jobType->qb_id = ine($input, 'qb_id') ? $input['qb_id'] : null;
        $jobType->qb_account_id = ine($input, 'qb_account_id') ? $input['qb_account_id'] : null;
        $jobType->type = $input['type'];
        $jobType->trade_id = $input['trade_id'];

        $jobType->save();


        if (ine($input, 'sync_on_qb') && ($jobType->qb_account_id)) {
            $token = $this->quickService->getToken();
            QBItem::createOrUpdateProduct($jobType, $jobType->qb_account_id);
            // $this->qbProduct->createOrUpdateProduct($token, $jobType, $jobType->qb_account_id);
        }

        return $jobType;
    }

    /**
     * Update JobType
     * @param $input : [array] : user inputs
     */
    public function updateJobType($jobType, $name, $meta)
    {
        if ($jobType->type == 2) {
            $jobType->color = issetRetrun($meta, 'color') ?: $jobType->color;
        }

        if (ine($meta, 'qb_id')) {
            $jobType->qb_id = $meta['qb_id'];
        }

        if (ine($meta, 'qb_account_id')) {
            $jobType->qb_account_id = $meta['qb_account_id'];
        }

        $jobType->trade_id = issetRetrun($meta, 'trade_id') ?: null;
        $jobType->name = $name;
        $jobType->update();

        if (ine($meta, 'sync_on_qb') && ($jobType->qb_account_id)) {
            $token = $this->quickService->getToken();
            QBItem::createOrUpdateProduct($jobType, $jobType->qb_account_id);
            // $this->qbProduct->createOrUpdateProduct($token, $jobType, $jobType->qb_account_id);
        }

        return $jobType;
    }

    /**
     * delete specific Job/Work Type
     * @param $id : [int]
     */
    public function deleteJobType($id)
    {
        $jobType = $this->make()->findOrFail($id);
        $deletedJobType = clone $jobType;
        $jobType->delete();

        return $deletedJobType;
    }

    /**
     * get list of Job/Work Types
     * @param $filters : [array]
     */
    public function getJobTypes($filters = [])
    {
        switchDBToReadOnly();
        $jobTypes = $this->model
            ->sortable();


        if ($this->scope->has()) {
            $jobTypes->where(function ($query) {
                $query->whereNull('job_types.company_id')->orWhere('job_types.company_id', $this->scope->id());
            });
        } else {
            $jobTypes->whereNull('job_types.company_id');
        }

        $this->applyFilter($jobTypes, $filters);

        switchDBToReadWrite();

        return $jobTypes;
    }

    public function getJobTypesWithJobCount($filters)
    {
        switchDBToReadOnly();
        $jobRepo = App::make(\App\Repositories\JobsListingRepository::class);
        $filters['include_projects'] = true;
        $jobsQueryBuilder = $jobRepo->getJobsQueryBuilder($filters);
        $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

        $jobTypes = $this->getJobTypes($filters);
        $jobTypes = $jobTypes->leftJoin(\DB::raw("(select coalesce(jobs.parent_id, job_id) as job_id, job_work_types.job_type_id from job_work_types join jobs on jobs.id=job_work_types.job_id) as job_work_types"), 'job_work_types.job_type_id', '=', 'job_types.id')
            ->leftJoin(\DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_work_types.job_id')
            ->selectRaw( 'job_types.id, job_types.name, job_types.type, COUNT(Distinct COALESCE(jobs.parent_id, jobs.id)) as job_count')
            ->groupBy('job_types.id')
            ->get();

        switchDBToReadWrite();

        return $jobTypes;
    }

    /**
     * get work type by id
     * @param  $id
     * @return $workType
     */
    public function getWorkTypeById($id)
    {
        $workType = $this->make()->whereId($id)
            ->whereType(JobType::WORK_TYPES)
            ->firstOrFail();

        return $workType;
    }

    /******************** Private Section *********************/

    private function applyFilter($query, $filters)
    {
        // apply type filter (default type is 2 which is 'Work Type')
        if (ine($filters, 'type')) {
            $query->whereType($filters['type']);
            if ($filters['type'] == JobType::JOB_TYPES) {
                $query->orderBy('order', 'asc');
            } else {
                $query->orderBy('name', 'asc');
            }
        } else {
            $query->whereType(JobType::WORK_TYPES);
            $query->orderBy('name', 'asc');
        }

        // apply trades filter
        if (ine($filters, 'trade_ids')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('trade_id', array_filter((array)$filters['trade_ids']));
            });
        }

        if (ine($filters, 'without_trade')) {
            $query->whereTradeId(false);
        }
    }
}
