<?php
namespace App\Services;
use App\Exceptions\ChangeOrderLeastAmountException;

class ChangeOrdersService {

	public function getTotalAmount($entities)
	{
		$totalAmount = 0;
		$totalAmount = array_sum(array_map(function($item) {
			return $item['amount'] * $item['quantity'];
		}, $entities));

		if($totalAmount < 0) {
			throw new ChangeOrderLeastAmountException(trans('response.error.least_amount', ['attribute' => 'Change Order']));
		}

		return $totalAmount;
	}
}
