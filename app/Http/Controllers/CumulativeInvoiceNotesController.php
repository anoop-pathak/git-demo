<?php
namespace App\Http\Controllers;

use App\Transformers\CumulativeInvoiceNotesTransformer;
use App\Repositories\CumulativeInvoiceNoteRepository;
use Sorskod\Larasponse\Larasponse;
use App\Services\Contexts\Context;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CumulativeInvoiceNote;
use App\Models\ApiResponse;
use App;
use App\Repositories\JobRepository;
use Exception;

class CumulativeInvoiceNotesController extends ApiController
{

	/**
	 * Vendor Repo
	 * @var \JobProgress\Repositories\CumulativeInvoiceNoteRepository
	 */
	protected $repo;

	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	protected $response;

	/**
	 * Set Company Scope
	 * @return company scope
	 */
	protected $scope;

	public function __construct(Larasponse $response, CumulativeInvoiceNoteRepository $repo, Context $scope)
	{
		$this->repo = $repo;
		$this->scope = $scope;
		$this->response = $response;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	/**
	 * Store a newly created resource in storage.
	 * POST /cumulative_invoice/note
	 *
	 * @return Response
	 */
	public function store($jobId)
	{
		$input = Request::only('note');
		$validator = Validator::make($input, CumulativeInvoiceNote::getCreateRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		$job = App::make(JobRepository::class)->getById($jobId);
		try {
			$cumulativeInvoiceNote = $this->repo->createCumulativeInvoiceNotes($job, $input['note']);

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Cumulative Invoice Note']),
				'data' => $this->response->item($cumulativeInvoiceNote, new CumulativeInvoiceNotesTransformer)
			]);
		}catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function get_notes($jobId) {
		$jobNote = $this->repo->getJobNote($jobId);

		return ApiResponse::success([
			'data' =>  ($jobNote) ? $this->response->item($jobNote, new CumulativeInvoiceNotesTransformer) : null
		]);
	}
}