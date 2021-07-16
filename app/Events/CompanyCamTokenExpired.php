<?php namespace App\Events;

class CompanyCamTokenExpired 
{
 	public $CompanyId;
 	function __construct($companyId)
	{
		$this->companyId = $companyId;
	}
}