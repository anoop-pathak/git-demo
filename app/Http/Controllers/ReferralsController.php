<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Customer;
use App\Models\Referral;
use App\Repositories\CustomerListingRepository;
use App\Repositories\ReferralRepository;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class ReferralsController extends Controller
{

    protected $repo;
    protected $customerRepo;
    protected $response;

    public function __construct(
        ReferralRepository $repo,
        CustomerListingRepository $customerRepo,
        Larasponse $response
    ) {

        $this->repo = $repo;
        $this->customerRepo = $customerRepo;
        $this->response = $response;
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /referrals
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::onlyLegacy('limit', 'include_system_referral');

        $referralsQuery = $this->repo->getReferrals($input);
        // return ApiResponse::success(['data' => $referralsQuery->get()]);

        $limit = ine($input, 'limit') ? (int)$input['limit'] : 0;

        if (!$limit) {
            $referrals = $referralsQuery->get();

            return ApiResponse::success($this->response->collection($referrals));
        }

        $referrals = $referralsQuery->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($referrals));
    }

    /**
     * Store a newly created resource in storage.
     * POST /referrals
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('name');

        $validator = Validator::make($input, Referral::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $referral = $this->repo->saveReferral($input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Referral']),
                'data' => $referral
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'));
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /referrals/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $referral = $this->repo->getById($id);
        $input = Request::onlyLegacy('name');
        $validator = Validator::make($input, Referral::getRules());

        if(($referral) && (!$referral->company_id)) {
            return ApiResponse::errorGeneral(Lang::get('response.error.update_system_referral'));
        }

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($referral->update($input)) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Referral']),
                'data' => $referral
            ]);
        }

        return ApiResponse::errorInternal(Lang::get('response.error.internal'));
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /referrals/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $referral = $this->repo->getById($id);

        if(($referral) && (!$referral->company_id)) {
            return ApiResponse::errorGeneral(Lang::get('response.error.delete_system_referral'));
        }

        if (!$referral->delete()) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'));
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.deleted', ['attribute' => 'Referral'])
        ]);
    }

    /**
     * Get Referrals with customer count
     * @return [json] [customer count]
     */
    public function withCount()
    {
        $companyId = getScopeId();

        $referral = $this->repo->getReferrals();

        $customerQuery = Customer::whereCompanyId($companyId);
        $customerQuery = $customerQuery->whereReferredByType('referral')
            ->groupBy('referred_by')
            ->own()
            ->selectRaw('COUNT(id) as customer_count, referred_by');


        $customerSubQuery = generateQueryWithBindings($customerQuery);

        $referral->leftJoin(
            DB::raw("($customerSubQuery) as customers"),
            'customers.referred_by',
            '=',
            'referrals.id'
        );

        $referrals = $referral->select(
            'name',
            'id',
            DB::raw('IFNULL(customers.customer_count, 0) as customer_count')
        )
            ->get()
            ->toArray();

        // foreach ($referrals as $key => $value) {
        // 	$customerCount =  $this->customerRepo->getFilteredCustomers([
        //              'referred_by_type' => 'referral',
        // 				'referred_by' => $value['id']
        // 			])->get()->count();

        // 	$referrals[$key]['customer_count'] = $customerCount;
        // }

        $data['refferals'] = $referrals;
        $filters['referred_by_type'] = 'customer';

        $data['customer']['customer_count'] = $this->customerRepo->getCustomerQeuryBuilder($filters)->count();

        $filters['referred_by_type'] = 'other';
        $data['other']['customer_count'] = $this->customerRepo->getCustomerQeuryBuilder($filters)->count();

        return $data;
    }
}
