<?php
namespace App\Services\UserDevices;

use App\Repositories\UserDevicesRepository;

Class UserDeviceService
{

	/**
     * repository
     * @var Repository
     */
    protected $repository;

    function __construct(UserDevicesRepository $repository){
		$this->repository = $repository;
    }

    /**
	 * Mark device as primary device.
	 *
	 * @param Integer $deviceId: integer device id.
	 * @param Integer $userId: Integer user id.
	 * @param Boolean $markAsPrimary: bool value to make device primary and non primary.
	 * @return void
	 */
	public function markDevicePrimary($deviceId, $userId, $markAsPrimary = 0)
	{
        // vaildate device id.
        $this->repository->getById($deviceId);

        // mark all user devices as non-primary devices.
        $this->repository->markAllUserDevicesNonPrimary($userId);

        // mark requested device primary/non-primary on the basis of request.
        $device = $this->repository->markDevicePrimary($deviceId, $userId, $markAsPrimary);

        return $device;
	}
}