<?php

namespace App\Repositories;

use App\Events\JobNoteAdded;
use App\Events\JobNoteUpdated;
use App\Models\JobNote;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Event;

class JobNotesRepository extends ScopedRepository
{

    protected $model;
    protected $scope;

    public function __construct(JobNote $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function saveNote($jobId, $noteText, $stageCode, $createdBy, $objectId = null, $attachments = array())
    {
        $note = new JobNote;
        $note->job_id = $jobId;
        $note->note = $noteText;
        $note->stage_code = $stageCode;
        $note->created_by = $createdBy;
        $note->company_id = $this->scope->id();
        $note->modified_by = $createdBy;
        $note->object_id = $objectId;
        $note->save();

        if(!empty($attachments)){
			$type = JobNote::JOB_NOTE;
			$attachments = $note->moveAttachments($attachments);
			$note->saveAttachments($note, $type, $attachments);
		}
        $note->job->updateJobUpdatedAt();
        //event for note added..
        Event::fire('JobProgress.Jobs.Events.JobNoteAdded', new JobNoteAdded($note->job, $noteText, $stageCode));

        return $note;
    }

    public function updateNote($note, $modifiedBy, JobNote $jobNote, $attachments = array(), $deleteAttachments = array())
    {
        $jobNote->modified_by = $modifiedBy;
        $jobNote->note = $note;
        $jobNote->save();

        $type = JobNote::JOB_NOTE;

		if (!empty($attachments)) {
			$attachments = $jobNote->moveAttachments($attachments);
			$jobNote->updateAttachments($jobNote, $type, $attachments);
		}

		if(!empty($deleteAttachments)) {
		    $jobNote->deleteAttachments($jobNote, $type, $deleteAttachments);
		}

        $jobNote->job->updateJobUpdatedAt();

        Event::fire('JobProgress.Jobs.Events.JobNoteUpdated', new JobNoteUpdated($jobNote->job, $note, $jobNote->stageCode));

        return $jobNote;
    }

    public function getFiltredNotes($filters, $sortable = true)
    {
        $notes = $this->getNotes($sortable, $filters);
        $this->applyFilters($notes, $filters);

        return $notes;
    }

    public function getNotes($sortable = true, $filters = [])
    {
        $notes = null;
        $includeData = $this->getIncludeData($filters);

        if ($sortable) {
            $notes = $this->make($includeData)->Sortable();
        } else {
            $notes = $this->make($includeData);
        }
        return $notes;
    }

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'job_id')) {
            $query->where('job_id', '=', $filters['job_id']);
        }
        if (ine($filters, 'stage_code')) {
            $query->where('stage_code', '=', $filters['stage_code']);
        }
    }

    private function getIncludeData($input)
    {
        $with = ['user.profile', 'stage', 'appointment.attendees', 'appointment.user.profile'];

        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }
        if (in_array('modified_by', $includes)) {
            $with[] = 'modifiedBy.profile';
        }

        if(in_array('attachments', $includes)) {
			$with[] = 'attachments';
		}

        return $with;
    }
}
