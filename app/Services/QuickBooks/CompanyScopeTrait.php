<?php
namespace App\Services\QuickBooks;

trait CompanyScopeTrait {

    /**
	 * Set Company Scope
	 */
	private function setCompanyScope($userId)
	{
		setAuthAndScope($userId);
		return true;
	}
}