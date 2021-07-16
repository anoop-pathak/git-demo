<?php
namespace App\Http\Controllers;

use App\Services\Contexts\Context;
use App\Repositories\CustomerRepository;
use App\Services\Resources\ResourceServices;
use App\Transformers\ResourcesTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Services\Grid\CommanderTrait;
use App\Exceptions\InvalidResourcePathException;
use Request;
use Exception;
use App\Models\ApiResponse;

class CustomerResourcesController extends ApiController
{
	use CommanderTrait;

    /**
	 * Customer Repo
	 * @var \JobProgress\Repositories\CustomerRepositories
	 */
	protected $repo;

	/**
	 * Display a listing of the resource.
	 * GET /customers
	 *
	 * @return Response
	 */
	protected $response;
	protected $scope;
	protected $resourceService;
	protected $customerListingRepo;

	public function __construct(Larasponse $response,
        CustomerRepository $repo,
        ResourceServices $resourceService,
        Context $scope)
    {

		$this->response = $response;
		$this->repo = $repo;
		$this->resourceService = $resourceService;
		$this->scope = $scope;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
    }

	/**
	 * Create root directories of customer if not exists and add default resource directories
	 *
	 * @param Integer $customerId
	 * @return Json
	 */
    public function getResources($customerId)
    {
		$input = Request::all();
		$input['customer_id'] = $customerId;
		$limit = isset($input['limit']) ? $input['limit'] : 0;

		try {
			$customerRootResource = $this->execute("\App\Commands\CustomerResourceCommand", ['input' =>$input]);

			$resources = $this->resourceService->getResources($customerRootResource->id, $input);
			$params = [
				'params' => $input
			];

			if(!$limit) {
				$resources = $resources->get();
				$data = $this->response->collection($resources, new ResourcesTransformer);
				return ApiResponse::success(array_merge($data, $params));

			}
			$resources = $resources->paginate($limit);
			$data = $this->response->paginatedCollection($resources, new ResourcesTransformer);
			return ApiResponse::success(array_merge($data, $params));
		}catch(InvalidResourcePathException $e){
			return ApiResponse::errorNotFound($e->getMessage());
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		return ApiResponse::errorInternal();
    }
}