<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\MarketSourceSpent;
use App\Repositories\MarketSourceSpentRepository;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class MarketSourceSpentController extends Controller
{

    protected $repo;
    protected $response;

    public function __construct(MarketSourceSpentRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
        parent::__construct();
    }

    /**
     * Det all market source spents
     * GET /market_source_spents
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $spent = $this->repo->getSpents($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $spent = $spent->get();

            return ApiResponse::success($this->response->collection($spent));
        }

        $spent = $spent->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($spent));
    }

    /**
     * Store market source spent
     * POST /market_source_spents
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, MarketSourceSpent::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $spent = $this->repo->saveSpent($input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Market Source Spent']),
                'data' => $spent,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update market source spents
     * PUT /market_source_spents/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('referral_id', 'amount', 'description', 'date');

        $validator = Validator::make($input, MarketSourceSpent::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $spent = $this->repo->getById($id);

        try {
            $spent->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Market Source Spent']),
                'data' => $spent
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Remove market source spents
     * DELETE /market_source_spents/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $spent = $this->repo->getById($id);

        try {
            $spent->delete();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Market Source Spent']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get specific market source spent
     * GET /market_source_spents/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $spent = $this->repo->getById($id);

        return ApiResponse::success(['data' => $spent]);
    }

    /**
     * Get Referrals with customer count
     * @return [json] [customer count]
     */
    /*public function withCount()
	{
		$referral = $this->repo->getReferrals();
		$referrals = $referral->select('name', 'id')->get()->toArray();
		
		foreach ($referrals as $key => $value) {
			$customerCount =  $this->customerRepo->getFilteredCustomers([
						'referred_by_type' => 'referral', 
						'referred_by' => $value['id']
					])->get()->count();

			$referrals[$key]['customer_count'] = $customerCount;
		}

		$data['refferals'] = $referrals;
		$data['customer']['customer_count'] = $this->customerRepo->getFilteredCustomers([
			'referred_by_type' => 'customer'])->get()->count();
		$data['other']['customer_count'] = $this->customerRepo->getFilteredCustomers([
								'referred_by_type' => 'other'])->get()->count();
		
		return $data;
	}*/
}
