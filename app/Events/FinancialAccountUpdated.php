<?php namespace App\Events;

class FinancialAccountUpdated
{
	public $financialAccount;

	public function __construct( $financialAccount )
	{
		$this->financialAccount = $financialAccount;
	}
}