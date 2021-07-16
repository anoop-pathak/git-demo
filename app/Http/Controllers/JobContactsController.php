<?php

namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Services\Grid\CommanderTrait;
use App\Repositories\ContactRepository;
use App\Transformers\JobContactTransformer;
use App\Exceptions\PrimaryAttributeCannotBeMultipleException;
use App\Exceptions\InvalidContactIdsException;
use App\Exceptions\EmptyFormSubmitException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\ContactNotesRepository;
use App\Transformers\ContactNoteTransformer;
use App\Models\ApiResponse;
use App\Models\Contact;
use App\Models\Job;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Request;

class JobContactsController extends ApiController
{
    use CommanderTrait;
    protected $response;
	protected $repo;
	protected $contactNoteRepo;

	public function __construct(Larasponse $response, ContactRepository $repo, ContactNotesRepository $contactNoteRepo)
	{
		$this->response = $response;
		$this->repo = $repo;
		$this->contactNoteRepo = $contactNoteRepo;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

    /**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();

		try {
			$contacts = $this->repo->getFilteredContacts($input, Contact::TYPE_JOB);

			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

			if(!$limit) {
				$contacts = $contacts->get();
				return ApiResponse::success($this->response->collection($contacts, new JobContactTransformer));
			}

			$contacts = $contacts->paginate($limit);

			return ApiResponse::success($this->response->paginatedCollection($contacts, new JobContactTransformer));
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Exception::all();
		$validator = Validator::make($input, Contact::getJobRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		if(!ine($input, 'type')) {
			$input['type'] = Contact::TYPE_JOB;
		}

		DB::beginTransaction();
		try {
			$contact = $this->execute("\App\Commands\ContactCreateCommand", ['input' => $input, $input['job_id']]);

			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Job contact']),
				'data' => $this->response->item($contact, new JobContactTransformer)
			]);
		} catch (EmptyFormSubmitException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (InvalidContactIdsException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$contact = $this->repo->getJobContactById($id);

		return ApiResponse::success(['data' => $this->response->item($contact, new JobContactTransformer)]);
	}

	/**
	 * Update the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$contact = $this->repo->getJobContactById($id);

		$input = Request::all();
		$input['id'] = $id;

		$validator = Validator::make($input, Contact::getValidationRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		DB::beginTransaction();
		try {

			$contact = $this->execute("\App\Commands\ContactUpdateCommand", ['input' => $input]);

			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Job Contact']),
				'data' => $this->response->item($contact, new JobContactTransformer)
			]);
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    /**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$contact = $this->repo->getJobContactById($id);
		try {
			$contact->delete();

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Job Contact']),
			]);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Link Specified Contact to Job
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function linkCompanyContact($jobId)
	{
		$input = Request::all();

		$validator = Validator::make($input, ['contact_id' => 'required']);

		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try{
			$contact = $this->repo->linkCompanyContactWithJob($jobId, $input['contact_id'], ine($input, 'is_primary'));

			return ApiResponse::success([
				'message' => trans('response.success.assigned', ['attribute' => 'Company contact']),
				'data' => $this->response->item($contact, new JobContactTransformer)
			]);
		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => $e->getModel()]));
		} catch (InvalidContactIdsException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (PrimaryAttributeCannotBeMultipleException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * UnLink Specified Contact to Job
	 *
	 * @param  int  $jobId | ID of a Job
	 * @return Response
	 */
	public function unlinkContact($jobId)
	{
		$job = Job::where('company_id', getScopeId())
			->findOrFail($jobId);

		$input = Request::all();

		$validator = Validator::make($input, [
			'contact_id' => 'required',
		]);

		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$contact = $this->repo->getCompanyContactById($input['contact_id']);

		try {
			$this->repo->unlinkContactWithJob($job, $contact);

			return ApiResponse::success([
				'message' => 'The company contact is removed from the job.',
			]);
		} catch(ModelNotFoundException $e){
			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Company Contact']));
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Update the specified Contact Note in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function updateNote($contactId, $noteId)
	{
		$input = Request::all();
		$validator = Validator::make($input, ['note' => 'required']);
		if ($validator->fails()) {
		    return ApiResponse::validation($validator);
		}
		$contact = $this->repo->getJobContactById($contactId);
		$note = $this->contactNoteRepo->getById($noteId);

		if ($note->contact_id != $contactId) {
			return ApiResponse::errorGeneral(trans('validation.not_in', ['attribute' => 'Note Id']));
		}
		try {
		    $note = $this->contactNoteRepo->updateNote($input['note'], $note);
		    return ApiResponse::success([
		        'message' => trans('response.success.updated', ['attribute' => 'Contact note']),
		        'data' => $this->response->item($note, new ContactNoteTransformer)
		    ]);

		} catch (Exception $e) {
		    return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}
