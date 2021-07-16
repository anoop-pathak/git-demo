<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Repositories\ContactNotesRepository;
use App\Transformers\ContactNoteTransformer;
use App\Repositories\ContactRepository;
use Request;
use App\Http\Controllers\ApiController;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\ContactNote;

class ContactNotesController extends ApiController
{

	protected $response;
	protected $repo;
	protected $contactRepo;

	public function __construct(
		Larasponse $response,
		ContactNotesRepository $repo,
		ContactRepository $contactRepo
	)
	{
		$this->response = $response;
		$this->repo = $repo;
		$this->contactRepo = $contactRepo;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	/**
	 * Display a listing of the Contact Notes.
	 *
	 * @return Response
	 */
	public function index($contactId)
	{
		$input = Request::all();
		$contact = $this->contactRepo->getById($contactId);
		$input['contact_id'] = $contactId;
		$notes = $this->repo->getFiltredContactNotes($input);
		$limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');
		$notes = $notes->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($notes, new ContactNoteTransformer));
	}

	/**
	 * Store a newly created Contact Note in storage.
	 *
	 * @return Response
	 */
	public function store($contactId)
	{
		$contact = $this->contactRepo->getById($contactId);

		$input = Request::all();

		$validator = Validator::make($input, ContactNote::getRules());

		if ($validator->fails()) {
		    return ApiResponse::validation($validator);
		}

		try {
			$notes = $this->repo->saveNote($input['notes'], $contactId);
			$data = $this->response->collection($notes, new ContactNoteTransformer);
			$data['message'] = trans('response.success.added', ['attribute' => 'Contact note']);

			return ApiResponse::success($data);
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}


	/**
	 * Display the specified Contact Note.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($contactId, $noteId)
	{
		$contact = $this->contactRepo->getById($contactId);

		$note = $this->repo->getById($noteId);

		return ApiResponse::success(['data' => $this->response->item($note, new ContactNoteTransformer)]);
	}

	/**
	 * Update the specified Contact Note in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($contactId, $noteId)
	{
		$contact = $this->contactRepo->getById($contactId);
		$note = $this->repo->getById($noteId);

		$input = Request::all();
		$validator = Validator::make($input, ['note' => 'required']);
		if ($validator->fails()) {
		    return ApiResponse::validation($validator);
		}

		try {
		    $note = $this->repo->updateNote($input['note'], $note);
		    return ApiResponse::success([
		        'message' => trans('response.success.updated', ['attribute' => 'Contact note']),
		        'data' => $this->response->item($note, new ContactNoteTransformer)
		    ]);

		} catch (\Exception $e) {
		    return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}


	/**
	 * Remove the specified Contact Note from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($contactId, $noteId)
	{
		$contact = $this->contactRepo->getById($contactId);
		$note = $this->repo->getById($noteId);

		$note->delete();

		return ApiResponse::success([
			'message' => trans('response.success.deleted', ['attribute' => 'Contact note']),
		]);
	}
}