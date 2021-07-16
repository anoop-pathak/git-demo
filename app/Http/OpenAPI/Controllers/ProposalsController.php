<?php

namespace App\Http\OpenAPI\Controllers;

use Request;
use App\Models\Job;
use App\Models\Proposal;
use App\Models\ApiResponse;
use App\Services\ProposalService;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Validator;
use App\Http\OpenAPI\Transformers\ProposalsTransformer;
use App\Http\Controllers\ApiController;
use App\Repositories\JobRepository;
use App\Exceptions\MaxFileSizeException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidURLException;

class ProposalsController extends ApiController
{

    /**
     * Representatives Service
     * @var \App\Services\ProposalService
     */

    protected $service;
    protected $jobRepo;

    public function __construct(Larasponse $response, ProposalService $service, JobRepository $jobRepo)
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
     * POST /proposals/file
     * @return Response
     */
    public function fileUpload($jobId)
    {
        $input = Request::onlyLegacy('file', 'file_url', 'file_name');

        $job = $this->jobRepo->getById($jobId);

        $validator = Validator::make($input, Proposal::getOpenAPIFileUploadRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            if(!Request::hasFile('file') && !ine($input, 'file_url')) {
                return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'File']));
            }
            
            $proposal = $this->service->uploadFile(
                $jobId,
                $input['file'],
                null,
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
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
