<?php

namespace App\Services\WorkCrewNote;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobWorkflowHistory;
use App\Repositories\WorkCrewNoteRepository;
use App\Services\Pdf\PdfService;
use Illuminate\Support\Facades\Lang;

class WorkCrewNoteService
{

    public function __construct(WorkCrewNoteRepository $repo, PdfService $pdfService)
    {
        $this->repo = $repo;
        $this->pdfService = $pdfService;
    }

    /**
     * Work Crew Note Save
     * @param  int $jobId Job Id
     * @param  string $note Work Crew Note
     * @param  int $createdBy User Id
     * @param  array $reps Reps ids
     * @param  array $labours Labour Ids
     * @param  array $sub Sub Ids
     * @return work crew note
     */
    public function save($jobId, $note, $createdBy, $reps = [], $labours = [], $sub = [])
    {
        $wcNote = $this->repo->save($jobId, $note, $createdBy);

        if (!empty($reps)) {
            $wcNote->reps()->attach($reps, ['job_id' => $wcNote->job_id]);
        }

        if (!empty($sub)) {
            $wcNote->subContractors()->attach($sub, ['job_id' => $wcNote->job_id]);
        }

        return $wcNote;
    }

    /**
     * Work Crew Note Update
     * @param  Instance $wcNote Work Crew Note
     * @param  string $note Work Crew Note
     * @param  int $createdBy User Id
     * @param  array $reps Reps ids
     * @param  array $labours Labour Ids
     * @param  array $sub Sub Ids
     * @return work crew note
     */
    public function update($wcNote, $note, $reps = [], $labours = [], $sub = [])
    {
        $wcNote = $this->repo->update($wcNote, $note);

        $wcNote->detachAllEntitiy();

        if (!empty($reps)) {
            $wcNote->reps()->attach($reps, ['job_id' => $wcNote->job_id]);
        }

        if (!empty($sub)) {
            $wcNote->subContractors()->attach($sub, ['job_id' => $wcNote->job_id]);
        }

        return $wcNote;
    }

    /**
     * Delete Work Crew Note
     * @param  Instamce $wcNote work crew note
     * @return work crew note
     */
    public function delete($wcNote)
    {
        $wcNote->delete();
        $wcNote->job->updateJobUpdatedAt();

        return $wcNote;
    }

    /**
     * Get single work crew note
     * @param  Int $id Work Crew Note Id
     * @return work crew note
     */
    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    /**
     * Get filtered notes
     * @param  array $filters filters
     * @return QueryBuilder
     */
    public function getFilteredNotes($jobId, $filters = [])
    {
        return $this->repo->getFilteredNotes($jobId, $filters);
    }

    /**
     * Print Multiple Notes
     * @param  Job $job Job
     * @param  array $filters Array of Filters
     * @return Response
     */
    public function printMultipleNotes($job, $filters = [])
    {
        $company = Company::find(getScopeId());

        $notes = $this->getFilteredNotes($job->id, $filters)->get();
        $completedStages = JobWorkflowHistory::where('job_id', $job->id)->pluck('created_at', 'stage')->toArray();
        $contents = \view('jobs.multiple_job_work_crew_notes', [
            'work_crew_notes' => $notes,
            'company' => $company,
            'completed_stages' => $completedStages,
            'job' => $job,
            'company_country_code' => $company->country->code
        ])->render();

        $this->pdfService->create($contents, $name = 'job_work_crew_notes', $filters);

        if (!ine($filters, 'save_as_attachment')) {
            return $this->pdfService->getPdf();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.file_uploaded'),
            'file' => $this->pdfService->getAttachment(),
        ]);
    }

    /**
     * Print Single Work Crew Note
     * @param  String $note Note
     * @param  array $filters Array of filters
     * @return Response
     */
    public function printSingleNote($note, $filters = [])
    {
        $company = Company::find(getScopeId());
        $contents = \view('jobs.single_work_crew_note', [
            'work_crew_note' => $note,
            'company' => $company,
            'job' => $note->job,
            'company_country_code' => $company->country->code
        ])->render();

        $this->pdfService->create($contents, $name = 'job_work_crew_note', $filters);

        if (!ine($filters, 'save_as_attachment')) {
            return $this->pdfService->getPdf();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.file_uploaded'),
            'file' => $this->pdfService->getAttachment(),
        ]);
    }
}
