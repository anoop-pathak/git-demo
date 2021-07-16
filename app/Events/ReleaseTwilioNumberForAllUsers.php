<?php
namespace App\Events;

class ReleaseTwilioNumberForAllUsers
{
	/**
	 * Company Model
	 */
	public $company;

	function __construct($company)
	{
		$this->company = $company;
	}
}