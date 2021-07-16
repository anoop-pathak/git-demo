<?php

namespace App\Services\Reports;

use App\Models\Proposal;
use App\Repositories\JobRepository;
use App\Repositories\ProposalsRepository;
use App\Services\Contexts\Context;
use App\Services\Solr\Solr;
use App\Transformers\ProposalsTransformer;
use Sorskod\Larasponse\Larasponse;

class ProposalsReport extends AbstractReport
{
    protected $scope;
    protected $proposalRepo;
    protected $response;

    function __construct(Context $scope, ProposalsRepository $proposalRepo, Larasponse $response, JobRepository $jobRepo)
    {
        $this->scope = $scope;
        $this->proposalRepo = $proposalRepo;
        $this->response = $response;
        $this->jobRepo = $jobRepo;
    }

    /**
     * return data for sales performace report
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        // //check JOB_AWARDED_STAGE is set
        // $jobAwardedStage = $this->getJobAwardedStage();

        //set include
        $include = ['job', 'customer'];

        //set date filters
        $filters = $this->setDateFilter($filters);
        // also include multi page proposals
        $filters['multi_page'] = true;

        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        if (ine($filters, 'customer_name') && Solr::isRunning()) {
            $customerIds = Solr::customerSearchByName($filters['customer_name']);
            $filters['customer_ids'] = $customerIds;
            unset($filters['customer_name']);
        }

        $query = $this->proposalRepo->getProposals($filters)
            ->with('job', 'job.customer');

        if (!ine($filters, 'status')) {
            $query->whereNotIn('status', (array)Proposal::DRAFT);
        }

        $this->applyFilters($query, $filters);

        // get proposals
        if (!$limit) {
            $proposals = $query->get();
            $data = $this->response->collection($proposals,(new ProposalsTransformer)->setDefaultIncludes($include));
        } else {
            $proposals = $query->paginate($limit);

            $data = $this->response->paginatedCollection(
                $proposals,
                (new ProposalsTransformer)->setDefaultIncludes($include)
            );

        }

        // set count
        $data['meta']['count'] = $this->getCount($filters);

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    /**
     * get proposal counts accordung to status
     *
     * @param $filters (array)
     * @return $count (array)
     */
    private function getCount($filters)
    {
        unset($filters['status']);

        $filters['stop_eager_loading'] = true;

        $proposal = $this->proposalRepo->getProposals($filters);
        $this->applyFilters($proposal, $filters);
        $proposals = $proposal->selectRaw('COUNT(proposals.id) as count, status')
            ->where('status', '!=', Proposal::DRAFT)
            ->groupBy('status')
            ->get()
            ->toArray();

        $default = ['sent' => 0, 'viewed' => 0, 'accepted' => 0, 'rejected' => 0];

        $proposals = array_column($proposals, 'count', 'status');

        return array_merge($default, $proposals);
    }

    public function applyFilters($query, $filters)
    {

        if (ine($filters, 'salesman_proposal')) {
            $query->whereHas('job', function ($query) use ($filters) {
                $query->attachAwardedStage(false);
                $this->applyJobFilters($query, $filters);
            });
        }
    }

    protected function applyJobFilters($query, $filters = [])
    {
        // check for restricted workflow
        $query->own();

        if (ine($filters, 'exclude_job_types')) {
            $query->excludeWorkTypes($filters['exclude_job_types']);
        }

        // sale performance jobs list for customer rep or estimator..
        if (ine($filters, 'sales_performance_for') && ine($filters, 'user_id')) {
            $query->where(function ($query) use ($filters) {
                $for = (array)$filters['sales_performance_for'];
                $userId = (array)$filters['user_id'];

                if (in_array('customer_rep', $for)) {
                    $query->whereIn('jobs.customer_id', function ($query) use ($userId) {
                        $query->select('id')->from('customers')
                            ->whereIn('rep_id', (array)$userId);
                    });
                }

                if (in_array('estimator', $for)) {
                    $query->orWhereIn('jobs.id', function ($query) use ($userId) {
                        $query->selectRaw("COALESCE(jobs.parent_id, job_estimator.job_id)")
                            ->from('job_estimator')
                            ->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
                            ->whereIn('rep_id', (array)$userId);
                    });
                }
            });
        }

        # set deafult (customer_reps and estimators)
        if( (ine($filters, 'sales_performance_for')
            && ine($filters, 'user_id')
            && !in_array('customer_rep', (array)$filters['sales_performance_for'])
            && !in_array('estimator', (array)$filters['sales_performance_for']) )
            ||(!ine($filters, 'sales_performance_for') && ine($filters, 'user_id'))
        ) {
            $query->where(function($query) use($filters) {
                $query->whereIn('jobs.customer_id', function($query) use($filters){
                    $query->select('id')->from('customers')
                    ->whereIn('rep_id', (array)$filters['user_id']);
                })->orWhereIn('jobs.id', function($query) use($filters) {
                    $query->selectRaw("COALESCE(jobs.parent_id, job_estimator.job_id)")
                        ->from('job_estimator')
                        ->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
                        ->whereIn('rep_id', (array)$filters['user_id']);
                });
            });
        }

        # date range filters
        if (ine($filters, 'start_date') || ine($filters, 'end_date') || ine($filters, 'date_range_type')) {
            $startDate = isSetNotEmpty($filters, 'start_date') ?: null;
            $endDate = isSetNotEmpty($filters, 'end_date') ?: null;
            $type = isSetNotEmpty($filters, 'date_range_type') ?: 'job_created_date';

            switch ($type) {
                case 'job_created_date':
                    $query->jobCreatedDate($startDate, $endDate);
                    break;
                case 'job_stage_changed_date':
                    $query->jobStageChangedDate($startDate, $endDate);
                break;
                case 'job_completion_date':
                    $query->jobCompletionDate($startDate, $endDate);
                break;
                case 'contract_signed_date':
                    $query->contractSignedDate($startDate, $endDate);
                break;
                case 'job_awarded_date':
                    $query->closedJobs($startDate, $endDate);
                    break;
                case 'job_updated_date':
                    $query->jobUpdatedDate($startDate, $endDate);
                break;
                case 'job_lost_date':
                    $query->lostJob($startDate, $endDate);
                    break;
            }

            if (!in_array($type, ['job_lost_date'])) {
                $query->excludeLostJobs();
            }
        }


        if (ine($filters, 'with_archived')) {
            $query->withArchived();
        } elseif (ine($filters, 'only_archived')) {
            $query->onlyArchived();
        } else {
            $query->withoutArchived();
        }

        //bid propsal
        if (ine($filters, 'for_bid_proposal') || ine($filters, 'for_accepted_proposal')) {
            $query->wherehas('proposals', function ($query) use ($filters) {
                if (ine($filters, 'for_bid_proposal')) {
                    $query->bidProposal();
                } elseif (ine($filters, 'for_accepted_proposal')) {
                    $query->accepted();
                }
            });
        }

        //exclude bad leads
        if (ine($filters, 'exclude_bad_leads')) {
            $query->excludeBadLeads();
        }
    }
}
