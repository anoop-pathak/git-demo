<?php
namespace App\Events;

class FinancialCategoryDeleted
{
	public $meta;

	public function __construct($meta)
	{
		$this->meta = $meta;
	}
}