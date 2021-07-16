<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Http\CustomerWebPage\Transformers\JobInvoiceTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Job;

use Illuminate\Http\Request as RequestClass;

class JobInvoicesController extends Controller
{

    function __construct(
        Larasponse $response,
        JobRepository $jobRepo
    ) {
        $this->response = $response;
        $this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Get job invoice
     * Get me/invoices
     * @param  int $jobId job id
     * @return response
     */
    public function getJobInvoices(RequestClass $request)
    {
        $jobToken = getJobToken($request);
        try{
            $job = $this->jobRepo->getByShareToken($jobToken);
            $input = Request::all();

             if($job->isMultiJob() && ine($input, 'project_id')) {
                $job = Job::where('id', $input['project_id'])->where('parent_id', $job->id)->firstOrFail();
            }
            $invoices = $job->invoices;
            $response = $this->response->collection($invoices, new JobInvoiceTransformer);
        
        return ApiResponse::success($response);
        } catch(ModelNotFoundException $e){
            return ApiResponse::errorNotFound('Project Not Found.');
        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
