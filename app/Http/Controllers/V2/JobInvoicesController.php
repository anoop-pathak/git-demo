<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\QuickBookException;
use App\Exceptions\AuthorizationException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\JobInvoice;
use App\Repositories\JobInvoiceRepository;
use App\Repositories\JobRepository;
use App\Repositories\ProposalsRepository;
use App\Services\JobInvoices\JobInvoiceService;
use App\Transformers\JobInvoiceTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\CustomTaxRepository;
use QuickBooks;
use Illuminate\Support\Facades\Event;
use App\Events\InvoiceCreated;
use App\Events\InvoiceUpdated;
use App\Events\InvoiceDeleted;
use App\Exceptions\InvoiceLeastAmountException;

class JobInvoicesController extends ApiController
{

    function __construct(
        Larasponse $response,
        JobInvoiceRepository $repo,
        JobInvoiceService $invoiceService,
        JobRepository $jobRepo,
        ProposalsRepository $proposalRepo,
        CustomTaxRepository $customRepo
    ) {
        $this->response = $response;
        $this->repo = $repo;
        $this->invoiceService = $invoiceService;
        $this->jobRepo = $jobRepo;
        $this->proposalRepo = $proposalRepo;
        $this->customRepo = $customRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Invoice Save
     * Post /jobs/invoice
     * @return Response
     */
    public function createInvoice()
    {
        $input = Request::all();
        $rules = JobInvoice::getRules();

		$messages = [];

		// Remove validation because user is not able to perform any operation for JP
		/*
		if(QuickBooks::isConnected() && ine($input, 'taxable')) {
			$rules['custom_tax_id'] = 'required';
			$messages['custom_tax_id.required'] = 'Tax id is required';
		}
		if(QuickBooks::isConnected() && ine($input, 'taxable') && ine($input, 'custom_tax_id')) {
			$customTaxId = $input['custom_tax_id'];
			if($customTaxId && !$this->customRepo->isValidQuickBookTax($input['custom_tax_id'])) {
				$rules['custom_tax_id'] = 'required';
				$messages['custom_tax_id.required'] = trans('response.error.quickbook_tax_is_not_selected');
			}
		}*/

		$validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->jobRepo->getById($input['job_id']);

        if (ine($input, 'proposal_id')) {
            $this->proposalRepo->getById($input['proposal_id']);
        }

        /* check job is awarded  or able to make */
        if (!SecurityCheck::isJobAwarded($job)) {
            return ApiResponse::errorInternal(trans('response.error.job_not_awarded'));
        }

        $input['invoice_number'] = $this->invoiceService->getInvoiceNumber();

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceService->createJobInvoice($job, $input['lines'], $input);
        } catch (AuthorizationException $e) {
            //Do nothing
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            //Do nothing
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvoiceLeastAmountException $e) {
			DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        Event::fire('JobProgress.JobInvoices.Events.InoviceCreated', new InvoiceCreated($invoice));

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Invoice']),
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

        $rules = JobInvoice::getUpdateRules();

		// Remove validation because user is not able to perform any operation for JP
		/*
		if(QuickBooks::isConnected() && ine($input, 'taxable')) {
			$rules['custom_tax_id'] = 'required';
			$messages['custom_tax_id.required'] = 'Tax id is required';
		}
		if(QuickBooks::isConnected() && ine($input, 'taxable') && ine($input, 'custom_tax_id')) {
			$customTaxId = $input['custom_tax_id'];
			if($customTaxId && !$this->customRepo->isValidQuickBookTax($input['custom_tax_id'])) {
				$rules['custom_tax_id'] = 'required';
				$messages['custom_tax_id.required'] = trans('response.error.quickbook_tax_is_not_selected');
			}
		}
		*/

		$validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $invoice = $this->repo->getById($invoiceId, ['job']);

        if (ine($input, 'proposal_id')) {
            $this->proposalRepo->getById($input['proposal_id'], $with = [], ['with_trashed' => true]);
        }

        $job = $invoice->job;

        /*
		if(QuickBooks::isConnected() && ine($input, 'taxable')) {
			$customTaxId = $input['custom_tax_id'];
			if($customTaxId && !$this->customRepo->isValidQuickBookTax($input['custom_tax_id'])) {
				return ApiResponse::errorInternal(trans('response.error.quickbook_tax_is_not_selected'));
			}
		}
		*/

        /* check job is awarded  or able to make */
        if (!SecurityCheck::isJobAwarded($job)) {
            return ApiResponse::errorInternal(trans('response.error.job_not_awarded'));
        }

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceService->updateJobInvoice($invoice, $input['lines'], $input);
        } catch (AuthorizationException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvoiceLeastAmountException $e) {
			DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        Event::fire('JobProgress.JobInvoices.Events.InoviceUpdated', new InvoiceUpdated($invoice));

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Invoice']),
            'job_invoice' => $this->response->item($invoice, new JobInvoiceTransformer)
        ]);
    }

    /**
     * Delete job invoice
     * Delete /jobs/invoice
     * @return Response
     */
    public function deleteJobInvoice()
    {
        $input = Request::onlyLegacy('invoice_id', 'password', 'reason');

        $validator = Validator::make($input, JobInvoice::getDeleteJobInvoiceRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $invoice = $this->repo->getById($input['invoice_id']);

        DB::beginTransaction();
        try {
            /**
			 * Delete invoice from QuickBooks and and JobProgress
			 * Second argument says delete from QuickBooks or not
			 * Second argument indroduced beacuse this function is used in Quickbook webhooks also
			 * so if invoice is already deleted then we can pass false as second arugment to not try to delete is on QuickBooks.
			 */
			$this->invoiceService->deleteJobInvoice($invoice, true);
        } catch (AuthorizationException $e) {
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        Event::fire('JobProgress.JobInvoices.Events.InoviceDeleted', new InvoiceDeleted($invoice));

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Job invoice'])
        ]);
    }
}
