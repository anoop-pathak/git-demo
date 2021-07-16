<?php
namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\JobFinancialNoteTransformer;
use App\Repositories\JobFinancialNotesRepository;
use App\Repositories\JobRepository;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\JobFinancialNote;

class JobFinancialNotesController extends ApiController
{
	protected $repo;
	protected $jobRepo;

	public function __construct(Larasponse $response, JobFinancialNotesRepository $repo,JobRepository $jobRepo) {

		$this->response = $response;
		$this->repo = $repo;
		$this->jobRepo = $jobRepo;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();

	}

	/**
	 * Display the specified resource.
	 * GET /jobs/{job_id}/financial_notes
	 *
	 * @param  int  $jobId
	 * @return Response
	 */
	public function show($jobId)
	{
		$job = $this->jobRepo->findById($jobId);
		$response = null;
		$financialNote = $job->jobFinancialNote;
		if($financialNote) {
			$response = $this->response->item($financialNote, new JobFinancialNoteTransformer);
		}

		return ApiResponse::success([
			'data' => $response
		]);
	}

	public function addJobFinancialNotes($jobId)
	{
		$input = Request::onlyLegacy('note');

		$validator = Validator::make($input, JobFinancialNote::getRules());
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		$job = $this->jobRepo->findById($jobId);

		$jobFinancialNote = $this->repo->addOrUpdateNote($jobId, $input['note']);

		return ApiResponse::success([
			'message' =>trans('response.success.added',['attribute' => 'Note']),
			'data' 	  => $this->response->item($jobFinancialNote, new JobFinancialNoteTransformer)
		]);
	}
}
