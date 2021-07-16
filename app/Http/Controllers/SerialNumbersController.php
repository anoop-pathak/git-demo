<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\SerialNumber;
use App\Services\SerialNumbers\SerialNumberService;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\SerialNumberInvalidException;

class SerialNumbersController extends Controller
{

    public function __construct(SerialNumberService $service)
    {
        $this->service = $service;

        parent::__construct();
    }

    /**
     * Store a newly created resource in storage.
     * POST /company/set_serial_number
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('type', 'start_from', 'prefix');

        $validator = Validator::make($input, SerialNumber::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $this->service->save($input['type'], $input['start_from'], $input['prefix']);
        } catch(SerialNumberInvalidException $e){

			return ApiResponse::errorGeneral($e->getMessage());
            DB::rollback();
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'serial_number' => trans('response.success.saved', ['attribute' => 'Serial Number']),
        ]);
    }

    /**
     * Generate Serial Number
     * Get /generate_serial_number
     * @return serial number
     */
    public function generateSerialNumber()
    {
        $input = Request::onlyLegacy('type');

        $validator = Validator::make($input, SerialNumber::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $serialNumber = $this->service->generateSerialNumber($input['type']);

        return ApiResponse::success([
            'data' => [
                'serial_number' => $serialNumber,
                'type' => $input['type']
            ]
        ]);
    }

    /**
     * Get Serial Numbes
     * Get company/serial_numbers
     * @return Response
     */
    public function getSerialNumbers()
    {
        $serialNumber = $this->service->getAllSerialNumbers();

        return ApiResponse::success([
            'data' => $serialNumber
        ]);
    }

    /**
	 * Generate Serial Number
	 * Put /generate_new_serial_number
	 * @return serial number
	 */
	public function generateNewSerialNumber()
	{
		$input = Request::onlyLegacy('type');

		$validator = Validator::make($input, ['type' => 'required|array']);

		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		DB::beginTransaction();
		try {
			$serialNumber = $this->service->generateNewSerialNumber($input['type']);

		} catch(\Exception $e){
			DB::rollback();

			return ApiResponse::errorInternal(Lang::get('response.error.internal'),$e);
		}
		DB::commit();

		return ApiResponse::success(['data' => $serialNumber]);
	}
}
