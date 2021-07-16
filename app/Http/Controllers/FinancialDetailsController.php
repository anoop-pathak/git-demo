<?php

namespace App\Http\Controllers;

use Request;
use Settings;
use Carbon\Carbon;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Models\ApiResponse;
use App\Helpers\SecurityCheck;
use App\Models\FinancialDetail;
use App\Models\JobPricingHistory;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Exceptions\QuickBookException;
use App\Models\JobFinancialCalculation;
use App\Services\FileSystem\FileService;
use App\Repositories\FinancialRepository;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\AuthorizationException;
use App\Transformers\JobInvoiceTransformer;
use App\Transformers\JobPaymentTransformer;
use App\Services\QuickBooks\QuickBookService;
use App\Exceptions\JobAmountNotFoundException;
use App\Exceptions\NoCreditAvailableException;
use App\Services\JobInvoices\JobInvoiceService;
use App\Transformers\JobPricingHistoryTransformer;
use App\Services\FinancialDetails\FinancialPayment;
use App\Transformers\JobFinancialCalculationTransformer;
use App\Exceptions\CreditLessThanInvoicePaymentException;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\PaymentMethodsRepository;
use App\Transformers\PaymentMethodsTransformer;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Events\JobPaymentUpdated;
use App\Events\JobPaymentCancelled;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Event;

class FinancialDetailsController extends Controller
{

    protected $repo;
    protected $jobRepo;
    protected $scope;

    public function __construct(
        FinancialRepository $repo,
        JobRepository $jobRepo,
        QuickBookService $quickBookService,
        Larasponse $response,
        FinancialPayment $financialPayment,
        JobInvoiceService $invoiceService,
        Context $scope,
        PaymentMethodsRepository $paymentMethodRepo
    ) {

        $this->repo = $repo;
        $this->jobRepo = $jobRepo;
        $this->quickBookService = $quickBookService;
        $this->response = $response;
        $this->finacialPayment = $financialPayment;
        $this->invoiceService = $invoiceService;
        $this->scope = $scope;
        $this->paymentMethodRepo = $paymentMethodRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Store a newly created resource in storage.
     * POST /financialdetails
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, FinancialDetail::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->jobRepo->getById($input['job_id']);
        try {
            FinancialDetail::where('job_id', $input['job_id'])->delete();
            $details = $this->getValideData($input);
            if (!empty($details)) {
                $this->saveDetails($details);
            }
            // $this->saveJobFinancial($job,$input);
            return ApiResponse::success(['message' => trans('response.success.saved', ['attribute' => 'Financial details'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get payment slip pdf print
     * Get jobs/payment_slip/{payment_id}
     */
    public function getPaymentSlip($id)
    {
        $input = Request::onlyLegacy('download', 'save_as_attachment');

        $jobPayment = JobPayment::with('details')
            ->whereHas('job', function ($query) {
                $query->where('company_id', getScopeId());
            })//->has('customer')
            ->findOrFail($id);

        try {
            $job = $jobPayment->job;
            $customer = $jobPayment->customer;

            $data = [
                'job' => $job,
                'company' => $job->company,
                'customer' => $customer,
                'jobPayment' => $jobPayment,

            ];
            $contents = view('jobs.job_payment_slip', $data)->render();

            return (new FileService)
                ->generateHtmlToPdf($contents, 'payment_slip.pdf', $input);
        } catch (\Exception $e) {
            return view('error-page', [
                'errorDetail' => getErrorDetail($e),
                'message' => trans('response.error.error_page'),
            ]);
        }
    }

    /**
     * Save or update job amount or tax_rate
     * PUT /jobs/{id}
     *
     * @param  int $id | Job Id
     * @return Response
     */
    public function jobAmount($id)
    {
        if (!SecurityCheck::maxCustomerJobEditLimit()) {
            return SecurityCheck::$error;
        }

        if(Settings::get('ENABLE_JOB_PRICE_REQUEST_SUBMIT_FEATURE')) {

			return ApiResponse::errorGeneral('Please disable job price update request submit feature.');
		}

        $job = $this->jobRepo->getById($id);
        $input = Request::onlyLegacy('amount', 'taxable', 'tax_rate', 'custom_tax_id');

        $validator = Validator::make($input, Job::getAmountRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $input['taxable'] = ine($input, 'taxable') ? $input['taxable'] : false;
            $input['custom_tax_id'] = ine($input, 'taxable') ? $input['custom_tax_id'] : null;
            $pricing = null; //default null if not updated
            if (($job->amount != $input['amount'])
                || ($job->taxable != $input['taxable'])
                || ($job->tax_rate != $input['tax_rate'])
            ) {
                // update job pricing..
                $job->amount  = numberFormat($input['amount']);
                $job->taxable = $input['taxable'];
                $job->tax_rate = $input['tax_rate'];
                $job->custom_tax_id = $input['custom_tax_id'];
                $job->job_amount_approved_by = null;
                $job->update();

                JobFinancialCalculation::updateFinancials($job->id);

            	if($job->isProject() || $job->isMultiJob()) {
                	//update parent job financial
                	JobFinancialCalculation::calculateSumForMultiJob($job);
            	}
                // maintain history..
                $pricing = JobPricingHistory::maintainHistory($job);
            }

            /* Set Response Attribute */
            $attribute = 'Job Price';
            if ($job->isProject()) {
                $attribute = 'Project Price';
            }
        } catch (JobAmountNotFoundException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return ApiResponse::l($e->getMessage());
        } catch (QuickBookException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }

        DB::commit();

        if ($pricing) {
            $pricing = $this->response->item($pricing, new JobPricingHistoryTransformer);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.updated', ['attribute' => $attribute]),
            'pricing' => $pricing,
        ]);
    }

    /**
     * Job Payment received
     * Post /jobs/payment
     *
     * @return Response
     */
    public function jobPayment()
    {
        set_time_limit(0);
        $input = Request::onlyLegacy('id', 'job_id', 'payment', 'method', 'echeque_number', 'invoice_payments', 'unapplied_payment', 'date', 'details');
        if (!ine($input, 'date')) {
            $input['date'] = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();
        }
        $jobInvoices = [];
        $validator = Validator::make($input, FinancialDetail::getJobPaymentRules());
        $refNo = 'ref no.';
        if (ine($input, 'method') && $input['method'] === 'echeque') {
            $refNo = 'check no.';
        }
        $validator->setAttributeNames(['echeque_number' => $refNo]);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->jobRepo->getById($input['job_id']);
        $customer = $job->customer;

        /* check job is awarded  or able to make */
        if (!SecurityCheck::isJobAwarded($job)) {
            return ApiResponse::errorInternal(trans('response.error.job_not_awarded'));
        }

        try {
            if (ine($input, 'invoice_payments')) {
                foreach ($input['invoice_payments'] as $key => $value) {
                    $invoice = JobInvoice::findOrFail($value['invoice_id']);
                    $input['invoice_payments'][$key]['quickbook_invoice_id'] = $invoice->quickbook_invoice_id;
                    $input['invoice_payments'][$key]['job_id'] = $invoice->job_id;
                    if ($value['amount'] > $invoice->open_balance) {
                        return ApiResponse::errorGeneral('incorrect amount.');
                    }
                }
            }
            $data = $this->finacialPayment->payment(
                $input['payment'],
                $input['unapplied_payment'],
                $input['method'],
                $input['echeque_number'],
                $input['invoice_payments'],
                $input['details'],
                $input,
                $job
            );
            $invoiceIds = [];
            if (!empty($input['invoice_payments'])) {
                $invoiceIds = array_column($input['invoice_payments'], 'invoice_id');
            }
            $this->updateInvoices($invoiceIds);

            //get open status invoice
            $jobInvoices = JobInvoice::whereStatus(JobInvoice::OPEN)
                ->whereJobId($job->id)
                ->with('payments')
                ->get();
            $data['job_invoices'] = $this->response->collection($jobInvoices, new JobInvoiceTransformer)['data'];
        } catch (CreditLessThanInvoicePaymentException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (NoCreditAvailableException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (UnauthorizedException $e) {
            //Do nothing
            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            //Do nothing
            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }

        $attribute = 'Job';
        if ($job->isProject()) {
            $attribute = 'Project';
        }

        $message = trans('response.success.job_payment_receieve', ['attribute' => $attribute]);
        if (ine($input, 'unapplied_payment')) {
            $message = trans('response.success.job_amount_applied');
        }

        return ApiResponse::success([
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Job Payment delete
     * Delete /jobs/payment
     *
     * @return Response
     */
    public function jobPaymentDelete()
    {
        $input = Request::onlyLegacy('id', 'job_id');
        $validator = Validator::make($input, ['id' => 'required', 'job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $payment = JobPayment::whereJobId($input['job_id'])->whereId($input['id'])->firstOrFail();
        if ($payment->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Payment']),
            ]);
        }
        return ApiResponse::errorInternal();
    }

    /**
     *
     * @return [type] [message]
     */
    public function jobPaymentCancel()
    {
        $input = Request::onlyLegacy('id', 'job_id', 'note');
        $validator = Validator::make($input, ['id' => 'required', 'job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $jobPayment = JobPayment::whereJobId($input['job_id'])
            ->whereId($input['id'])
            ->with('invoicePayments', 'transferFromPayment')
            ->firstOrFail();

        $appliedPaymentIds = JobPayment::whereRefId($input['id'])->pluck('id')->toArray();
        $refJobIds = JobPayment::whereRefId($input['id'])->pluck('job_id')->toArray();

        DB::beginTransaction();
        try {
            $job = $jobPayment->job;
            $customer = $job->customer;
            $quickBookCustomerId = $customer->quickbook_id;
            //restore unapplied amount if any.
            $canceled = $jobPayment->canceled;
            $refId = $jobPayment->ref_id;
            $refTo = $jobPayment->ref_to;
            $invoiceIds = [];
            $jobIds = [];
            $jobIds = array_merge($jobIds, $refJobIds);
            $jobIds[] = $jobPayment->job_id;
            $qbAction = null;
			$qbName = null;
            if(is_null($canceled) && $refId) {
                $paymentObj = JobPayment::whereId($refId)->firstOrFail();
                $jobIds[] = $paymentObj->job_id;
                $invoiceIds = array_merge($invoiceIds, $paymentObj->invoicePayments()->pluck('invoice_id')->toArray());
                $unapplied_amount = $paymentObj->unapplied_amount;
                $payment = $jobPayment->payment;
                $paymentObj->unapplied_amount = $unapplied_amount + $payment;
                $paymentObj->save();
            }
            $refToJobPayment = JobPayment::whereRefTo($jobPayment->id)->first();
            if($refToJobPayment) {
                $refToJobPayment->cancel_note = ine($input, 'note') ? $input['note'] : null;
                $refToJobPayment->canceled = Carbon::now()->toDateTimeString();
                $refToJobPayment->save();
            }
            if(is_null($canceled) && $refTo) {
                $refToJobPayment = JobPayment::findOrFail($refTo);
                $jobIds[] = $refToJobPayment->job_id;
                $invoiceIds = array_merge($invoiceIds, $refToJobPayment->refInvoicePayments()->pluck('invoice_id')->toArray());
                $refToJobPayment->refInvoicePayments()->delete();
                $refToJobPayment->cancel_note = ine($input, 'note') ? $input['note'] : null;
                $refToJobPayment->canceled = Carbon::now()->toDateTimeString();
                $refToJobPayment->save();
            }
            $jobPayment->canceled = Carbon::now()->toDateTimeString();
            $jobPayment->modified_by = Auth::id();
            // $jobPayment->quickbook_id = null;
            // $jobPayment->quickbook_sync_token = null;
            $jobPayment->unapplied_amount = 0;
            $jobPayment->cancel_note = ine($input, 'note') ? $input['note'] : null;
            $jobPayment->save();
            $invoiceIds  = array_merge($invoiceIds, $jobPayment->invoicePayments()->pluck('invoice_id')->toArray());
            $invoiceIds  = array_merge($invoiceIds, $jobPayment->refInvoicePayments()->pluck('invoice_id')->toArray());
            $jobPayment->invoicePayments()->delete();
            if($jobPayment->ref_id) {
                $jobPayment->refInvoicePayments()->delete();
            }
            $jobInvoices = JobInvoice::whereIn('id', arry_fu($invoiceIds))->get();
            foreach ($jobInvoices as $invoice) {
                $this->invoiceService->updatePdf($invoice);
            }
            JobPayment::whereIn('id', $appliedPaymentIds)
                ->update([
                    'canceled'               => Carbon::now()->toDateTimeString(),
                    'modified_by'        => Auth::id(),
                    'unapplied_amount' => 0,
                    'cancel_note'        => ine($input, 'note') ? $input['note'] : null,
                    ]);
                foreach(arry_fu($jobIds) as $jobId) {
                    JobFinancialCalculation::updateFinancials($jobId);
                }
            $totalReceivedAmount = $job->payments()->whereNull('canceled')->where('status', '=', 'closed')->sum('payment');
            $paymentIds = $job->payments()->whereNull('canceled')->where('status', '=', 'closed')->pluck('id')->toArray();
            $totalRefAmount = 0;
            foreach ($paymentIds as $paymentId) {
                $totalRefAmount += JobPayment::whereNull('canceled')->whereRefId($paymentId)->whereNull('ref_to')->sum('payment');
            }
            $totalAmount = $totalReceivedAmount - $totalRefAmount;
            $unappliedPayment = $customer->payments()->whereNull('canceled')->sum('unapplied_amount');
            if($transferPayment = $jobPayment->transferFromPayment) {
                $qbAction = QuickBookTask::UPDATE;
				$qbName = QuickBookTask::QUICKBOOKS_PAYMENT_UPDATE;

				// QBDesktopQueue::addPayment($transferPayment->id);

				Event::fire('JobProgress.FinancialDetails.Events.PaymentUpdated', new JobPaymentUpdated($transferPayment));

            } else {
               // QBDesktopQueue::deletePayment($jobPayment->id);
				$qbAction = QuickBookTask::DELETE;
				$qbName = QuickBookTask::QUICKBOOKS_PAYMENT_DELETE;

				Event::fire('JobProgress.FinancialDetails.Events.PaymentCancelled', new JobPaymentCancelled($jobPayment));
			}

			$token = QuickBooks::getToken();

			if($token
				&& $jobPayment->quickbook_id
				&& $qbAction
				&& $qbName){
				QBOQueue::addTask(QuickBookTask::QUICKBOOKS_PAYMENT_DELETE, ['id'=>$jobPayment->id], [
					'object_id' => $jobPayment->id,
					'object' => QuickBookTask::PAYMENT,
					'action' => $qbAction,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
			}

        } catch (UnauthorizedException $e) {
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => Lang::get('response.success.canceled', ['attribute' => 'Job Payment']),
            'data' => $this->response->item($jobPayment, new JobPaymentTransformer),
            'total_amount' => $totalAmount,
            'unapplied_amount' => $unappliedPayment
        ]);
    }

    /**
     *
     * @param  [type] $id [description]
     * @return [string]     [message]
     */
    public function jobPaymentUpdate($id)
    {
        $input = Request::onlyLegacy('payment', 'method', 'echeque_number');
        $validator = Validator::make($input, FinancialDetail::getJobPaymentUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $jobPayment = JobPayment::findOrFail($id);

        try {
            $quickbookPaymentResponse = $this->quickBookService->jobInvoicePayment(
                $jobPayment->job_id,
                $input['method'],
                $input['payment'],
                $input['echeque_number'],
                $jobPayment->quickbook_id,
                $jobPayment->quickbook_sync_token,
                $invoiceLists,
                $jobPayment->customer
            );
            if (ine($quickbookPaymentResponse, 'quickbook_id')) {
                $input = array_merge($input, $quickbookPaymentResponse);
            }
            $input['modified_by'] = Auth::id();
            $jobPayment->update($input);

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Job payment']),
                'payment' => $jobPayment,
            ]);
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Get total amount received of a job
     * Get /job/total_amount_received/{id}
     *
     * @return Response
     */
    public function totalAmountReceived($id)
    {
        $job = Job::findOrFail($id);
        try {
            $customer = $job->customer;
            $totalReceivedAmount = $job->payments()->whereNull('canceled')->where('status', '=', 'closed')->sum('payment');
            $paymentIds = [];
            $paymentIds = $job->payments()->whereNull('canceled')->where('status', '=', 'closed')->pluck('id');
            $totalRefAmount = 0;
            foreach ($paymentIds as $paymentId) {
                $totalRefAmount += JobPayment::whereNull('canceled')->whereRefId($paymentId)->whereNull('ref_to')->sum('payment');
            }
            $totalAmount = $totalReceivedAmount - $totalRefAmount;
            $unappliedPayment = $customer
                ->payments()
                ->whereNull('canceled')
                ->sum('unapplied_amount');

            return ApiResponse::success([
                'data' => [
                    'total_amount' => $totalAmount,
                    'unapplied_amount' => $unappliedPayment
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Create Job Invoice
     * Post /jobs/create_job_invoice/{id}
     *
     * @return Response
     */
    public function newOrUpdateInvoice($jobId)
    {
        set_time_limit(0);
        $input = Request::onlyLegacy('description');

        $validator = Validator::make($input, ['description' => 'max:4000']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->jobRepo->getById($jobId);

        /* check job is awarded  or able to make */
        if (!SecurityCheck::isJobAwarded($job)) {
            return ApiResponse::errorInternal(trans('response.error.job_not_awarded'));
        }

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceService->createOrUpdateJobInvoice($job, $input);
            $job->invoice_id = $invoice->id;
            $job->update();
        } catch (JobAmountNotFoundException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (UnauthorizedException $e) {
            // DB::rollBack();
            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            // DB::rollBack();
            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.created', ['attribute' => 'Job Invoice']),
            'invoice' => $this->response->item($invoice, new JobInvoiceTransformer)
        ]);
    }

    /**
     * Financial Sum for multiple jobs
     * Get /jobs/financial_sum
     *
     * @param  $jobIds | Job Ids
     * @return Response
     */
    public function financialSum()
    {
        $jobIds = (array)Request::get('job_ids');
        $data = JobFinancialCalculation::getFinancialSum($jobIds);
        return ApiResponse::success(['data' => $data]);
    }

    /**
     * Get Invoice ids of jobs
     * Get /jobs/invoice_ids
     *
     * @param  $jobIds | Job Ids
     * @return Response
     */
    public function jobInvoiceIds()
    {
        $jobIds = (array)Request::get('job_ids');
        $data = Job::whereIn('id', $jobIds)->select('id', 'invoice_id')->get();
        return ApiResponse::success(['data' => $data]);
    }

    /**
     * Get Job Payment Received History
     * Get /jobs/payments_history/{jobid}
     *
     * @param  $jobId | Job Id
     * @return Response
     */
    public function jobPaymentsHistory($id)
    {
        $job = $this->jobRepo->getById($id);
        if ($job->isMultiJob()) {
            $payments = JobPayment::whereIn('job_id', function ($query) use ($id) {
                $query->select('id')->from('jobs')->whereParentId($id)
                    ->orWhere('id', $id);
            })->whereNull('credit_id')
            ->orderBY('id', 'desc')->get();
        } else {
            $payments = $job->payments;
        }

        return ApiResponse::success($this->response->collection($payments, new JobPaymentTransformer));
    }

    /**
     * Get Job Pricing History
     * Get /jobs/pricing_history/{jobid}
     *
     * @param  $jobId | Job Id
     * @return Response
     */
    public function jobPricingHistory($id)
    {
        $job = $this->jobRepo->getById($id);
        if ($job->isMultiJob()) {
            $jobId = $job->id;
            $pricingHistory = JobPricingHistory::whereIn('job_id', function ($query) use ($jobId) {
                $query->select('id')->from('jobs')->whereParentId($jobId)
                    ->orWhere('id', $jobId);
            })->orderBy('id', 'desc');
        } else {
            $pricingHistory = $job->pricingHistory();
        }

        $input = Request::all();

        if (isset($input['includes'])) {
            if (in_array('job', (array)$input['includes'])) {
                $pricingHistory->with('job');
            }

            if (in_array('created_by', (array)$input['includes'])) {
                $pricingHistory->with('createdBy');
            }
        }

        return ApiResponse::success($this->response->collection($pricingHistory->get(), new JobPricingHistoryTransformer));
    }

    /**
     * GET Jobs/financials
     * @return Response json finacial calculations
     */
    public function getJobFinancials()
    {
        $input = Request::onlyLegacy('job_ids');
        $financials = JobFinancialCalculation::whereIn('job_id', (array)$input['job_ids'])
            ->where('multi_job_sum', 0)
            ->get();

        return ApiResponse::success($this->response->collection($financials, new JobFinancialCalculationTransformer));
    }

    /********************* Private function ***************************/

    private function getValideData($data)
    {
        $details = [];
        $jobId = $data['job_id'];
        foreach ($data['categories'] as $category) {
            if (!ine($category, 'id') || !ine($category, 'details') || !(is_array($category['details']))) {
                continue;
            }

            foreach ($category['details'] as $detail) {
                if (!$detail = $this->getValideDetail($detail, $category['id'], $jobId)) {
                    continue;
                }
                $details[] = $detail;
            }
        }
        return $details;
    }

    private function getValideDetail($detail, $categoryId, $jobId)
    {

        $validator = Validator::make($detail, FinancialDetail::getDetailRules());
        if ($validator->fails()) {
            return false;
        }
        $detail['category_id'] = $categoryId;
        $detail['job_id'] = $jobId;
        return $detail;
    }

    private function saveDetails($details)
    {

        foreach ($details as $detail) {
            $detail = $this->repo->saveDetail(
                $detail['job_id'],
                $detail['category_id'],
                $detail['quantity'],
                $detail['product_name'],
                $detail['unit'],
                $detail['unit_cost'],
                $detail
            );
        }
    }

    private function updateInvoices($ids)
    {
        // $token = $this->quickBookService->getToken();
        if (empty($ids)) {
            return false;
        }

        $jobInvoices = JobInvoice::whereIn('id', $ids)
            ->with('payments')
            ->get();

        if (empty($jobInvoices)) {
            return false;
        }

        foreach ($jobInvoices as $invoice) {
            $this->invoiceService->updatePdf($invoice);
            // $this->quickBookService->createOrUpdateQbInvoicePdf($invoice, $token);
        }
    }

    /**
	 * Get all the payments used in API.
	 */
	public function getPaymentMethods()
	{
		$paymentMethods = $this->paymentMethodRepo->getAll();

		return ApiResponse::success($this->response->collection($paymentMethods, new PaymentMethodsTransformer));
	}
}
