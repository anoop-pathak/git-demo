<?php

namespace App\Http\Controllers;

use App\Exceptions\QuickBookException;
use App\Exceptions\AuthorizationException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\ChangeOrder;
use App\Models\JobPayment;
use App\Repositories\ChangeOrdersRepository;
use App\Repositories\JobRepository;
use App\Services\JobInvoices\JobInvoiceService;
use App\Services\QuickBooks\QuickBookService;
use App\Transformers\ChangeOrderTransformer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\CustomTaxRepository;
use App\Events\InvoiceCreated;
use App\Exceptions\InvoiceLeastAmountException;
use App\Services\ChangeOrdersService;
use App\Exceptions\ChangeOrderLeastAmountException;
use App\Events\InvoiceUpdated;
use Exception;
use Illuminate\Support\Facades\Event;
use App\Models\QuickBookTask;

class ChangeOrdersController extends ApiController
{

    protected $repo;
    protected $jobRepo;

    public function __construct(
        ChangeOrdersRepository $repo,
        JobRepository $jobRepo,
        QuickBookService $quickService,
        Larasponse $response,
        JobInvoiceService $invoiceService,
		CustomTaxRepository $customRepo,
		ChangeOrdersService $service
    ) {

        $this->repo = $repo;
        $this->jobRepo = $jobRepo;
        $this->quickBookService = $quickService;
        $this->invoiceService = $invoiceService;
        $this->response = $response;
        $this->customRepo = $customRepo;
		$this->service = $service;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Save Change Order
     * POST /jobs/change_orders
     *
     * @return Response
     */
    public function saveChangeOrder()
    {

        // Prevent if request from mobile (allow force save)
        if (config('is_mobile') && !Request::has('force')) {
            return ApiResponse::errorInternal(trans('response.error.temporarily_unavailable', ['attribute' => 'Change Order']));
        }

        $input = Request::all();

        $rules = ChangeOrder::getRules();
        $messages = [];

		// Remove validation because user is not able to perform any operation for JP
		/*
		if (QuickBooks::isConnected() && ine($input, 'taxable')) {
			$rules['custom_tax_id'] = 'required';
			$messages['custom_tax_id.required'] = 'Tax id is required';
		}
		if (QuickBooks::isConnected() && ine($input, 'taxable') && ine($input, 'custom_tax_id')) {
			$customTaxId = $input['custom_tax_id'];
			if ($customTaxId && !$this->customRepo->isValidQuickBookTax($input['custom_tax_id'])) {
				$rules['custom_tax_id'] = 'required';
				$messages['custom_tax_id.required'] = trans('response.error.quickbook_tax_is_not_selected');
			}
		}
        */
        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $job = $this->jobRepo->getById($input['job_id']);

        /* check job is awarded  or able to make */
        if (!SecurityCheck::isJobAwarded($job)) {
            return ApiResponse::errorInternal(trans('response.error.job_not_awarded'));
        }

        $input['invoice_number'] = $this->invoiceService->getInvoiceNumber();

        DB::beginTransaction();
        try {
            $input['created_by'] = \Auth::id();
            $input['order'] = $job->changeOrderHistory->count() + 1;
            $changeOrderAmount = $this->service->getTotalAmount($input['entities']);
            $changeOrder = $this->repo->save($job, $input['entities'], $input);

            if (!ine($input, 'create_without_invoice')) {
                //create invoice
                $invoice = $this->invoiceService->updateChangeOrderInvoice($changeOrder, $input);

                $changeOrder->update([
                    'invoice_id' => $invoice->id,
                    'invoice_updated' => true
                ]);
            }
        } catch (AuthorizationException $e) {
            //Do Nothing
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            //Do Nothing
            // DB::rollBack();

            // return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvoiceLeastAmountException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch(ChangeOrderLeastAmountException $e) {
            DB::rollBack();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(Exception $e){
            DB::rollBack();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        if(!ine($input, 'create_without_invoice')) {

			Event::fire('JobProgress.JobInvoices.Events.InoviceCreated', new InvoiceCreated($invoice));
		}

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Change Order']),
            'change_order' => $this->response->item($changeOrder, new ChangeOrderTransformer),
        ]);
    }

    /**
     * update Change Order
     * Put /jobs/change_orders/{id}
     *
     * @return Response
     */
    public function updateChangeOrder($id)
    {
        $input = Request::all();
        $rules = ChangeOrder::getUpdateRules();
        $messages = [];
        // Remove validation because user is not able to perform any operation for JP
		/*
		if (QuickBooks::isConnected() && ine($input, 'taxable')) {
			$rules['custom_tax_id'] = 'required';
			$messages['custom_tax_id.required'] = 'Tax id is required';
		}
		if (QuickBooks::isConnected() && ine($input, 'taxable') && ine($input, 'custom_tax_id')) {
			$customTaxId = $input['custom_tax_id'];
			if ($customTaxId && !$this->customRepo->isValidQuickBookTax($input['custom_tax_id'])) {
				$rules['custom_tax_id'] = 'required';
				$messages['custom_tax_id.required'] = trans('response.error.quickbook_tax_is_not_selected');
			}
		}
		*/

		$validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $changeOrder = $this->repo->getById($id);

        DB::beginTransaction();
        try {
            $input['modified_by'] = \Auth::id();
            $changeOrderAmount = $this->service->getTotalAmount($input['entities']);
            $changeOrder = $this->repo->update($changeOrder, $input['entities'], $input);

            if (!ine($input, 'update_without_invoice')) {
                //update invoice
                $invoice = $this->invoiceService->updateChangeOrderInvoice($changeOrder, $input);
                $changeOrder->invoice_updated = true;
                $changeOrder->invoice_id = $invoice->id;
                $changeOrder->save();
            }
        } catch (QuickBookException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvoiceLeastAmountException $e) {
			DB::rollBack();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ChangeOrderLeastAmountException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        if(!ine($input, 'update_without_invoice')) {
			Event::fire('JobProgress.JobInvoices.Events.InoviceUpdated', new InvoiceUpdated($invoice));
        }

        $changeOrder = $this->repo->getById($id);

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Change Order']),
            'change_order' => $this->response->item($changeOrder, new ChangeOrderTransformer),
        ]);
    }

    /**
     * Approved Change order
     * Put /jobs/change_order_approval
     *
     * @return Response
     */
    public function approval()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $job = $this->jobRepo->getById($input['job_id']);
        //disapproved previous..
        $job->changeOrderHistory()->update(['approved' => false]);
        // get latest
        $changeOrder = $job->changeOrder;
        if (!$changeOrder) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Change Order']));
        }
        $changeOrder->approved = true;
        $changeOrder->save();
        return ApiResponse::success([
            'message' => trans('response.success.approved_successfully')
        ]);
    }

    /**
     * Get Change order
     * GET /jobs/change_orders
     *
     * @return Response
     */
    public function getChangeOrder()
    {
        $input = Request::onlyLegacy('job_id', 'id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $job = $this->jobRepo->getById($input['job_id']);
        if (!ine($input, 'id')) {
            $changeOrder = $job->changeOrder;
        } else {
            $changeOrder = $job->changeOrderHistory()->whereId($input['id'])->first();
        }
        if ($changeOrder) {
            $changeOrder = $this->response->item($changeOrder, new ChangeOrderTransformer);
        }
        return ApiResponse::success(['data' => (array)$changeOrder]);
    }

    /**
     * Get change order by invoice id
     * Get /jobs/change_order/by_invoice_id/{id}
     * @param  Int $id Change Order id
     * @return Change order
     */
    public function getByInvoiceId($id)
    {
        $changeOrder = $this->repo->getByInvoiceId($id);

        return ApiResponse::success([
            'data' => $this->response->item($changeOrder, new ChangeOrderTransformer)
        ]);
    }

    public function changeOrderSum()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $job = $this->jobRepo->getById($input['job_id']);

        //send sum of total_payment and count
        $changeOrderMeta = $job->changeOrderHistory()
            ->whereNull('canceled')
            ->select(DB::raw('sum(IF(taxable = 1, (total_amount + (total_amount * tax_rate * 0.01)), total_amount)) as total_amount, count(*) as count'))
            ->first('total_amount', 'count');

        return ApiResponse::success([
            'data' => $changeOrderMeta
        ]);
    }

    /**
     * Change Order history
     * Get /jobs/change_order_history
     *
     * @return Response
     */
    public function changeOrderHistory()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $job = $this->jobRepo->getById($input['job_id']);
        if ($job->isMultiJob()) {
            $projectIds = $job->projects->pluck('id')->toArray();
            $projectIds[] = $job->id;
            $changeOrders = ChangeOrder::whereIn('job_id', $projectIds)->get();
        } else {
            $changeOrders = $job->changeOrderHistory;
        }

        return ApiResponse::success($this->response->collection($changeOrders, new ChangeOrderTransformer));
    }

    /**
     * Delete Change Order (with history)
     * Delete /jobs/delete_change_order_history
     *
     * @return Response
     */
    public function deleteChangeOrderHistory()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //validate job Id
        $job = $this->jobRepo->getById($input['job_id']);

        //delete(soft) history
        try {
            $job->changeOrderHistory()->delete();

            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Change Order']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function cancelChangeOrder($id)
    {
        $changeOrder = $this->repo->getById($id);
        DB::beginTransaction();
        try {
            $totalAmount = $changeOrder->job->changeOrderHistory()->whereNull('canceled')->sum('total_amount');
            $invoice = $changeOrder->invoice;

            if ($invoice) {
                $paymentIds = $invoice->jobPayments->pluck('id')->toArray();
                JobPayment::whereIn('id', $paymentIds)
                    ->update(['status' => 'unapplied']);

                $invoice->payments()->delete();
                $invoice->delete();

                $token = QuickBooks::getToken();
                if($token && $invoice->quickbook_invoice_id) {
                    QBOQueue::addTask(QuickBookTask::QUICKBOOKS_INVOICE_DELETE, [
						'id' => $invoice->id
					], [
						'object_id' => $invoice->id,
						'object' => QuickBookTask::INVOICE,
						'action' => QuickBookTask::DELETE,
						'origin' => QuickBookTask::ORIGIN_JP,
						'created_source' => QuickBookTask::SYSTEM_EVENT
					]);
				}
            }

            $changeOrder->canceled = \Carbon\Carbon::now()->toDateTimeString();
            $changeOrder->modified_by = \Auth::id();
            $changeOrder->save();
        } catch (AuthorizationException $e) {
            DB::rollBack();

            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (QuickBookException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();
        return ApiResponse::success([
            'message' => trans('response.success.canceled', ['attribute' => 'Change Order']),
            'change_order' => $this->response->item($changeOrder, new ChangeOrderTransformer),
            'total_amount' => $totalAmount
        ]);
    }
}
