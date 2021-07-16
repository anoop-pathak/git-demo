<?php

namespace App\Http\OpenAPI\Controllers;

use App\Models\ApiResponse;
use App\Models\MaterialList;
use App\Services\WorkOrders\WorkOrderService;
use App\Http\OpenAPI\Transformers\WorkOrderTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Repositories\JobRepository;
use App\Exceptions\MaxFileSizeException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidURLException;

class WorkOrdersController extends ApiController
{

    public function __construct(WorkOrderService $service, Larasponse $response, JobRepository $jobRepo)
    {
        $this->service = $service;
        $this->response = $response;
        $this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Upload FIle
     * POST /work_orders/file
     * @return Response
     */
    public function uploadFile($jobID)
    {
        $input = Request::onlyLegacy('file', 'file_url', 'file_name');

        $job = $this->jobRepo->getById($jobID);

        $validator = Validator::make($input, MaterialList::getOpenAPIFileUploadRule($input));
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            if(!Request::hasFile('file') && !ine($input, 'file_url')) {
                return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'File']));
            }

            $workOrder = $this->service->uploadFile(
                $jobID,
                $input['file'],
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($workOrder, new WorkOrderTransformer)
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
