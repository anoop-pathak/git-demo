<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\UserDevice;
use App\Repositories\UserDevicesRepository;
use App\Transformers\UserDevicesTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\UserDeviceNotExistException;
use App\Exceptions\PrimaryDeviceAlreadyExistException;
use App\Services\UserDevices\UserDeviceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserDevicesController extends Controller
{

    /**
     * Customer Repo
     * @var \App\Repositories\CustomerRepositories
     */
    protected $repo;

    public function __construct(Larasponse $response, UserDeviceService $service, UserDevicesRepository $repo)
    {
        $this->response = $response;
        $this->service = $service;
        $this->repo = $repo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        $this->middleware('company_scope.ensure', ['only' => ['index']]);
    }

    public function index()
    {
        $input = Request::all();
        $devices = $this->repo->getDevices($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        if (!$limit) {
            return ApiResponse::success($this->response->collection($devices->get(), new UserDevicesTransformer));
        }
        $devices = $devices->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($devices, new UserDevicesTransformer));
    }

    /**
     * Save device token for notification..
     * Put /devices
     *
     * @return Response
     */
    public function update($deviceId)
    {
        $device = $this->repo->getById($deviceId);
        $input = Request::all();
        try {
            $device->update($input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Device token'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
	 * get item by id.
	 * GET /userdevices/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getById($deviceId)
	{
		try {
			$userDevice = UserDevice::findOrFail($deviceId);

			return ApiResponse::success([
				"item" => $this->response->item($userDevice, new UserDevicesTransformer)
			]);
		} catch (\Exception $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		}
	}

    /**
     * Remove the specified resource from storage.
     * DELETE /userdevices/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($deviceId)
    {
        $device = UserDevice::findOrFail($deviceId);
        if ($device->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Device'])
            ]);
        }
        return ApiResponse::errorInternal();
    }

    public function markDeviceAsPrimary($id, $isPrimaryDevice = 0)
	{
		DB::beginTransaction();
		try {
			$userId = Auth::user()->id;
			$userDevice = $this->service->markDevicePrimary($id, $userId, $isPrimaryDevice);

			$markDevice = 'non-primary';
			if($userDevice->is_primary_device) {
				$markDevice = 'primary';
			}

			DB::commit();
			return ApiResponse::success([
				"item" => $this->response->item($userDevice, new UserDevicesTransformer),
				'message' => 'Device is marked as ' . $markDevice
			]);

		} catch(UserDeviceNotExistException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PrimaryDeviceAlreadyExistException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal();
		}
	}
}
