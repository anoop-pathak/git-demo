<?php
namespace App\Repositories;

use App\Models\SerialNumber;
use App\Services\Contexts\Context;

class SerialNumberRepository extends ScopedRepository
{
    protected $scope;

    public function __construct(Context $scope, SerialNumber $model)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    /**
     * Save Serial Number
     * @param  String $type [description]
     * @param  Integer $startFrom [description]
     * @param  Boolean $active [description]
     * @param  Integer $currentNumber [description]
     * @return Serial Number Instance
     */
    public function save($type, $startFrom, $active, $lastRecordId, $prefix = null)
    {
        $serialNumber = $this->getByType($type);

        if ($serialNumber) {
            $serialNumber->update(['is_active' => false]);
        }

        $serialNumber = SerialNumber::create([
            'company_id' => $this->scope->id(),
            'type' => $type,
            'start_from' => ($startFrom) ? $startFrom : 0,
            'last_record_id' => ($lastRecordId) ?: 0,
            'is_active' => $active,
            'prefix'         => $prefix,
			'current_allocated_number' => $startFrom
        ]);

        return $serialNumber;
    }

    /**
     * Get Serial Number by type
     * @param  String $type Type (estimate, proposal and worksheet)
     * @return Response
     */
    public function getByType($type)
    {
        return $this->make()->active()->whereType($type)->first();
    }

    public function getByTypes($types)
	{
		return $this->make()->active()->whereIn('type', $types)->get();
	}

    /**
     * Get all serial numbers
     * @return collection
     */
    public function getAllSerialNumbers()
    {
        return $this->make()->active()->get();
    }
}
