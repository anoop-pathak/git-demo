<?php

namespace App\Http\CustomerWebPage\Controllers;


use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Http\CustomerWebPage\Transformers\CustomersTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request as RequestClass;
use App\Models\CustomerFeedback;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Validator;
use App\Services\Weather\Weather;
use Illuminate\Support\Facades\Log;
use Exception;

class WeatherController extends ApiController
{

    /**
     * Customer Repo
     * @var \App\Repositories\CustomerRepositories
     */
    protected $repo;

    /**
     * Display a listing of the resource.
     * GET /customers
     *
     * @return Response
     */
    protected $response;
    protected $scope;
    protected $jobRepo;
    protected $weatherService;

    public function __construct(
        Larasponse $response,
        Context $scope,
        JobRepository $jobRepo,
        Weather $weatherService
    ) {

        $this->response = $response;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->weatherService = $weatherService;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function Weather(RequestClass $request)
    {
        $jobToken = getJobToken($request);

        try{
            $job   = $this->jobRepo->getByShareToken($jobToken);
            $input = Request::all();
            $validator = Validator::make($input, ['unit' => 'in:f,c']);
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $unit = ine($input, 'unit') ? $input['unit'] : 'f';
            $weather = $this->getWeather($job->address, $unit);

            return ApiResponse::success(['data' => $weather]);
        } catch(Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

     /**
     * get weather
     * GET customer_job_preview/weather
     *
     * @param $adress
     * @return $weather
     */
    public function getWeather($address, $unit)
    {
        if(!$address || (!$country = $address->country) || (!$address->city && !$address->zip)) return false;
        $location = '';
        
        try {
            if($address->city) {
                $location = $address->city;
            }elseif($address->zip) {
                $location = $address->zip;
            }

            $weather = $this->weatherService->getWeather($location, $country->code, $unit);

            return $weather;
        } catch (Exception $e) {
            throw $e;
            //handle exception
            // Log::warning('Home Owner Page Weather: ' . getErrorDetail($e));
        }
    }
}

