<?php

namespace App\Services\QuickBookPayments\Actions;

use App\Services\QuickBookPayments\Objects\Charge as ChargeObject;

trait Charges
{
	private $chargesUrl = "/quickbooks/v4/payments/charges";
 	/**
	 * This method will create the charge on the Payments Server from the ChargesObject
	 * @param  ChargeObject $chargesObject The charge to be created at the Payments Server
	 * @return Response from the Server
	 */
	public function createCharge(ChargeObject $chargesObject)
	{
		return $this->send($this->chargesUrl, $chargesObject->payload());
	}
}