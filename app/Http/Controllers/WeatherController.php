<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Services\Weather\Weather;
use Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class WeatherController extends ApiController
{

    protected $weatherService;

    public function __construct(Weather $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function get_weather()
    {
        $input =    Request::onlyLegacy('location', 'unit', 'country_code');
        $validator = Validator::make($input,[
            'location'      => 'required',
            'country_code'  => 'required',
            'unit'          => 'in:f,c',
        ]);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $unit = ine($input, 'unit') ? $input['unit'] : 'f';
        try{
            $response = $this->weatherService->getWeather($input['location'],
                $input['country_code'],
                $unit
            );
            return $response;
        } catch(Exception $e){
            switch ($e->getCode()) {
                case 401:
                    return \ApiResponse::errorGeneral('Unauthorized');
                    break;
                # Handle Request Timeout Exception i.e 504 #
                case 504:
                    return \ApiResponse::errorGeneral(trans('response.error.timeout'));
                    break;
            }
            throw $e;
        }

        return $response;
    }
}
