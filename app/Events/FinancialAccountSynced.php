<?php namespace App\Events;

class FinancialAccountSynced
{
	public $financialAccount;

	public function __construct( $financialAccount )
	{
		$this->financialAccount = $financialAccount;
	}
}