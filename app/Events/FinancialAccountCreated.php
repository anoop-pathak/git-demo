<?php namespace App\Events;

class FinancialAccountCreated
{
	public $financialAccount;

	public function __construct( $financialAccount )
	{
		$this->financialAccount = $financialAccount;
	}
}