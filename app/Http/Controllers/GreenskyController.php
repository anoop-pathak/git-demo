<?php
namespace App\Http\Controllers;

use App\Repositories\GreenskyRepository;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\GreenskyTransformer;
use App\Repositories\JobRepository;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ApiResponse;
use App\Models\Greensky;
use App\Models\Job;

class GreenskyController extends ApiController {

 	public function __construct(GreenskyRepository $repo, Larasponse $response, JobRepository $jobRepo)
	{
		$this->repo = $repo;
		$this->response = $response;
		$this->jobRepo = $jobRepo;

 		parent::__construct();
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();
		$validator = Validator::make($input, ['job_id' => 'required']);
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

 		$job = $this->jobRepo->getById($input['job_id']);
		$greensky = $this->repo->getListing($job->id);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

 		if(!$limit) {
			$greensky = $greensky->get();

 			return ApiResponse::success($this->response->collection($greensky, new GreenskyTransformer));
		}

 		$greensky = $greensky->paginate($limit);

 		return ApiResponse::success($this->response->paginatedCollection($greensky, new GreenskyTransformer));
	}

 	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, Greensky::getGreenskyRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

 		$job = $this->jobRepo->getById($input['job_id']);

 		try {
			$greensky = $this->repo->saveOrUpdate($job, $input);

 			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Greensky']),
				'data' => $this->response->item($greensky, new GreenskyTransformer)
			]);
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

 	/**
	 * save or update greensky detail by job share token
	 * POST - /greensky/{job_share_token}
	 * @param  String | $jobShareToken | Job share token
	 * @return response
	 */
	public function saveOrUpdateByJobToken($jobShareToken)
	{
		$input = Request::all();
		$validator = Validator::make($input, [
			'application_id' => 'required',
		]);
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

 		$job = Job::where('share_token', $jobShareToken)->firstOrFail();

 		try {
			$greensky = $this->repo->saveOrUpdate($job, $input);

 			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Greensky']),
				'data' => $this->response->item($greensky, new GreenskyTransformer)
			]);
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

 	/**
	 * get greensky detail by job share token
	 * GET - /greensky/{job_share_token}
	 * @param  String | $jobShareToken | Job share token
	 * @return response
	 */
	public function getByJobToken($jobShareToken)
	{
		$input = Request::all();
		$job = Job::where('share_token', $jobShareToken)->firstOrFail();

 		try {
			$greensky = $this->repo->getListing($job->id);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

 			if(!$limit) {
				$greensky = $greensky->get();

 				return ApiResponse::success($this->response->collection($greensky, new GreenskyTransformer));
			}
			$greensky = $greensky->paginate($limit);

 			return ApiResponse::success($this->response->paginatedCollection($greensky, new GreenskyTransformer));
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}
