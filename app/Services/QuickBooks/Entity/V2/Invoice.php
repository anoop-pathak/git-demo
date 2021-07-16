<?php
namespace App\Services\QuickBooks\Entity\V2;

use App\Services\QuickBooks\Entity\BaseEntity;

class Invoice extends BaseEntity
{

	public function __construct()
	{

		parent::__construct();

	}
	/**
	 * Implement base class abstract function
	 *
	 * @return void
	 */
    public function getEntityName()
    {
        return 'invoice';
	}

	public function getJpEntity($qb_id){
		return null;
	}
}