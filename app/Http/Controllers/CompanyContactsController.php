<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\CompanyContact;
use App\Repositories\CompanyContactsRepository;
use App\Transformers\CompanyContactTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Services\Grid\CommanderTrait;
use App\Repositories\ContactRepository;
use App\Exceptions\PrimaryAttributeCannotBeMultipleException;
use App\Exceptions\InvalidContactIdsException;
use App\Exceptions\EmptyFormSubmitException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\ContactNotesRepository;
use App\Transformers\ContactNoteTransformer;
use App\Exceptions\InvalidTagIdsException;
use Exception;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class CompanyContactsController extends ApiController
{
    use CommanderTrait;

    protected $repo;
    protected $response;

    public function __construct(Larasponse $response, ContactRepository $repo, ContactNotesRepository $contactNoteRepo)
    {
        $this->response = $response;
        $this->repo = $repo;
		$this->contactNoteRepo = $contactNoteRepo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /companycontacts
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        try {
			$contacts = $this->repo->getFilteredContacts($input, Contact::TYPE_COMPANY);

			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

			if(!$limit) {
				$contacts = $contacts->get();
				return ApiResponse::success($this->response->collection($contacts, new CompanyContactTransformer));
			}

			$contacts = $contacts->paginate($limit);

			return ApiResponse::success($this->response->paginatedCollection($contacts, new CompanyContactTransformer));
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    /**
     * Store a newly created resource in storage.
     * POST /companycontacts
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, Contact::getValidationRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		if(ine($input, 'tag_ids')) {
			if(!$this->repo->validateTags($input['tag_ids'])) {

				return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'Tag(s) Id']));
			}
		}

		DB::beginTransaction();
		try {
			$input['type'] = Contact::TYPE_COMPANY;
			$contact = $this->execute("\App\Commands\ContactCreateCommand", ['input' => $input]);

			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Company Contact']),
				'data' => $this->response->item($contact, new CompanyContactTransformer)
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
     * GET /companycontacts/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $contact = $this->repo->getCompanyContactById($id);

		return ApiResponse::success(['data' => $this->response->item($contact, new CompanyContactTransformer)]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /companycontacts/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $companyContact = $this->repo->getById($id);
        $input = Request::all();
        $input['id'] = $id;

		$validator = Validator::make($input, Contact::getValidationRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		if(ine($input, 'tag_ids')) {
			if(!$this->repo->validateTags($input['tag_ids'])) {
				return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'Tag(s) Id']));
			}
		}

		DB::beginTransaction();
		try {
			$contact = $this->execute("\App\Commands\ContactUpdateCommand", ['input' => $input]);

			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Company Contact']),
				'data' => $this->response->item($contact, new CompanyContactTransformer)
			]);
		} catch (EmptyFormSubmitException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /companycontacts/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $contact = $this->repo->getCompanyContactById($id);
		try {
			$contact->delete();

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Company Contact']),
			]);
		} catch(Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
	 * Assign Tag to Contact
	 * Put /contacts/{id}/assign_tags
	 * @param  int  $id
	 * @return Response
	 */
	public function assignTags($contactId)
	{
		$input = Request::all();

		$validator = Validator::make($input, Contact::getTagValidationRules());

		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$contact = $this->repo->getCompanyContactById($contactId);
		try{
			$this->repo->assignTags($contact, $input['tag_ids']);

			return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Contact group(s)']),
			]);
		} catch(ModelNotFoundException $e){
			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Company Contact']));
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Multiple Company Contacts Delete
	 */
	public function multipleDelete()
	{
		$input = Request::all();

		$validator = Validator::make($input, [
			'ids' => 'required',
		]);

		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$contacts = Contact::where('type', Contact::TYPE_COMPANY)
				->where('company_id', getScopeId())
				->whereIn('id', $input['ids'])
				->get();

			if($contacts->count() != count(arry_fu($input['ids']))) {
				return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'contact id(s)']));
			}

			foreach ($contacts as $contact) {
				$contact->delete();
			}

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Company contact(s)']),
			]);
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
		$contact = $this->repo->getCompanyContactById($contactId);
		$note = $this->contactNoteRepo->getById($noteId);

		if ($note->contact_id != $contactId) {
			return ApiResponse::errorGeneral(trans('validation.not_in', ['attribute' => 'Note Id']));
		}
		try {
		    $note = $this->contactNoteRepo->updateNote($input['note'], $note);
		    return ApiResponse::success([
		        'message' => Lang::get('response.success.updated', ['attribute' => 'Contact note']),
		        'data' => $this->response->item($note, new ContactNoteTransformer)
		    ]);
		} catch (\Exception $e) {
		    return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	public function assignMultipleTags()
	{
		$input = Request::all();
		$validator = Validator::make($input, Contact::assignMultipleTagRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		DB::beginTransaction();
		try {
			$this->repo->assignMultipleTags($input['tag_ids'], $input['contact_ids']);

			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Contact group(s)']),
			]);
		} catch (InvalidContactIdsException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (InvalidTagIdsException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}
