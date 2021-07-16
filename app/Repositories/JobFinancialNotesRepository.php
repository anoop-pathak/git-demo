<?php
namespace App\Repositories;

use App\Models\JobFinancialNote;
use Illuminate\Support\Facades\Auth;

class JobFinancialNotesRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
	protected $scope;

	public function __construct(JobFinancialNote $model)
	{
		$this->model = $model;
	}

	public function addOrUpdateNote($jobId, $noteText)
	{
		$note = $this->model->whereJobId($jobId)->first();
		if(!$note){
			$note = new JobFinancialNote;
			$note->job_id = $jobId;
			$note->note = $noteText;
			$note->company_id = getScopeId();
			$note->created_by = Auth::id();
			$note->updated_by = Auth::id();
			$note->save();
		} else {
			$note->note = $noteText;
			$note->updated_by = Auth::id();
			$note->update();
		}

		return $note;
	}

}
