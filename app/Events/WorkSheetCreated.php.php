<?php namespace App\Events;

class WorkSheetCreated
{
	public $worksheet;

	public function __construct($worksheet)
	{
		$this->worksheet = $worksheet;
	}
}