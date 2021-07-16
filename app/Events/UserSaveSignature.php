<?php
namespace App\Events;

class UserSaveSignature
{
	/**
	 * User Model
	 */
	public $user;

    function __construct( $user )
    {
		$this->user = $user;
	}
}