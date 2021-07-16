<?php namespace App\Events;

class WorkSheetUpdated
{
	public $worksheet;

	public function __construct($worksheet)
	{
		$this->worksheet = $worksheet;
	}
}