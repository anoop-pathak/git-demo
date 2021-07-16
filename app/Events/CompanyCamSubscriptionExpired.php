<?php namespace App\Events;

class CompanyCamSubscriptionExpired 
{
  
	public $CompanyId;
 	function __construct($companyId)
	{
		$this->companyId = $companyId;
	}
} 