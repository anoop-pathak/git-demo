<?php
namespace App\Services\QuickBooks\Entity\V2;

use App\Services\QuickBooks\Entity\BaseEntity;

class Payment extends BaseEntity
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
        return 'payment';
	}

	public function getJpEntity($qb_id){
		return null;
	}
}