<?php namespace App\Events;

class SubContractorActivateOrDeactivate
{
	public $subContractor;
 	public function __construct( $subContractor )
	{
		$this->subContractor = $subContractor;
	}
} 