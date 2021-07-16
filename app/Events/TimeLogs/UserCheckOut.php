<?php

namespace App\Events\TimeLogs;

class UserCheckOut
{
	public function __construct($timeLog)
	{
		$this->timeLog = $timeLog;
	}
}