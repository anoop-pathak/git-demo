<?php
namespace App\Repositories;

use App\Models\CumulativeInvoiceNote;
use App\Services\Contexts\Context;

Class CumulativeInvoiceNoteRepository extends ScopedRepository {

	/**
     * The base eloquent customer
     * @var Eloquent
     */
	protected $model;
	protected $scope;

	function __construct(CumulativeInvoiceNote $model, Context $scope)
	{
		$this->model   = $model;
		$this->scope   = $scope;
	}

	public function createCumulativeInvoiceNotes($job, $note)
	{
		$invoiceNote = CumulativeInvoiceNote::firstOrNew([
			'job_id'      => $job->id,
			'company_id'  => $this->scope->id(),
			'customer_id' => $job->customer_id
		]);
		$invoiceNote->note   = $note;
		$invoiceNote->save();

		return $invoiceNote;
	}

	public function getJobNote($jobId)
	{
		$note = $this->make()->where('job_id', $jobId)->first();

		return $note;
	}
}