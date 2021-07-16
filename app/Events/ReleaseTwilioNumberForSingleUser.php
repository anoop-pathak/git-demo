<?php
namespace App\Events;

class ReleaseTwilioNumberForSingleUser
{
	/**
	 * User Model
	 */
	public $user;

	function __construct($user)
	{
		$this->user = $user;
	}
}