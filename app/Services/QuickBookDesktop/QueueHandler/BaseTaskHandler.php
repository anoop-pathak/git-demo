<?php
namespace App\Services\QuickBookDesktop\QueueHandler;

use App;
use Exception;
use Illuminate\Support\Facades\Auth;
use Log;

abstract class BaseTaskHandler
{
	protected $resubmitted = false;
	protected $resubmittedFromSynch = false;

	/**
	 * Pass extra input data to the handler
	 */
	protected $mappedInput = [];

	public function checkPreConditions()
	{
		return true;
	}

	abstract function synch($task, $entity);

	public function isReSubmitted()
	{
		return $this->resubmitted;
	}

	public function reSubmit()
	{
		$this->resubmitted = true;
	}
}