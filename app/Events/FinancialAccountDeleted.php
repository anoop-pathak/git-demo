<?php namespace App\Events;

class FinancialAccountDeleted
{
	public $financialAccount;

	public function __construct( $financialAccount )
	{
		$this->financialAccount = $financialAccount;
	}
}