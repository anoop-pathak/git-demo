<?php

namespace App\Repositories;

use App\Models\TempImportCustomer;
use App\Services\Contexts\Context;

class TempImportCustomersRepository extends ScopedRepository
{

    /**
     * The base eloquent temp_import_customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(TempImportCustomer $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Get all records
     * @return Object
     */
    public function get()
    {
        return $this->make();
    }

    /**
     * Get vaild records
     * @return Object
     */
    public function getValidRecords($filters=[])
    {
        $query   = $this->get()->whereIsValid(true);

		if(!ine($filters, 'include_duplicates')) {
			$query->whereDuplicate(false);
		}

		return $query;
    }

    /**
     * Get invalid records
     * @return Object
     */
    public function getInvalidRecords()
    {
        return $this->get()->invalid();
    }

    /**
     * Get duplicate record
     * @return Object [duplicate records]
     */
    public function getDuplicateRecords()
    {
        return $this->get()->duplicate();
    }

    public function getQuickBookValidRecords()
    {
        return $this->get()->valid(true);
    }

    public function getQuickBookRecords()
    {
        return $this->get()->quickBook(true);
    }
}
