<?php
namespace App\Services\QuickBooks\Entity\V2;

use App\Services\QuickBooks\Entity\BaseEntity;

class Credit extends BaseEntity
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
        return 'creditmemo';
	}

	public function getJpEntity($qb_id){
		return null;
	}
}