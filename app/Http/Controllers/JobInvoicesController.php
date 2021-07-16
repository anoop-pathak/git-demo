<?php

namespace App\Http\Controllers;

use App\Exceptions\QuickBookException;
use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Repositories\JobInvoiceRepository;
use App\Repositories\JobRepository;
use App\Repositories\ProposalsRepository;
use FlySystem;
use App\Services\JobInvoices\JobInvoiceService;
use App\Services\QuickBooks\QuickBookService;
use App\Transformers\EstimationsTransformer;
use App\Transformers\JobInvoiceTransformer;
use App\Transformers\MaterialListTransformer;
use App\Transformers\ProposalsTransformer;
use App\Transformers\WorkOrderTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\QuickBooks;

class JobInvoicesController extends Controller
{

    function __construct(
        Larasponse $response,
        JobInvoiceRepository $repo,
        QuickBookService $qbService,
        ProposalsRepository $proposalRepo,
        JobInvoiceService $invoiceService,
        JobRepository $jobRepo
    ) {
        $this->response = $response;
        $this->repo = $repo;
        $this->qbService = $qbService;
        $this->proposalRepo = $proposalRepo;
        $this->invoiceService = $invoiceService;
        $this->proposalRepo = $proposalRepo;
        $this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Invoice Listing
     * Get /jobs/invoice
     * @return Response
     */
    public function searchInvoice()
    {
        $input = Request::all();
        // $validator = Validator::make($input, ['invoice_id' => 'required']);
        // if ($validator->fails()) {
        //     return ApiResponse::validation($validator);
        // }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $invoices = $this->invoiceService->getFilteredInvoice($input);

        if (!$limit) {
            $invoices = $invoices->get();

            return ApiResponse::success($this->response->collection($invoices, new JobInvoiceTransformer));
        }
        $invoices = $invoices->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($invoices, new JobInvoiceTransformer));
    }

    /**
     * Get job invoice
     * Get jobs/{job_id}/invoices
     * @param  int $jobId job id
     * @return response
     */
    public function getJobInvoices($jobId)
    {
        $input = Request::all();
        $validator = Validator::make($input, ['status' => 'In:closed,open,all']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::findOrFail($jobId);
        if(!ine($input, 'status')) {
           $input['status'] = 'open';
        }

        if($input['status'] == 'all') {
            unset($input['status']);
        }
        $input['job_id'] = $jobId;
        $invoices = $this->invoiceService->getFilteredInvoice($input);

        return ApiResponse::success(
            $this->response->collection($invoices->get(), new JobInvoiceTransformer)
        );
    }

    /**
     * Get Single Job Invoice
     * @param  Int $id Invoice Id
     * @return Response
     */
    public function show($id)
    {
        $invoice = $this->repo->getById($id);

        return ApiResponse::success([
            'job_invoice' => $this->response->item($invoice, new JobInvoiceTransformer)
        ]);
    }

    /**
     * Update Invoice
     * Put /jobs/invoice/{id}
     * @param  Int $invoiceid [description]
     * @return Json Response
     */
    public function update($invoiceId)
    {
        $input = Request::all();
        $invoice = $this->repo->getById($invoiceId);

        DB::beginTransaction();
        try {
            $this->invoiceService->updateDates($invoice, $input);
        } catch (QuickBookException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Invoice']),
            'job_invoice' => $this->response->item($invoice, new JobInvoiceTransformer)
        ]);
    }

    /**
     * get system invoice; a public route which would require job share token as well in request for security.
     * So that no user can see other invoices by just changing the invoiceId in url.
     * It calls the getJobInvoice method form
     * @param  Int $invoiceId  Invoice Id
     * @return Response
     */
    public function getJobInvoicePublic($invoiceId)
    {
        $token = Request::get('token');
        try {
            $jobInvoice = JobInvoice::findOrFail($invoiceId);
            setScopeId($jobInvoice->customer->company_id);
            
            if(!$token || ($jobInvoice->job->share_token != $token)) {
                return view('errors.404', [
                    'errorDetail' => "You are not allowed to view this invoice.",
                    'message'     => trans('response.error.not_found_page'),
                ]);
            }
        
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('errors.404', [
                'errorDetail' => $e->getMessage(),
                'message'     => trans('response.error.not_found_page'),
            ]);
        }
        return $this->getJobInvoice($invoiceId);
    }

    /**
     * get system invoice
     * @param  Int $invoiceId Invoice Id
     * @return Response
     */
    public function getJobInvoice($invoiceId)
    {
        $jobInvoice = JobInvoice::findOrFail($invoiceId);
        setScopeId($jobInvoice->customer->company_id);
        $input = Request::onlyLegacy('download');
        try {
            $fileResource = null;

            if($token = QuickBooks::getToken()) {
                $fileResource = QBInvoice::getPdf($jobInvoice);
            }

            if (!$fileResource) {
                $path = config('jp.BASE_PATH') . $jobInvoice->file_path;

                if (!$jobInvoice->file_size) {
                    $path = 'public/' . $jobInvoice->file_path;
                }
                $fileResource = FlySystem::read($path);
            }

            $response = \response($fileResource, 200);

            $response->header('Content-Type', 'application/pdf');

            $filename = $jobInvoice->id . '_invoice.pdf';

            if (!$input['download']) {
                $response->header('Content-Disposition', 'filename="' . $filename . '"');
            } else {
                $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }

            return $response;
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.error_page'),
            ]);
        }
    }

    /**
     * Attach Proposal
     * @param  Int $invoiceId Invoice Id
     * @return Response
     */
    public function proposalLink()
    {
        $input = Request::onlyLegacy('proposal_id', 'invoice_id');

        $validator = Validator::make($input, ['invoice_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $invoice = $this->repo->getById($input['invoice_id']);
        $job = $invoice->job;

        $proposalId = ine($input, 'proposal_id') ? $input['proposal_id'] : null;
        $message = trans('response.success.invoice_unlinked', ['attribute' => 'Proposal']);

        if ($proposalId) {
            $this->proposalRepo->getById($input['proposal_id']);
            $message = trans('response.success.invoice_link', ['attribute' => 'Proposal']);
        }
        try {
            $invoice->proposal_id = $proposalId;

            $job->jobInvoices()->update(['proposal_id' => $proposalId]);

            return ApiResponse::success([
                'message' => $message,
                'job_invoice' => $this->response->item($invoice, new JobInvoiceTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get invoice linked data
     * Put /jobs/{id}/invoice_linked_data
     * @param  Int $jobId Job Id
     * @return Response
     */
    public function invoiceLinkedData($jobId)
    {
        $job = $this->jobRepo->getById($jobId);
        $jobInvoices = $job->jobInvoices;
        if (!$jobInvoices->count()) {
            return ApiResponse::errorNotFound('JobInvoice Not Found');
        }
        try {
            $invoice = $job->jobInvoices->first();
            $proposal = $invoice->proposal;
            $invoiceTrans = new JobInvoiceTransformer;
            $invoiceTrans->setDefaultIncludes([]);

            $proposalData = $estimateData = $workOrderData = $materialData = null;

            if ($proposal) {
                //get proposal data
                $proposalTrans = new ProposalsTransformer;
                $proposalTrans->setDefaultIncludes([]);
                $proposalData = $this->response->item($proposal, $proposalTrans);

                //get linked estimate
                if ($estimate = $proposal->linkedEstimate) {
                    $estimationTrans = new EstimationsTransformer;
                    $estimationTrans->setDefaultIncludes([]);
                    $estimateData = $this->response->item($estimate, $estimationTrans);
                }

                //get linked material list data
                $materialList = null;
                //if($materialList = $proposal->getMaterialList()) {
                if ($materialList) {
                    $materialListTrans = new MaterialListTransformer;
                    $materialListTrans->setDefaultIncludes([]);
                    $materialData = $this->response->item($materialList, $materialListTrans);
                }

                //get linked work order data
                if ($workOrder = $proposal->getWorkOrder()) {
                    $workOrderTrans = new WorkOrderTransformer;
                    $workOrderTrans->setDefaultIncludes([]);

                    $workOrderData = $this->response->item($workOrder, $workOrderTrans);
                }
            }

            $data = [
                'invoices' => $this->response->collection($jobInvoices, $invoiceTrans),
                'linked_proposal' => $proposalData,
                'linked_estimate' => $estimateData,
                'linked_material_list' => $materialData,
                'linked_work_order' => $workOrderData,
            ];

            return ApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
