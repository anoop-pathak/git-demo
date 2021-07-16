<?php
namespace App\Http\OpenAPI\Controllers;

use Request;
use App\Services\Measurement\MeasurementService;
use App\Http\OpenAPI\Transformers\MeasurementTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Models\ApiResponse;
use Validator;
use App\Services\Contexts\Context;
use App\Models\Measurement;
use Exception;
use App\Http\Controllers\ApiController;
use App\Repositories\JobRepository;
use App\Exceptions\MaxFileSizeException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidURLException;

class MeasurementController extends ApiController {
		
	public function __construct(MeasurementService $service, Larasponse $response, Context $scope, JobRepository $jobRepo)
	{
		$this->service = $service;
		$this->response = $response;
		$this->scope = $scope;
		$this->jobRepo = $jobRepo;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	/**
	* upload file in measurement
	*
	* @return success response
	*/
	public function fileUpload($jobId)
	{
		$input = Request::onlyLegacy('file', 'file_url', 'file_name');

		$job = $this->jobRepo->getById($jobId);

        $validator = Validator::make($input,Measurement::getOpenAPIFileUploadRules());
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            if(!Request::hasFile('file') && !ine($input, 'file_url')) {
                return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'File']));
            }

            $measurement = $this->service->uploadFile(
            	$jobId,
            	$input['file'],
            	$input
            );

			return ApiResponse::success([
				'message' => trans('response.success.file_uploaded'),
				'data'    => $this->response->item($measurement, new MeasurementTransformer)
			]);
			
		} catch(InvalidFileException $e) {
             return ApiResponse::errorGeneral($e->getMessage());
        } catch(MaxFileSizeException $e) {
             return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidURLException $e) {
             return ApiResponse::errorGeneral($e->getMessage());
        } catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);	
		}
	}
}
