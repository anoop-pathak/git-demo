<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\CompanyTrade;
use App\Models\Job;
use App\Models\SetupAction;
use App\Models\Trade;
use App\Services\Contexts\Context;
use App\Transformers\TradesTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Exception;

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
     * Display a listing of the resource.
     * GET /tradeassignments
     *
     * @return Response
     */
    public function index()
    {
        switchDBToReadOnly();
        $company = Company::find($this->scope->id());

        if (!$company) {
            return ApiResponse::errorNotFound(Lang::get('response.error.not_found', ['attribute' => 'Company']));
        }

        $data['trades'] = $company->trades->pluck('name', 'id')->toArray();
        $data['job_types'] = $company->jobTypes->pluck('id')->toArray();

        switchDBToReadWrite();

        return ApiResponse::success([
            'data' => $data
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /tradeassignments
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('trades','job_types');
		$validator = Validator::make($input, Company::getAssignRules());
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

        try {
			$company = Company::find($this->scope->id());
			$newTrades = $input['trades'];
			// check for assigned trades from trades difference..
			$assignedTrades = $this->getAssignedFromTradesDifference($company,$newTrades);
			if($assignedTrades) {
				// add assigned trades into new trades. because assigned trades can't be removed..
				$newTrades = array_merge($newTrades,$assignedTrades);
			}

			$company->trades()->detach();
			$company->trades()->attach(array_unique($newTrades));
			$company->jobTypes()->detach();
			if(isset($input['job_types']) && !empty($input['job_types'])) {
				$company->jobTypes()->attach($input['job_types']);
			}

			//CHECK-LIST TRADE ASSIGNMENT..
			$this->checkListCompletedActions($company->id,SetupAction::TRADE_TYPES);

			//create measurment
			$this->createMeasurementAttributes($company);

			if($assignedTrades) {
				$assignedTradesList = Trade::whereIn('id',$assignedTrades)->pluck('name')->toArray();

				return ApiResponse::errorGeneral(
					trans('response.error.trades_unable_to_delete', ['trades' => implode(',', $assignedTradesList)])
				);
			}

			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.trades_assigned')
			]);
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

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
                ->leftJoin(DB::raw("(select coalesce(jobs.parent_id, job_id) as job_id, job_trade.trade_id from job_trade join jobs on jobs.id=job_trade.job_id where jobs.company_id = {$company->id}) as job_trade"), 'job_trade.trade_id', '=', 'trades.id')
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

    public function assignTradeColor()
    {
        $input = Request::onlyLegacy('trade_id', 'color');

        $validator = Validator::make($input, CompanyTrade::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $tradeColor = CompanyTrade::where('company_id', $this->scope->id())
            ->where('trade_id', $input['trade_id'])
            ->update(['color' => $input['color']]);

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Trade color']),
        ]);
    }

    /*************************Private Section************************************/

    private function checkListCompletedActions($companyId, $action)
    {
        try {
            $company = Company::findOrFail($companyId);

            //get product id from company's Subscription Model..
            $productId = $company->subscription->product_id;

            //get action from SetupAction Model..
            $action = SetupAction::productId($productId)->ActionName($action)->first();
            $companyActionsList = $company->setupActions()->pluck('setup_action_id')->toArray();

            //add this action in company)setup_action list if not added before..
            if (!in_array($action->id, $companyActionsList)) {
                $company->setupActions()->attach([$action->id]);
            }
        } catch (\Exception $e) {
            //handle exception..
        }
    }

    private function getAssignedFromTradesDifference($company, $newTrades)
    {
        $currentTrades = $company->trades->pluck('id')->toArray();
        $tradeDifference = array_diff($currentTrades, $newTrades);
        if (empty($tradeDifference)) {
            return false;
        }
        
        $companyJobsIds = Job::where('company_id', $company->id)
            ->join('customers', 'customers.id', '=', 'jobs.customer_id')
            ->whereNull('jobs.deleted_at')
            ->where('jobs.multi_job', 0)
            ->whereNull('jobs.parent_id')
            ->whereNull('customers.deleted_at')
            ->select('jobs.id')
            ->pluck('id')
            ->toArray();

            $projectIds = Job::whereNotNull('jobs.parent_id')->whereIn('jobs.parent_id', function($query) use($company){
                    $query->select('id')->from('jobs')
                    ->where('company_id', $company->id)
                    ->where('multi_job', true)
                    ->whereNull('deleted_at');
                })->where('jobs.company_id', $company->id)
                ->join('customers', 'customers.id', '=', 'jobs.customer_id')
                ->whereNull('customers.deleted_at')
                ->whereNull('jobs.deleted_at')
                ->select('jobs.id')
                ->pluck('id')
                ->toArray();


        $companyJobsIds = array_merge($companyJobsIds, $projectIds);


        $assignedTrades = DB::table('job_trade')->whereIn('job_id', $companyJobsIds)
            ->whereIn('trade_id', $tradeDifference)
            ->pluck('trade_id')
            ->toArray();

        if (empty($assignedTrades)) {
            return false;
        }
        return $assignedTrades;
    }

    public function createMeasurementAttributes($company)
    {
        try {
            $app = App::make('App\Services\Measurement\MeasurementAttributeService');
            $app->createNewAttributes($company);
            return true;
        } catch (\Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /********** Private Functions **********/

     private function getIncludes($input)
    {
        $with = ['workTypes'];
        if(!ine($input, 'includes') || (!is_array($input['includes']))) return $with;
        if(in_array('attributes', $input['includes'])) {
            $with['measurementAttributes'] = function($query) {
    			$query->where('company_id', '=', getScopeId());
    		};
    		$with['measurementAttributes.subAttributes'] = function($query) {
    			$query->where('company_id', '=', getScopeId());
    		};
        }
        return $with;
    }
}
