<?php
namespace App\Events;

class CloseDripCampaignOfLastStage {

	/**
	 * Job Model
	 */
	public $job;

	public function __construct($job) {
		$this->job = $job;
	}
}