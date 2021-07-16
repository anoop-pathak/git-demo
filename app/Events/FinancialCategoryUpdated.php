<?php
namespace App\Events;

class FinancialCategoryUpdated
{
	public $meta;

	public function __construct($meta)
	{
		$this->meta = $meta;
	}
}