<?php
namespace App\Http\OpenAPI\Controllers;
use Request;
use App\Models\ApiResponse;
use Sorskod\Larasponse\Larasponse;
use App\Http\OpenAPI\Transformers\JobPaymentTransformer;
use App\Http\Controllers\ApiController;
use App\Repositories\JobRepository;
use App\Repositories\JobPaymentsRepository;

class JobPaymentsController extends ApiController
{
    public function __construct(Larasponse $response, JobRepository $jobRepo, JobPaymentsRepository $paymentRepo) {
        $this->response = $response;
        $this->jobRepo = $jobRepo;
        $this->paymentRepo = $paymentRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Get Job Payment Received History
     * Get /jobs/{id}/payment_history
     *
     * @param  $id | Job Id
     * @return Response
     */
    public function paymentsHistory($id)
    {
        $input = Request::all();
        $payments = $this->paymentRepo->paymentsHistory($id);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $payments = $payments->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($payments, new JobPaymentTransformer));
    }
}