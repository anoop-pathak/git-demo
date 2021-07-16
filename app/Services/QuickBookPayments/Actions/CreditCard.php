<?php

namespace App\Services\QuickBookPayments\Actions;

use App\Services\QuickBookPayments\Objects\CreditCard as CreditCardObject;

trait CreditCard
{
	private $creditCardUrl = "/quickbooks/v4/payments/tokens";

	public function createCcToken(CreditCardObject $creditCardObject)
	{
		$response = $this->send($this->creditCardUrl, $creditCardObject->payloadAsJson());
		return $this->extractCcToken($response);
	}
 	private function extractCcToken($response)
	{
		return json_decode($response);
	}
}