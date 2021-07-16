<?php

namespace App\Services\Xactimate;

class BaseXactimate
{
	protected $note;

	public function getNote()
	{
		return $this->note;
	}

	# get quantity and unit column
	protected function getQtyUnit($data)
	{
		$qtyUnit = explode(" ", $data);

		if(count($qtyUnit) > 1) {
			$quantity = $qtyUnit[0];
			$unit = $qtyUnit[1];
		}elseif(count($qtyUnit == 1)) {
			if(is_numeric($qtyUnit[0])) {
				$quantity = $qtyUnit[0];
				$unit = '';
			}else {
				$quantity = '';
				$unit = $qtyUnit[0];
			}
		}else {
			$quantity = '';
			$unit = '';
		}

		return [
			'quantity'	=> $quantity,
			'unit'		=> $unit,
		];
	}
}