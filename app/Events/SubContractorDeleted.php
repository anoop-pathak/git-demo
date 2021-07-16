<?php namespace App\Events;

class SubContractorDeleted
{
	public $subContractor;
 	public function __construct( $subContractor )
	{
		$this->subContractor = $subContractor;
	}
} 