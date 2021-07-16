<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Contexts\Context;
use App\Exceptions\UserDeviceNotExistException;
use App\Exceptions\PrimaryDeviceAlreadyExistException;
use Exception;

class UserDevicesRepository extends ScopedRepository
{

    /**
     * The base eloquent user_device
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(UserDevice $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function getDevices($filters = []) {
		$builder = $this->make();

		if(ine($filters, 'user_ids')) {
			$builder->WhereUsersIn((array)$filters['user_ids']);
		}

		if(ine($filters, 'platforms')) {
			$builder->WhereIn('platform', (array)$filters['platforms']);
		}
		return $builder;
	}

    public function findByUUID($uuid)
    {
        return $this->make()->where('uuid', $uuid)->first();
    }

    public function saveDevice(User $user, $data)
    {
        if (!ine($data, 'uuid')) {
            return false;
        }
        try {
            // delete if device already registered..
            $this->deleteIfExists($data['uuid']);

            // check user old devices
			$userDevice = $this->checkUserDeviceIfExists($user->id);

            $device = $this->model->create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'uuid' => $data['uuid'],
                'app_version' => isset($data['app_version']) ? $data['app_version'] : "",
                'platform' => isset($data['platform']) ? strtolower($data['platform']) : "",
                'manufacturer' => isset($data['manufacturer']) ? $data['manufacturer'] : "",
                'os_version' => isset($data['os_version']) ? $data['os_version'] : "",
                'model' => isset($data['model']) ? $data['model'] : "",
                'device_token' => isset($data['device_token']) ? $data['device_token'] : "",
                'session_id' => isset($data['session_id']) ? $data['session_id'] : 0,
                'is_primary_device' => (!$userDevice)
            ]);

            return $device;
        } catch (Exception $e) {
            // handle exception..
        }
    }

    /**
	 * Mark all user devices as non primary.
	 *
	 * @param Integer $userId: integer user id.
	 * @return Boolean(true)
	 */
	public function markAllUserDevicesNonPrimary($userId)
	{
		$builder = $this->make();
		$builder = $builder->where('user_id', $userId);

		$builder->update(['is_primary_device' => 0]);
		return true;
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
		$builder = $this->make();
		$builder = $builder->where('id', $deviceId)->where('user_id', $userId);
        $userDevice = $builder->first();

		if(!$userDevice) {
			throw new UserDeviceNotExistException('Invalid selected device.');
		}

		if($markAsPrimary && $userDevice->is_primary_device) {
			throw new PrimaryDeviceAlreadyExistException('Requested device is alreay a primary device.');
		}

		$userDevice->is_primary_device = $markAsPrimary;
		$userDevice->save();

		return $userDevice;

	}

    /*************** private function *******************/
    private function deleteIfExists($uuid)
    {
        $device = UserDevice::where('uuid', $uuid)->first();
        if ($device) {
            $device->delete();
        }
    }

    private function checkUserDeviceIfExists($userId)
	{
		$device = UserDevice::where('user_id', $userId)->exists();

		return $device;
	}
}
