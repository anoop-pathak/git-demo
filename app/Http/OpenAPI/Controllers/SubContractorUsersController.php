<?php

namespace App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Repositories\LabourRepository;
use App\Http\OpenAPI\Transformers\SubContractorsTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;

use App\Http\Controllers\ApiController;


class SubContractorUsersController extends ApiController
{

    /* Larasponse class Instance */
    protected $response;

    /* Labour Repository */
    protected $repo;


    public function __construct(
        LabourRepository $repo,
        Larasponse $response
    ) {

        $this->repo = $repo;
        $this->response = $response;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /sub_contractors
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $subContractors = $this->repo->getLabours($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
           $limit = 10;
        }

        if($limit > 100) {
            $limit = 100;
        }

        $subContractors = $subContractors->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($subContractors, new SubContractorsTransformer));
    }
}
