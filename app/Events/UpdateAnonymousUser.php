<?php
namespace App\Events;

class UpdateAnonymousUser{

	/**
	 * User Model
	 */
	public $user;

	function __construct( $user ){
		$this->user = $user;
	}
}