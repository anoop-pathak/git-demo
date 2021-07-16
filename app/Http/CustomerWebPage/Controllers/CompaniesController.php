<?php

namespace App\Http\CustomerWebPage\Controllers;


use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Http\CustomerWebPage\Transformers\CompaniesTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Models\Setting;
use App\Models\QuickBook;
use App\Models\HoverClient;
use Settings;
use Illuminate\Http\Request as RequestClass;

class CompaniesController extends ApiController
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

    public function __construct(
        Larasponse $response,
        Context $scope,
        JobRepository $jobRepo
    ) {

        $this->response = $response;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function getJobCompany(RequestClass $request)
    {
        $jobToken = getJobToken($request);

        try{
            $job = $this->jobRepo->getByShareToken($jobToken);
            $company = $job->company;

            $response = $this->response->item($company, new CompaniesTransformer);

            return ApiResponse::success($response);
        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function connectedThirdParties(RequestClass $request)
    {
        $jobToken = getJobToken($request);
        $job = $this->jobRepo->getByShareToken($jobToken);
        $companyId = $job->company->id;

        $input = Request::onlyLegacy('name');
        $data = [];

        if(in_array('quickbook_pay', (array)$input['name'])) {
            $quickbook = QuickBook::where('company_id', $companyId)->whereNotNull('quickbook_id')->where('is_payments_connected','1')->exists();
            $data['quickbook_pay'] =  (int)($quickbook);
        }

        if(in_array('greensky', (array)$input['name'])) {
            $greensky = Setting::whereCompanyId($companyId)->where('key','GREEN_SKY')->where('value->greensky_enabled','1')->exists();
            $data['greensky'] =  (int)($greensky);
        }

        if(in_array('hover', (array)$input['name'])) {
            $hover = HoverClient::where('company_id', $companyId)->whereNotNull('webhook_id')->whereNull('deleted_at')->exists();
            $data['hover'] =  (int)($hover);
        }

        return ApiResponse::success([
            'data' => $data,
        ]);
    }
}
