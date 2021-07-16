<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\WorkCrewNote;
use App\Repositories\JobRepository;
use App\Services\WorkCrewNote\WorkCrewNoteService;
use App\Transformers\WorkCrewNotesTransformer;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class WorkCrewNotesController extends ApiController
{

    protected $repo;

    public function __construct(Larasponse $response, WorkCrewNoteService $service, JobRepository $jobRepo)
    {
        $this->response = $response;
        $this->service = $service;
        $this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the work crew note.
     * GET /work_crew_notes
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $wcNotes = $this->service->getFilteredNotes($input['job_id'], $input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $wcNotes = $wcNotes->get();

            return ApiResponse::success($this->response->collection($wcNotes, new WorkCrewNotesTransformer));
        }
        $wcNotes = $wcNotes->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($wcNotes, new WorkCrewNotesTransformer));
    }

    /**
     * Store a newly created work crew note in storage.
     * POST /work_crew_notes
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('job_id', 'note', 'rep_ids', 'labour_ids', 'sub_contractor_ids');

        $validator = Validator::make($input, WorkCrewNote::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $wcNote = $this->service->save(
                $input['job_id'],
                $input['note'],
                \Auth::id(),
                $input['rep_ids'],
                $input['labour_ids'],
                $input['sub_contractor_ids']
            );

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Work Crew Note']),
                'work_crew_note' => $this->response->item($wcNote, new WorkCrewNotesTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified work crew note.
     * GET /work_crew_notes/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $wcNote = $this->service->getById($id);

        return ApiResponse::success([
            'work_crew_note' => $wcNote
        ]);
    }


    /**
     * Update the specified work crew note in storage.
     * PUT /work_crew_notes/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $wcNote = $this->service->getById($id);
        $input = Request::onlyLegacy('note', 'rep_ids', 'labour_ids', 'sub_contractor_ids');

        $validator = Validator::make($input, ['note' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $wcNote = $this->service->update(
                $wcNote,
                $input['note'],
                $input['rep_ids'],
                $input['labour_ids'],
                $input['sub_contractor_ids']
            );

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Work Crew Note']),
                'work_crew_note' => $this->response->item($wcNote, new WorkCrewNotesTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified work crew note from storage.
     * DELETE /work_crew_notes/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $wcNote = $this->service->getById($id);
        try {
            $this->service->delete($wcNote);

            return ApiResponse::success([
                'work_crew_note' => trans('response.success.deleted', ['attribute' => 'Work Crew Note'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Print Multiple Work Crew Note
     * Get /work_crew_notes/pdf_print
     * @return Response
     */
    public function printMultipleNotes()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->jobRepo->getById($input['job_id']);

        try {
            return $this->service->printMultipleNotes($job, $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Print Single Work Crew Note
     * Get /work_crew_notes/{id}/pdf_print
     * @return Response
     */

    public function singlePdfPrint($id)
    {
        $input = Request::all();
        $note = $this->service->getById($id);
        try {
            return $this->service->printSingleNote($note, $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }
}
