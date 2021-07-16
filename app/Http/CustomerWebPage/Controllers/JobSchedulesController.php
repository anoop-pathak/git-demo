<?php
namespace App\Http\CustomerWebPage\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use Validator;
use App\Http\CustomerWebPage\Transformers\JobScheduleTransformer;
use Exception;
use App\Models\JobSchedule;
use App\Models\Job;
use App\Repositories\JobRepository;
use App\Models\ApiResponse;
use App\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request as RequestClass;

class JobSchedulesController extends ApiController
{
	protected $response;

	public function __construct(
		Larasponse $response,
		JobRepository $jobRepo)
	{
		$this->response = $response;
		$this->jobRepo = $jobRepo;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));	
		}
		parent::__construct();
	}

	public function getJobSchedules(RequestClass $request)
	{
		$jobToken = getJobToken($request);

		try {
			$job = $this->jobRepo->getByShareToken($jobToken);
			$input = Request::all();

			if($job->isMultiJob() && ine($input, 'project_id')) {
				$job = Job::where('id', $input['project_id'])->where('parent_id', $job->id)->firstOrFail();
			}

			$schedules = $job->schedules;
			$response = $this->response->collection($schedules, new JobScheduleTransformer);
			
			return ApiResponse::success($response);
		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound('Project Not Found.');
		} catch(\Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}