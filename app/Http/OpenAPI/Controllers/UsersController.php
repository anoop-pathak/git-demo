<?php

namespace App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Contexts\Context;
use App\Http\OpenAPI\Transformers\UsersTransformer;
use Sorskod\Larasponse\Larasponse; 
use App\Services\Users\UserService;
use App\Exceptions\InvalidDivisionException;
use Illuminate\Support\Facades\Response; 
use App\Http\Controllers\ApiController;
use Request;

// use User;

class UsersController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /users
     *
     * @return Response
     */
    protected $response;
    protected $user;
    protected $repo;
    protected $scope;
    protected $service;

    public function __construct(User $user, Larasponse $response, UserRepository $repo, Context $scope, UserService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->scope = $scope;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        $this->user = $user;
        $this->repo = $repo;

        $this->middleware('company_scope.ensure', ['only' => ['index']]);
    }

    /**
     *  get company users list according to company wise.
     *
     * @access public
     * @return json of company users listing.
     */
    public function index()
    {

        $input = Request::all();
        try{
            $users = $this->repo->getFilteredUsers($input);

            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $users    = $users->get();
                $response = $this->response->collection($users, new UsersTransformer);
            } else {
                $users    = $users->paginate($limit);
                $response =  $this->response->paginatedCollection($users, new UsersTransformer);
            }

            if(\Auth::user()->isSubContractorPrime() && !ine($input, 'exclude_sub_user')) {
                $response['data'] = $this->addCurrentUserInResponse($response['data']);
            }

            return ApiResponse::success($response);

        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

}
