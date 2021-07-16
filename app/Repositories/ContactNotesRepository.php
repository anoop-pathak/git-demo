<?php
namespace App\Repositories;

use App\Models\ContactNote;
use App\Services\Contexts\Context;

Class ContactNotesRepository extends ScopedRepository
{
	protected $model;
    protected $scope;

    function __construct(ContactNote $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save Contact Notes.
     *
     * @return Response
     */
	public function saveNote($notes, $contactId)
    {

        foreach ($notes as $note) {
            $results[] = ContactNote::create([
            	'company_id' => $this->scope->id(),
            	'contact_id' => $contactId,
            	'note'		 => $note,
            ]);
        }

        return $results;

    }

    public function updateNote($note, ContactNote $contactNote)
    {
        $contactNote->note = $note;
        $contactNote->save();

        return $contactNote;
    }

    public function getFiltredContactNotes($filters = array(), $sortable = true)
    {
        $with = $this->getIncludesData($filters);
        $notes = $this->make($with);

        if($sortable){
        	$notes = $notes->sortable();
        }
        $notes->orderBy('id', 'desc');

        $this->applyFilters($notes, $filters);
        return $notes;
    }

    private function applyFilters($query, $filters = array())
    {
        if (ine($filters, 'contact_id')) {
            $query->where('contact_id', $filters['contact_id']);
        }
    }

    private function getIncludesData($filters)
    {
        $with = [];
        if(!ine($filters, 'includes')) return $with;

        $includes = (array)$filters['includes'];

        if(in_array('created_by', $includes)) {
            $with[] = 'createdBy';
        }

        return $with;
    }
}