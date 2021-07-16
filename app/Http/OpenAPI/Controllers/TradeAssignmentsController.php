<?php

namespace  App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Models\Company; 
use App\Models\Trade;
use App\Services\Contexts\Context;
use App\Http\OpenAPI\Transformers\TradesTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang; 
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;

class TradeAssignmentsController extends ApiController
{

    protected $scope;
    protected $response;

    public function __construct(Context $scope, Larasponse $response)
    {
        $this->scope = $scope;
        $this->response = $response;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
        $this->middleware('company_scope.ensure', ['only' => ['index', 'store']]);
    }

    /**
     * Display a listing of the trades.
     * @return Response
     */
    
    public function companies_trades_list()
    {
        switchDBToReadOnly();
        $company = Company::find($this->scope->id());

        if (!$company) {
            return ApiResponse::errorNotFound(Lang::get('response.error.not_found', ['attribute' => 'Company']));
        }

        $inputs = Request::onlyLegacy('trade_ids', 'unassign_work_types', 'includes', 'division_ids');

        $query = $company->trades();

        // apply trade ids filter
        if (ine($inputs, 'trade_ids')
            && is_array($inputs['trade_ids'])) {
            $query->whereIn('trades.id', array_filter($inputs['trade_ids']));
        }

        $tradesList = $query->get()->pluck('id')->toArray();
        $with = $this->getIncludes($inputs);

        if(ine($inputs, 'includes') &&  in_array('jobs_count', $inputs['includes'])) {
            $jobRepo = \App::make('App\Repositories\JobsListingRepository');
            $filters['include_projects'] = true;

			if(ine($inputs, 'division_ids')){
                $filters['division_ids'] = $inputs['division_ids'];
            }

            $jobsQueryBuilder = $jobRepo->getJobsQueryBuilder($filters);
            $jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);

            $trades = Trade::with($with)
                ->whereIn('trades.id', $tradesList)
                ->sortable()
                ->withColor()
                ->select('trades.*')
                ->leftJoin(DB::raw("(select coalesce(jobs.parent_id, job_id) as job_id, job_trade.trade_id from job_trade join jobs on jobs.id=job_trade.job_id) as job_trade"), 'job_trade.trade_id', '=', 'trades.id')
                ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'job_trade.job_id')
                ->addSelect(DB::raw('COUNT(Distinct COALESCE(jobs.parent_id, jobs.id)) as job_count'))
                ->orderBy('name', 'asc')
                ->groupBy('trades.id')
                ->get();
        } else {
           $trades = Trade::with($with)
                ->whereIn('trades.id', $tradesList)
                ->sortable()
                ->withColor()
                ->orderBy('name', 'asc')
                ->get();
        }

        $data = $this->response->collection($trades, new TradesTransformer);

        if (ine($inputs, 'unassign_work_types')) {
            $jobTypeRepo = App::make(\App\Repositories\JobTypesRepository::class);
            $workTypes = $jobTypeRepo->getJobTypes(['without_trade' => true])->get();
            $data['unassign_work_types'] = $workTypes;
        }

        switchDBToReadWrite();
        
        return ApiResponse::success($data);
    }


    /********** Private Functions **********/

     private function getIncludes($input)
    {
        $with = ['workTypes'];
        if(!ine($input, 'includes') || (!is_array($input['includes']))) return $with;
        if(in_array('attributes', $input['includes'])) {
            $with[] = 'measurementAttributes';
        }
        return $with;
    }
}
