<?php

namespace App\Events\TimeLogs;

class UserCheckIn
{
	public function __construct($timeLog)
	{
		$this->timeLog = $timeLog;
	}
}