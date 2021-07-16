<?php

namespace App\Repositories;

use App\Models\WorkCrewNote;


class WorkCrewNoteRepository extends AbstractRepository
{

    protected $scope;

    public function __construct(WorkCrewNote $model)
    {
        $this->model = $model;
    }

    /**
     * Work Crew Note Save
     * @param  Int $jobId Job Id
     * @param  String $note Job note
     * @param  Int $createdBy User id
     * @return work crew note
     */
    public function save($jobId, $note, $createdBy)
    {
        $wcNote = $this->model->create([
            'job_id' => $jobId,
            'note' => $note,
            'created_by' => $createdBy
        ]);

        $wcNote->job->updateJobUpdatedAt();
        return $wcNote;
    }

    /**
     * Work Crew Note Update
     * @param  Instance $wcNote Work Crew note
     * @param  String $note note
     * @return Work Crew Note
     */
    public function update($wcNote, $note)
    {
        $wcNote->update([
            'note' => $note
        ]);

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
        return $this->model->findOrFail($id);
    }

    /**
     * Get filtered notes
     * @param  array $filters filters
     * @return QueryBuilder
     */
    public function getFilteredNotes($jobId, $filters = [])
    {
        $notes = $this->make(['reps', 'subContractors']);
        $notes->sortable();

        if (!ine($filters, 'sort_by')) {
            $notes->orderBy('id', 'desc');
        }

        $notes->whereJobId($jobId);

        $this->applyFilters($notes, $filters);

        return $notes;
    }

    /**
     * Apply Filters
     * @param  array $filters filters
     * @return QueryBuilder
     */
    private function applyFilters($query, $filters)
    {
        // get only sub contractor's WC notes
        if (\Auth::user()->isSubContractorPrime()) {
            $query->subOnly(\Auth::id(), $filters['job_id']);
        }
        
        if (ine($filters, 'sub_ids')) {
            $query->whereIn('id', function ($query) use ($filters) {
                $query->select('work_crew_note_id')
                    ->from('job_sub_contractor')
                    ->whereIn('sub_contractor_id', (array)$filters['sub_ids'])
                    ->whereJobId($filters['job_id']);
            });
        }
    }
}
