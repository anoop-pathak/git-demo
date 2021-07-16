<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Services\Sync\SyncService;
use Sorskod\Larasponse\Larasponse;
use App\Helpers\CloudFrontSignedCookieHelper as CloudFront;
use App\Exceptions\InvalidDivisionException;

class SyncController extends ApiController
{

    protected $response;
    protected $service;

    public function __construct(Larasponse $response, SyncService $service)
    {
        $this->response = $response;
        $this->service = $service;
        parent::__construct();

    }

    public function sync()
    {
        try {
            $data = $this->service->getSync();

            // set headers for cloud front cookies..
            CloudFront::setCookies();

            return ApiResponse::success(['data' => $data]);
        } catch(InvalidDivisionException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }
    }
}
