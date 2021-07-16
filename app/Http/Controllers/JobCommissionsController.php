<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\JobCommission;
use App\Repositories\CommissionsRepository;
use App\Services\JobCommission\JobCommissionService;
use App\Transformers\JobCommissionsTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\JobCommissionPaymentTransformer;
use Illuminate\Support\Facades\DB;
use App\Models\JobCommissionPayment;

class JobCommissionsController extends ApiController
{
    protected $repo;
    protected $response;

    public function __construct(CommissionsRepository $repo, Larasponse $response, JobCommissionService $service)
    {
        $this->repo = $repo;
        $this->response = $response;
        $this->service = $service;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Get commissions of all jobs
     * GET /job_commissions
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $app = App::make(\App\Repositories\JobRepository::class);
        $job = $app->getById($input['job_id']);
        if ($job->isMultiJob()) {
            $jobId = $job->id;
            $commissions = JobCommission::whereIn('job_id', function ($query) use ($jobId) {
                $query->select('id')->from('jobs')->whereParentId($jobId);
                $query->orWhere('id', $jobId);
            })->orderBy('id', 'desc')
                ->get();
        } else {
            $commissions = $this->repo->getFiltredCommissions($input);
            $commissions = $commissions->get();
        }

        return ApiResponse::success($this->response->collection($commissions, new JobCommissionsTransformer));
    }

    /**
     * Get user's commissions in date range | Default Is YTD
     * GET /user_commissions
     *
     * @return Response
     */
    public function getUsersCommissions()
    {
        $input = Request::all();

        $commissions = $this->service->userCommissions($input);

        $limit = isset($input['limit']) ? $input['limit'] : \config('jp.pagination_limit');

        $transformers = (new JobCommissionsTransformer)->setDefaultIncludes(['job']);

        if (!$limit) {
            $commissions = $commissions->get();
            return ApiResponse::success($this->response->collection($commissions, $transformers));
        }

        $commissions = $commissions->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($commissions, $transformers));
    }

    /**
     * Save commission of job.
     * POST /job_commissions
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, JobCommission::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $commission = JobCommission::create([
                'company_id' => $this->repo->getScopeId(),
                'job_id' => $input['job_id'],
                'user_id' => $input['user_id'],
                'amount' => $input['amount'],
                'due_amount'  => $input['amount'],
                'status'      => JobCommission::UNPAID,
                'description' => isset($input['description']) ? $input['description'] : null,
                'date' => isset($input['date']) ? $input['date'] : null,
                'commission_percentage' => isset($input['commission_percentage']) ? $input['commission_percentage'] : null
            ]);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Job Commission']),
                'data' => $this->response->item($commission, new JobCommissionsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get commissions of specified job
     * GET /job_commissions/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $commission = $this->repo->getById($id);

        return ApiResponse::success(['data' => $this->response->item($commission, new JobCommissionsTransformer)]);
    }

    /**
     * Update specified commission
     * PUT /job_commissions/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::all();

        $validator = Validator::make($input, JobCommission::getUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $commission = $this->repo->getById($id);
        try {
            $paidCommission = $commission->commissionPayment()->sum('amount');
            if($input['amount'] < $paidCommission) {
                return ApiResponse::errorGeneral(trans('response.error.less_commission_amount'));
            }
            $dueAmount = $input['amount'] - $paidCommission;
            $input['due_amount'] = $dueAmount;
            $input['status']     = ($dueAmount > 0) ? JobCommission::UNPAID : JobCommission::PAID;
            $commission->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Job Commission']),
                'data' => $this->response->item($commission, new JobCommissionsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Cancel specified commission
     * POST /job_commissions/{id}/cancel
     *
     * @param  int $id
     * @return Response
     */
    public function cancel($id)
    {
        $commission = $this->repo->getById($id);

        DB::beginTransaction();
        try {
            $canceledAt = Carbon::now()->toDateTimeString();
            foreach($commission->commissionPayment as $payment) {
                $this->service->cancelPayment($payment, $canceledAt);
            }
            $commission->canceled_at = $canceledAt;
            $commission->save();

            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.canceled', ['attribute' => 'Job Commission'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Cancel specified Commission Payment
     * PUT /job_commissions/payment_cancel
     *
     * @return Response
     */
    public function cancelPayment()
    {
        $input = Request::all();
        $payment = JobCommissionPayment::findOrFail($input['id']);
        DB::beginTransaction();
        try{
            $commission = $payment->jobCommission;
            if(!$payment->canceled_at) {
                $canceledAt = Carbon::now()->toDateTimeString();
                $commission->due_amount = $commission->due_amount + $payment->amount;
                $commission->status = JobCommission::UNPAID;
                $commission->save();
            $payment =  $this->service->cancelPayment($payment, $canceledAt);
            }
            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.canceled', ['attribute' => 'Job Commission Payment']),
                'data' => $this->response->item($payment, new JobCommissionPaymentTransformer)
            ]);
        } catch(Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Delete specified Commission Payment
     * DELETE job_commissions/payment/{id}
     *
     * @return Response
     */
    public function deletePayment($id)
    {
        $payment = JobCommissionPayment::findOrFail($id);
        DB::beginTransaction();
        try{
            $commission = $payment->jobCommission;
            if(!$payment->canceled_at) {
                $commission->due_amount = $commission->due_amount + $payment->amount;
                $commission->status = JobCommission::UNPAID;
                $commission->save();
            }
            $payment->delete();
            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Job commission payment']),
            ]);
        } catch(Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * MarkAsPaid a specified commission
     * PUT /job_commissions/paid/{id}
     *
     * @param  int $id (commission_id)
     * @return Response
     */
    public function markAsPaid($id)
    {
        $commission = $this->repo->getByID($id);
        try {
            $commission->paid_on = Carbon::now();
            $commission->paid_by = \Auth::id();
            $commission->save();

            return ApiResponse::success(['message' => 'Commission marked as paid.']);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Add Commission Payment
     * @param  int $id (commission_id)
     * @return Response
     */
    public function addCommissionPayment()
    {
        $input = Request::all();
        $validator = Validator::make($input, JobCommissionPayment::getRules());
        if( $validator->fails()){
            return ApiResponse::validation($validator);
        }
        $commission = $this->repo->getById($input['commission_id']);
        DB::beginTransaction();
        try{
            $input['job_id'] = $commission->job_id;
            $input['paid_by'] = \Auth::id();
            
            if($input['amount'] > $commission->due_amount) {
                return ApiResponse::errorGeneral(
                    trans('response.error.amount_more_than_commission')
                );
            }
            $payment =  $this->service->addCommissionPayment(
                    $commission,
                    $input['job_id'],
                    $input['amount'],
                    $input['paid_by'],
                    $input['paid_on']
                );
            DB::commit();
            return ApiResponse::success([
                'message' => 'Commission Paid Successfully.',
                'data' => $this->response->item($payment, new JobCommissionPaymentTransformer)
            ]); 
        }catch(Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
    public function getCommissionPayments($id)
    {
        $input = Request::all();
        // $validator = Validator::make($input, ['commission_id' => 'required']);
        // if( $validator->fails()){
        //  return ApiResponse::validation($validator);
        // }
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $commission = $this->repo->getById($id);
        $payments = $commission->commissionPayment();
        $transformers = new JobCommissionPaymentTransformer;
        
        if(!$limit) {
            return ApiResponse::success($this->response->collection($payments->get(), $transformers));
        }
        $payments = $payments->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($payments, $transformers));
    }
}
