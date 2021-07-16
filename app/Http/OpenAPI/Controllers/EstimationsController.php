<?php

namespace App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Models\Estimation;
use App\Models\Job;
use App\Services\EstimationService;
use App\Http\OpenAPI\Transformers\EstimationsTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Repositories\JobRepository;
use App\Exceptions\InvalidFileException;
use App\Exceptions\MaxFileSizeException;
use App\Exceptions\InvalidURLException;

class EstimationsController extends ApiController
{

    /**
     * Representatives Service
     * @var \App\Services\EstimationService
     */

    public function __construct(Larasponse $response, EstimationService $service, JobRepository $jobRepo)
    {
        $this->response = $response;
        $this->service = $service;
        $this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     * Upload FIle
     * POST /estimations/file
     * @return Response
     */
    public function fileUpload($jobId)
    {
        $input = Request::onlyLegacy('file', 'file_url', 'file_name');

        $job = $this->jobRepo->getById($jobId);

        $validator = Validator::make($input, Estimation::getOpenAPIFileUploadRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            if(!Request::hasFile('file') && !ine($input, 'file_url')) {
                return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'File']));
            }

            $estimation = $this->service->uploadFile(
                $jobId,
                $input['file'],
                null,
                $input
            );
            
            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($estimation, new EstimationsTransformer)
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
