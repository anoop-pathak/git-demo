<?php namespace App\Events;

class SubContractorGroupChanged
{
	public $subContractor;
 	public function __construct( $subContractor )
	{
		$this->subContractor = $subContractor;
	}
} 