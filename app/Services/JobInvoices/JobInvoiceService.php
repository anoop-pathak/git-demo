<?php

namespace App\Services\JobInvoices;

use App\Models\Company;
use App\Models\InvoicePayment;
use App\Models\JobFinancialCalculation;
use App\Models\JobInvoice;
use App\Models\JobInvoiceNumber;
use App\Models\JobInvoiceLine;
use App\Models\JobPayment;
use App\Models\JobPricingHistory;
use App\Repositories\JobInvoiceRepository;
use FlySystem;
use PDF;
use QBDesktopQueue;
use App\Services\QuickBooks\QuickBookService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\QBOQueue;
use QuickBooks;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Event;
use App\Events\InvoiceCreated;
use App\Exceptions\InvoiceLeastAmountException;

class JobInvoiceService
{

    public function __construct(QuickBookService $quickBookService, JobInvoiceRepository $repo)
    {
        $this->quickBookService = $quickBookService;
        $this->repo = $repo;
    }

    /**
     * Create Job Invoice
     * @param  Instance $job Job instance
     * @param  array $lines Lines
     * @param  array $meta Array of meta
     * @return Invoice
     */
    public function createJobInvoice($job, $lines, $meta = [])
    {
        $totalInvoceAmount = 0;
		$totalInvoceAmount = array_sum(array_map(function($item) {
			return $item['amount'] * $item['quantity'];
		}, $lines));

		if($totalInvoceAmount < 0) {
			throw new InvoiceLeastAmountException(trans('response.error.least_amount', ['attribute' => 'Job Invoice']));
        }

        $title = 'Job Invoice';
        if ($job->isProject()) {
            $title = 'Project Invoice';
        }

        $order = $this->repo->getJobInvoiceOrder($job->id);
        if ($order > 1) {
            $title .= ' #' . $order;
        }

        $meta['order'] = $order;
        $meta['type'] = JobInvoice::JOB;
        $invoice = $this->createInvoice($job, $title, $lines, $meta);

        $job = $invoice->job;
		$invoiceSum = $this->repo->getJobInvoiceSum($job->id);

        if (ine($meta, 'update_job_price')) {
            $job = $this->updateJobPricing($job, $invoiceSum, $invoice);
        }

        if(ine($meta, 'update_job_tax_rate')) {
            $job = $this->updateTaxRate($invoiceSum, $invoice);
        }

        if(ine($meta, 'update_job_price') || ine($meta, 'update_job_tax_rate')) {
			JobPricingHistory::maintainHistory($job);
		}

        //update job invoce amount and its tax rate
        JobFinancialCalculation::updateJobInvoiceAmount(
            $job,
            $invoiceSum->job_amount,
            $invoiceSum->tax_amount
        );

        // QBDesktopQueue::addInvoice($invoice->id);

        return $invoice;
    }

    /**
     * update tax rate
     * @param  Instance $invoiceSum InvoiceSum
     * @param  Instance $invoice    Invoice
     * @return Boolean
     */
    public function updateTaxRate($invoiceSum, $invoice)
    {
        $job = $invoice->job;
        $taxRate = calculateTaxRate($invoiceSum->job_amount, $invoiceSum->tax_amount);

		$job->tax_rate = $taxRate;
        $job->taxable  = 1;
        $job->update();
        return $job;
    }

    /**
     * Update job Invoice
     * @param  Instance $invoice Job invoice
     * @param  array $liens Lines
     * @param  array $meta Array of meta
     * @return Invoice
     */
    public function updateJobInvoice($invoice, $lines, $meta = [])
    {
        $totalInvoceAmount = 0;
		$totalInvoceAmount = array_sum(array_map(function($item) {
			return $item['amount'] * $item['quantity'];
		}, $lines));

		if($totalInvoceAmount < 0) {
			throw new InvoiceLeastAmountException(trans('response.error.least_amount', ['attribute' => 'Job Invoice' ]));
		}

		$invoicePayments = $invoice->payments->sum('amount');

		$updatedAmount = 0;

		foreach($lines as $line) {
			$invoiceAmount = $line['amount'] * $line['quantity'];
			$updatedAmount = $updatedAmount + $invoiceAmount;
		}

		if($updatedAmount < $invoicePayments) {
			throw new InvoiceLeastAmountException('Invoice amount must be greater then payment received.');
        }

        $invoice = $this->updateInvoice($invoice, $lines, $meta);
        $job = $invoice->job;

        //update job price
        $invoiceSum = $this->repo->getJobInvoiceSum($job->id);
        if (ine($meta, 'update_job_price')) {
            $job = $this->updateJobPricing($job, $invoiceSum, $invoice);
			JobPricingHistory::maintainHistory($job);
        }
        //update job invoce amount and its tax rate
        JobFinancialCalculation::updateJobInvoiceAmount(
            $job,
            $invoiceSum->job_amount,
            $invoiceSum->tax_amount
        );

        // QBDesktopQueue::addInvoice($invoice->id);

        return $invoice;
    }

    public function updateDates($invoice, $meta = [])
    {
        if (isset($meta['due_date'])) {
            $invoice->due_date = ine($meta, 'due_date') ? $meta['due_date'] : null;
        }

        if (ine($meta, 'date')) {
            $invoice->date = ine($meta, 'date') ? $meta['date'] : $invoice->getCreatedDate();
        }

        if (isset($meta['due_date']) || isset($meta['date'])) {
            $invoice->save();

            //update pdf
            $this->updatePdf($invoice);

            //quickbook invoice update
            $token = Quickbooks::getToken();
            if ($token) {
                QBOQueue::addTask(QuickBookTask::QUICKBOOKS_INVOICE_UPDATE, [
					'id' => $invoice->id
				], [
					'object_id' => $invoice->id,
					'object' => QuickBookTask::INVOICE,
					'action' => QuickBookTask::UPDATE,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
            }
        }
    }

    /**
     * Update change order invoice
     * @param  Instance $changeOrder Change Order
     * @param  array $meta Meta
     * @return Response
     */
    public function updateChangeOrderInvoice($changeOrder, $meta = [])
    {
        $job = $changeOrder->job;

        $meta['tax_rate'] = $changeOrder->tax_rate;
        $meta['taxable'] = $changeOrder->taxable;
        $meta['custom_tax_id'] = $changeOrder->custom_tax_id;
        $meta['type'] = JobInvoice::CHANGE_ORDER;
        $meta['order'] = $changeOrder->order;
        $meta['note'] = $changeOrder->invoice_note;

        $invoice = $changeOrder->invoice;

        //make invoices lines from change order entities
        $lines = [];

        foreach ($changeOrder->entities as $entity) {
            $lines[] = [
                'tier1' => $entity->tier1,
                'tier2' => $entity->tier2,
                'tier3' => $entity->tier3,
                'amount' => $entity->amount,
                'description' => $entity->description,
                'quantity' => $entity->quantity,
                'product_id' => $entity->product_id,
                'trade_id'    => $entity->trade_id,
                'work_type_id'  => $entity->work_type_id,
                'is_chargeable'  => $entity->is_chargeable,
            ];
        }

        //save or update invoice
        if ($invoice) {
            $invoice = $this->updateInvoice($invoice, $lines, $meta);
        } else {
            $title = 'Change Order #' . $changeOrder->order;
            $invoice = $this->createInvoice($job, $title, $lines, $meta);
        }

        return $invoice;
    }


    /**
     * Create or Update Job  Invoice
     * @param  Instance $job Job Instance
     * @param  array $meta Aray of meta
     * @return Invoice
     */
    public function createOrUpdateJobInvoice($job, $meta = [])
    {
        //Create system job description and details.
        $title = 'Job Invoice';

        //set invoice description
        if (!ine($meta, 'description')) {
            $customer = $job->customer;
            $trades = $job->trades->pluck('name')->toArray();
            $description = $job->number . ' / ';
            if (in_array('OTHER', $trades) && ($job->other_trade_type_description)) {
                $otherKey = array_search('OTHER', $trades);
                unset($trades[$otherKey]);
                $other = 'OTHER - ' . $job->other_trade_type_description;
                array_push($trades, $other);
            }
            $description .= implode(', ', $trades);
        } else {
            $description = $meta['description'];
        }


        // tax rate
        $meta['tax_rate'] = $job->tax_rate;
        $meta['taxable'] = $job->taxable;
        $meta['custom_tax_id'] = $job->custom_tax_id;
        $meta['type'] = JobInvoice::JOB;

        //create lines
        $lines[] = [
            'amount' => $job->amount,
            'description' => $description,
            'quantity' => 1,

        ];

        //save or update invoice
        if (($invoice = $job->invoice)) {
            $invoice = $this->updateInvoice($invoice, $lines, $meta);
        } else {
            $invoice = $this->createInvoice($job, $title, $lines, $meta);
        }

        return $invoice;
    }

    /**
     * Get Open API Filtered Invoice
     * @param  array $filters Filters
     * @return QueryBuilder
     */
    public function getOpenApiFilteredInvoice($filters = [])
    {
        if(!ine($filters, 'status')) {
            $filters['status'] = 'open';
        }

        if($filters['status'] == 'all') {
            unset($filters['status']);
        }

        return $this->repo->getFilteredInvoice($filters);
    }

    /**
     * Get Filtered Invoice
     * @param  array $filters Filters
     * @return QueryBuilder
     */
    public function getFilteredInvoice($filters = [])
    {
        return $this->repo->getFilteredInvoice($filters);
    }

    /**
     * Update Invoice Pdf
     * @param  Instance $invoice Job Invoice
     * @return Invoice
     */
    public function updatePdf($invoice)
    {
        $job = $invoice->job;
        $customer = $job->customer;
        $company = Company::find($customer->company_id);

        $oldFilePath = null;
        if (!empty($invoice->file_path)) {
            $oldFilePath = config('jp.BASE_PATH') . $invoice->file_path;
        }


        // Some record have incorect file path saved (If file size is null then need to correct the path in database)
        if (!$invoice->file_size && !empty($invoice->file_path)) {
            $oldFilePath = 'public/' . $invoice->file_path;
        }

        $fileName = $invoice->id . '_' . timestamp() . '.pdf';
        $baseName = 'job_invoices/' . $fileName;
        $fullPath = config('jp.BASE_PATH') . $baseName;

        //getting payment methods.
        $paymentMethods = $invoice->jobPayments()
            ->groupBy('method')
            ->orderBy('invoice_payments.id', 'asc')
            ->get()
            ->pluck('method')->toArray();

        $amountPaid = $invoice->payments()->whereNull('credit_id')->sum('amount');
        $applyCredits = InvoicePayment::whereInvoiceId($invoice->id)->whereNotNull('credit_id')->sum('amount');

        $contents = view('jobs.job_invoice', [
            'invoice' => $invoice,
            'customer' => $customer,
            'company' => $company,
            'payment_methods' => $paymentMethods,
            'job' => $job,
            'amount_paid'     => $amountPaid,
            'apply_credits'   => $applyCredits,
        ])->render();

        $pdf = PDF::loadHTML($contents)->setOption('page-size', 'A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', '0.8cm')
			->setOption('margin-bottom', '0.8cm');

        FlySystem::put($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);

        $status = JobInvoice::OPEN;
        if ((float)$invoice->open_balance <= 0) {
            $status = JobInvoice::CLOSED;
        }

        $invoice->status = $status;
        $invoice->file_path = $baseName;
        $invoice->file_size = FlySystem::getSize($fullPath);
        $invoice->update();

        $this->fileDelete($oldFilePath);

        return $invoice;
    }

    /**
     * Update quickbok
     * @param  Object $token Token
     * @param  Instance $invoice Invoice
     * @return Void
     */
    public function updateQuickbookInvoice($token, $invoice)
    {
        $customer = $invoice->customer;
        $job = $invoice->job;
        QBInvoice::createOrUpdateInvoice($invoice);
        $paymentIds = InvoicePayment::whereInvoiceId($invoice->id)->whereNull('credit_id')->pluck('payment_id')->toArray();
        $invoicePaymentId = InvoicePayment::whereInvoiceId($invoice->id)->pluck('payment_id')->toArray();
        $creditIds        = InvoicePayment::whereInvoiceId($invoice->id)->whereNotNull('credit_id')->pluck('credit_id')->toArray();
        QBPayment::paymentsSync(array_filter((array)$paymentIds), $customer->quickbook_id);

        # sync credit payments #
        $credits = App::make('App\Services\Credits\JobCredits');
        $credits->syncJobUpdateCredits($token, $job, $creditIds);
    }

    /**
     * Create QuickBook Invoice
     * @param  Object   $token   Token
     * @param  Instance $invoice Invoice
     * @return Void
     */
    public function createQuickbookInvoice($token, $invoice)
    {
        $customer = $invoice->job->customer;
        QBInvoice::createOrUpdateInvoice($invoice);
    }

    /*
	 * Invoice Linking
	 * @param  Instance $invoice  Job Invoice
	 * @param  Int      $linkId   Link Id
	 * @param  String   $linkType Link Type(proposal, estimate)
	 * @return Invoice
	 */
    public function linking($invoice, $linkId, $linkType)
    {
        switch ($linkType) {
            case JobInvoice::PROPOSAL:
                $this->proposalRepo->getById($linkId);
                break;
            case JobInvoice::ESTIMATE:
                $this->estimationRepo->getById($linkId);
                break;
        }

        $invoice->link_type = $linkType;
        $invoice->link_id = $linkId;
        $invoice->save();

        return $invoice;
    }

    /**
     * Delete job invoice
     * @param  Instance $invoice JobInvoice
     * @return Void
     */
    public function deleteJobInvoice($invoice, $deleteFromQuickBooks = true)
    {
        //set unapplied status of invoice payments
        $paymentIds = $invoice->payments->pluck('payment_id')->toArray();
        $refIds = $invoice->payments->pluck('ref_id')->toArray();
        $paymentIds = array_merge($paymentIds, $refIds);
        $jobIds = [];
        $jobPayments = JobPayment::whereIn('id', $paymentIds)->whereNull('credit_id')->get();
        $creditJobPayments = JobPayment::whereIn('id', $paymentIds)->whereNotNull('credit_id')->get();
        $creditPaymentIds = $creditJobPayments->pluck('id')->toArray();
        foreach ($jobPayments as $jobPayment) {
            if($jobPayment->ref_id) {
                $jobPayment->delete();
                JobPayment::where('ref_to', $jobPayment->id)->delete();
                if($jobPayment->job_id == $invoice->job_id) continue;
                $jobPayment = JobPayment::where('id', $jobPayment->ref_id)->first();
                $jobIds[] = $jobPayment->job_id;
            }
            $amount = InvoicePayment::where('payment_id', $jobPayment->id)->where('invoice_id', $invoice->id)->sum('amount');
            \DB::table('job_payments')->where('id', $jobPayment->id)
                ->whereNull('credit_id')
                ->update([
                'status' => JobPayment::UNAPPLIED,
                'unapplied_amount' => $jobPayment->unapplied_amount + $amount,
                'updated_at' => timestamp(),
            ]);
        }

        # update applied credit #
        foreach ($creditJobPayments as $creditJobPayment) {
            $amount = $creditJobPayment->payment;
            $credit = JobCredit::find($creditJobPayment->credit_id);
            $credit->status = JobCredit::UNAPPLIED;
            $credit->unapplied_amount = $credit->unapplied_amount + $amount;
            $credit->save();
            $creditJobPayment->canceled = \Carbon\Carbon::now()->toDateTimeString();
            $creditJobPayment->save();
        }

        $invoice->payments()->delete();
        $invoice->delete();

        $job = $invoice->job;

        //update job invoice amount on deleting of invoice
        if ($invoice->isJobInvoice()) {
            $invoiceSum = $this->repo->getJobInvoiceSum($invoice->job_id);
            //update job invoce amount and its tax rate
            JobFinancialCalculation::updateJobInvoiceAmount(
                $job,
                $invoiceSum->job_amount,
                $invoiceSum->tax_amount
            );
        }

        $jobIds[] = $job->id;

        foreach(arry_fu($jobIds) as $jobId) {
            JobFinancialCalculation::updateFinancials($jobId);
        }

        if($deleteFromQuickBooks) {
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

        return true;
    }

    /**
     * Get invoice number
     * @param  int $companyId company id
     * @return serial number
     */
    public function getInvoiceNumber()
    {
        $companyId = getScopeId();

        $number = JobInvoiceNumber::where('company_id', $companyId)->first();

        if (!$number) {
            $startFrom = $this->repo->getLatestInvoiceId();
            $number = JobInvoiceNumber::create([
                'start_from' => $startFrom,
                'current_number' => 0,
                'company_id' => $companyId,
            ]);
        }

        $number->current_number += 1;
        $number->save();

        return $companyId . '-' . ($number->start_from + $number->current_number);
    }

    /**
     * File delete
     * @param  url $oldFilePath Old file Path Url
     * @return Boolan
     */
    private function fileDelete($oldFilePath)
    {
        if (!$oldFilePath) {
            return;
        }
        try {
            FlySystem::delete($oldFilePath);
        } catch (\Exception $e) {
            // nothing to do.
        }
    }

    /**
     * Make line object
     * @param  array $lines Array
     * @return Lines Object
     */
    private function makeLinesObject($lines = [], $input = [])
    {
        $invoiceLines = [];
        foreach ($lines as $line) {
            $line['is_taxable'] = isset($line['is_taxable']) ? $line['is_taxable'] : ine($input, 'taxable');
            $invoiceLines[] = new JobInvoiceLine($line);
        }

        return $invoiceLines;
    }

    /**
     * Update job pricing
     * @param  Instance $invoiceSum InvoiceSum
     * @param  Instance $invoice Invoice
     * @return Boolean
     */
    private function updateJobPricing($job, $invoiceSum, $invoice)
    {
        $taxRate = calculateTaxRate($invoiceSum->job_amount, $invoiceSum->tax_amount);

        $job->taxable  = (bool)$taxRate;
		$job->tax_rate = $taxRate;
        $job->amount = $invoiceSum->job_amount;
        $job->job_amount_approved_by = null;
        $job->update();

        JobFinancialCalculation::updateFinancials($job->id);

        return $job;
    }

    /**
     * Create Invoice
     * @param  Instance $job Job instance
     * @param  Float $taxRate Tax Rate
     * @param  array $lines Lines
     * @param  array $meta Array of meta
     * @return Invoice
     */
    private function createInvoice($job, $title, $lines, $meta = [])
    {
        $serialNumber = issetRetrun($meta, 'invoice_number') ?: $this->getInvoiceNumber();

        $lines = $this->makeLinesObject($lines, $meta);

        //save invoice and entities
        $invoice = $this->repo->save($job, $serialNumber, $title, $lines, $meta);

        $this->updatePdf($invoice);

        return $invoice;
    }

    /**
     * Update Invoice
     * @param  Instance $invoice Job invoice
     * @param  array $liens Lines
     * @param  array $meta Array of meta
     * @return Invoice
     */
    private function updateInvoice($invoice, $lines, $meta = [])
    {
        $lines = $this->makeLinesObject($lines, $meta);

        $invoice = $this->repo->update($invoice, $lines, $meta); //update invoice

        $this->updatePdf($invoice); //update pdf file

        $token = QuickBooks::getToken();

        if ($token) {
            $job = $invoice->job;

			if(!$job->quickbook_id) {

				QuickBooks::syncJobOrCustomerToQuickBooks($job);

			} else if(empty($invoice->quickbook_invoice_id)) {

				QBOQueue::addTask(QuickBookTask::QUICKBOOKS_INVOICE_CREATE, [
					'id' => $invoice->id
				], [
					'object_id' => $invoice->id,
					'object' => QuickBookTask::INVOICE,
					'action' => QuickBookTask::CREATE,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
			} else if(!empty($invoice->quickbook_invoice_id)) {

				QBOQueue::addTask(QuickBookTask::QUICKBOOKS_INVOICE_UPDATE, [
					'id' => $invoice->id
				], [
					'object_id' => $invoice->id,
					'object' => QuickBookTask::INVOICE,
					'action' => QuickBookTask::UPDATE,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
			}
        }
        return $invoice;
    }

    /**
	 * Create Job Invoice by QuickBooks Hook
	 * @param  Instance $job     Job instance
	 * @param  array  $lines     Lines
	 * @param  array  $meta      Array of meta
	 * @return Invoice
	 */
	public function saveInvoice($job, $lines, $meta = array())
	{
		$title = 'Job Invoice';

		$order = $this->repo->getJobInvoiceOrder($job->id);

		if($order > 1) {
            $title .= ' #'. $order;
        }

        $meta['order'] = $order;

		$meta['type'] = JobInvoice::JOB;

		$serialNumber = issetRetrun($meta, 'invoice_number') ?: $this->getInvoiceNumber();

		$lines = $this->makeLinesObject($lines, $meta);
		//save invoice and entities
		$invoice = $this->repo->save($job, $serialNumber, $title, $lines, $meta);

		$this->updatePdf($invoice); //update pdf

		return $invoice;
    }

    //Update job Price and financials from QBO
	public function updateJobPrice($invoice, $meta = array())
	{
		if(!$invoice) return $invoice;
		$job = $invoice->job;

		$invoiceSum = $this->repo->getJobInvoiceSum($job->id);

		$totalJobAmount = totalAmount($job->amount, $job->tax_rate);
		$totalInvoiceAmount = $invoiceSum->job_amount + $invoiceSum->tax_rate;

		if($totalInvoiceAmount > $totalJobAmount) {

			$this->updateJobPricing($job, $invoiceSum, $invoice);
		}

		if(ine($meta, 'taxable') && ine($meta, 'tax_rate')) {
			$this->updateTaxRate($invoiceSum, $invoice);
		}

		//update job invoice amount and its tax rate
		JobFinancialCalculation::updateJobInvoiceAmount($job,
			$invoiceSum->job_amount,
			$invoiceSum->tax_amount
		);

		if($job->isProject() || $job->isMultiJob()) {
        	//update parent job financial
        	JobFinancialCalculation::calculateSumForMultiJob($job);
    	}

		return $invoice;

    }

    /**
	 * Update Job Invoice by QuickBooks Hook
	 * @param  Instance $invoice JobInvoice instance
	 * @param  array  $lines     Lines
	 * @param  array  $meta      Array of meta
	 * @return Invoice
	 */

	public function qbUpdateJobInvoice($invoice, $lines, $meta = array())
	{
		$lines = $this->makeLinesObject($lines, $meta);

		$invoice = $this->repo->update($invoice, $lines, $meta);

		$this->updatePdf($invoice);

		return $invoice;
	}

	/**
	 * Update Job Invoice by QuickBookDesktop
	 * @param  Instance $invoice JobInvoice instance
	 * @param  array  $lines Lines
	 * @param  array  $meta Array of meta
	 * @return Invoice
	 */

	public function qbdUpdateJobInvoice($invoice, $lines, $meta = array())
	{
		$lines = $this->makeLinesObject($lines, $meta);

		$invoice = $this->repo->update($invoice, $lines, $meta);

        $this->updatePdf($invoice);
        return $invoice;
    }
}
