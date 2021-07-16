<?php
namespace App\Events;

class JobSynched
{
	/**
	 * Job Model
	 */
	public $job;

	public function __construct($job) {
		$this->job = $job;
	}
}
