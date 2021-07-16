<?php namespace App\Events;

class SubContractorUpdated
{
	public $subContractor;
 	public function __construct( $subContractor )
	{
		$this->subContractor = $subContractor;
	}
} 