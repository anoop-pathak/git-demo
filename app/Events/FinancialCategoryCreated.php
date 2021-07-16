<?php
namespace App\Events;

class FinancialCategoryCreated
{
	public $meta;

	public function __construct($meta)
	{
		$this->meta = $meta;
	}
}