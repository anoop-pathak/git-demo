<?php

namespace App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Repositories\ReferralRepository;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Http\OpenAPI\Transformers\ReferralsTransformer;

class ReferralsController extends ApiController
{
    protected $repo;
    protected $response;

    public function __construct(ReferralRepository $repo, Larasponse $response)
    {
        parent::__construct();
        $this->repo = $repo;
        $this->response = $response;
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

        $limit = isset($input['limit']) ? $input['limit'] : \Config::get('jp.pagination_limit');

        $referrals = $referralsQuery->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($referrals, new ReferralsTransformer));
    }
}