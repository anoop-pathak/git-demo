<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Http\Controllers\ApiController;
use App\Models\ApiResponse;
use App\Http\CustomerWebPage\Transformers\JobsTransformer;
use Sorskod\Larasponse\Larasponse;
use Request;
use App\Repositories\JobRepository;
use Illuminate\Http\Request as RequestClass;

class JobsController extends ApiController
{
    protected $response;
    protected $repo;

    public function __construct(Larasponse $response, JobRepository $repo)
    {
        parent::__construct();

        $this->response = $response;
        $this->repo = $repo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function getJob(RequestClass $request)
    {
        $jobToken = getJobToken($request);

		try{
            $job = $this->repo->getByShareToken($jobToken);

            return ApiResponse::success([
                'data' => $this->response->item($job, new JobsTransformer)
            ]);

		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}
