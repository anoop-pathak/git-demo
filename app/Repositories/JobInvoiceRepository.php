<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\JobCredit;
use App\Models\JobInvoice;
use App\Models\InvoicePayment;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use App\Models\QuickBookTask;
use App\Repositories\JobRepository;

class JobInvoiceRepository extends AbstractRepository
{

    /**
     * The base eloquent appointment
     * @var Eloquent
     */
    protected $model;
    protected $jobRepo;
    protected $scope;

    function __construct(JobInvoice $model, Context $scope, JobRepository $jobRepo)
    {
        $this->scope = $scope;
        $this->model = $model;
        $this->jobRepo = $jobRepo;
    }

    /**
     * Get filtered invoice
     * @param  array $filters Array of filters
     * @return QueryBuilder
     */
    public function getFilteredInvoice($filters = [])
    {
        $query = $this->make()->sortable();

		$query->join('jobs', 'jobs.id', '=', 'job_invoices.job_id');

		$query->where('jobs.company_id', getScopeId());
		$query->whereNull('jobs.deleted_at');

		$query->select("job_invoices.*");

		$this->applyfilters($query, $filters);

		$includeData = $this->includeData($filters);
		$query->with($includeData);

		return $query;
    }


	/**
	 * Get Sum of Filtered invoices amount
	 * @param  array  $filters [description]
	 * @return response
	 */
	public function getSumOfInvoices($filters = array())
	{
		$query = $this->getJobInvoiceListing($filters);

		$clonedQuery = clone $query;

		$invoicePayments = $clonedQuery->leftjoin('invoice_payments', 'invoice_payments.invoice_id', '=', 'job_invoices.id')
			->selectRaw('invoice_payments.invoice_id, SUM(coalesce(invoice_payments.amount, 0)) AS open_balance_amount')->groupBy('invoice_payments.invoice_id')
			->first();

		$openAmount = 0;
		if($invoicePayments) {
			$openAmount = $invoicePayments->open_balance_amount;
        }

		$query->selectRaw('
			sum(coalesce(job_invoices.total_amount, 0)) as total_invoice_amount,
			sum(coalesce(job_invoices.amount, 0)) as invoice_amount,
			sum(coalesce(job_invoices.total_amount, 0) - coalesce(job_invoices.amount,0)) as tax_rate_amount,
			sum(coalesce(job_invoices.total_amount, 0) - '.$openAmount.') as open_amount
		');

		return $query->first();
	}

	/**
	 * Get Jobs Invoice Listing
	 * @param  array  $filters [description]
	 * @return resposne
	 */
	public function getJobInvoiceListing($filters = array())
	{
		$query = $this->make();
		$filters['invoice_report_filter_only'] = true;
		$filters['include_projects'] = true;
		$jobQuery = $this->jobRepo->getJobsQueryBuilder($filters, ['customers'])
			->whereNull('customers.deleted_at')->select('jobs.id');
		$jobsJoinQuery = generateQueryWithBindings($jobQuery);

		$query->join(DB::raw("({$jobsJoinQuery}) as jobs"), 'jobs.id', '=', 'job_invoices.job_id')->select('job_invoices.*');
		$query->with('customer', 'job', 'payments', 'job.trades', 'job.division');

		$this->applyfilters($query, $filters);

		return $query;
	}

    public function getById($id, $with = [])
    {
        return $this->getFilteredInvoice()->where('job_invoices.id', $id)
            ->with($with)
            ->firstOrFail();
    }

    public function save($job, $invoiceNumber, $title, $lines, $meta = [])
    {
        $data = [
            'customer_id' => $job->customer_id,
            'job_id' => $job->id,
            'title' => $title,
            'detail' => null,
            'job_number' => $job->number,
            'description' => ine($meta, 'description') ? $meta['description'] : null,
            'custom_tax_id' => ine($meta, 'custom_tax_id') ? $meta['custom_tax_id'] : null,
            'signature' => ine($meta, 'signature') ? $meta['signature'] : null,
            'signature_date' => ine($meta, 'signature') ? Carbon::now() : null,
            'due_date' => ine($meta, 'due_date') ? $meta['due_date'] : null,
            'date' => ine($meta, 'date') ? $meta['date'] : Carbon::now()->format('Y-m-d'),
            'taxable' => ine($meta, 'taxable'),
            'tax_rate' => ine($meta, 'tax_rate') ? $meta['tax_rate'] : null,
            'type' => ine($meta, 'type') ? $meta['type'] : null,
            'proposal_id' => ine($meta, 'proposal_id') ? $meta['proposal_id'] : null,
            'invoice_number' => $invoiceNumber,
            'order' => ine($meta, 'order') ? $meta['order'] : 1,
            'note' => ine($meta, 'note') ? $meta['note'] : null,
            'name'           => ine($meta, 'name') ? $meta['name'] : 'Invoice',
            'unit_number'    => ine($meta, 'unit_number') ? $meta['unit_number'] : null,
            'division_id'    => ine($meta, 'division_id') ? $meta['division_id'] : null,
            'branch_code'    => ine($meta, 'branch_code') ? $meta['branch_code'] : null,
            'ship_to_sequence_number' => ine($meta, 'ship_to_sequence_number') ? $meta['ship_to_sequence_number'] : null,
            'qb_division_id' => ine($meta, 'qb_division_id') ? $meta['qb_division_id'] : null,
        ];

        if (ine($meta, 'taxable') && isset($meta['tax_rate'])) {
            $data['tax_rate'] = $meta['tax_rate'];
        }

        if (ine($meta, 'qb_desktop_txn_id') && isset($meta['qb_desktop_txn_id'])) {
			$data['qb_desktop_txn_id'] = $meta['qb_desktop_txn_id'];
		}


		if (ine($meta, 'qb_desktop_sequence_number') && isset($meta['qb_desktop_sequence_number'])) {
			$data['qb_desktop_sequence_number'] = $meta['qb_desktop_sequence_number'];
		}

		$invoice = JobInvoice::create($data);

        $invoice->lines()->saveMany($lines);

        $taxableAmount = $totalAmount = $amount = 0;
		foreach ($invoice->lines as $line) {
			if(!$line->is_chargeable) continue;

			$lineAmount = $line->amount *  $line->quantity;
			$amount += $lineAmount;
			if($line->is_taxable) {
				$taxableAmount += $lineAmount;
				$totalAmount +=  $lineAmount + calculateTax($lineAmount, $invoice->tax_rate);
			} else {
				$totalAmount += $lineAmount;
			}
		}

		$invoice->update([
			'total_amount' => $totalAmount,
			'amount'       => $amount,
			'taxable_amount' => $taxableAmount,
		]);

        //update proposal id to all job invoices
        if ($invoice->type == JobInvoice::JOB) {
            $proposalInvoice = JobInvoice::where('job_id', $job->id)
                ->whereType(JobInvoice::JOB)
                ->whereNotNull('proposal_id')
                ->first();
            if ($proposalInvoice && $proposalInvoice->proposal_id) {
                JobInvoice::where('job_id', $job->id)->whereType(JobInvoice::JOB)
                    ->update(['proposal_id' => $proposalInvoice->proposal_id]);
                $invoice->proposal_id = $proposalInvoice->proposal_id;
            }
        }

        return $invoice;
    }

    public function update($invoice, $lines, $meta = [])
    {
        $data = [
			'tax_rate'       => ine($meta, 'tax_rate') ? $meta['tax_rate'] : null,
			'signature'      => ine($meta, 'signature') ? $meta['signature'] : null,
			'signature_date' => ine($meta, 'signature') ? Carbon::now() : null,
			'description'    => ine($meta, 'description') ? $meta['description'] : null,
			'detail'         => null,
			'custom_tax_id'  => ine($meta, 'custom_tax_id') ? $meta['custom_tax_id'] : null,
			'taxable'        => ine($meta, 'taxable'),
			'tax_rate'       => ine($meta, 'tax_rate') ? $meta['tax_rate'] : null,
		];

		if(isset($meta['date'])) {
			$data['date'] = ine($meta, 'date') ? $meta['date'] : $invoice->getCreatedDate();
		}

		if(isset($meta['due_date'])) {
			$data['due_date'] = ine($meta, 'due_date') ? $meta['due_date'] : null;
		}

		if(isset($meta['name'])) {
			$data['name'] = $meta['name'] ?: 'Invoice';
		}

		if(isset($meta['unit_number'])) {
			$data['unit_number'] = ine($meta, 'unit_number') ? $meta['unit_number'] : null;
		}

		if(isset($meta['division_id'])) {
			$data['division_id'] = ine($meta, 'division_id') ? $meta['division_id'] : null;
		}

		if(isset($meta['branch_code'])) {
			$data['branch_code'] = ($meta['branch_code']) ?: null;
		}

		if(isset($meta['ship_to_sequence_number'])) {
			$data['ship_to_sequence_number'] = ($meta['ship_to_sequence_number']) ?: null;
		}

		if(array_key_exists('note', $meta)) {
			$data['note'] = ($meta['note']) ?: null;
		}

		if(isset($meta['quickbook_id'])) {
			$data['quickbook_id'] = ine($meta, 'quickbook_id') ? $meta['quickbook_id'] : null;
		}

		if(isset($meta['is_taxable'])) {
			$data['is_taxable'] = $data['is_taxable'];
		}

		$invoice->update($data);
		$invoice->lines()->delete();
		$invoice->lines()->saveMany($lines);

		$taxableAmount = $totalAmount = $amount = 0;
		foreach ($invoice->lines as $line) {
			if(!$line->is_chargeable) continue;

			$lineAmount = $line->amount *  $line->quantity;
			$amount += $lineAmount;
			if($line->is_taxable) {
				$taxableAmount += $lineAmount;
				$totalAmount +=  $lineAmount + calculateTax($lineAmount, $invoice->tax_rate);
			} else {
				$totalAmount += $lineAmount;
			}
		}

		$invoice->update([
			'total_amount' => $totalAmount,
			'amount'       => $amount,
			'last_updated_origin' => QuickBookTask::ORIGIN_JP,
			'taxable_amount' => $taxableAmount,
		]);

		$invoicePayments = $invoice->payments->sum('amount');
		$diffAmount = $invoice->amount - $invoicePayments;
		if($diffAmount<0){
			$this->updateCredits($invoice, $diffAmount);
		}
		//proposal id update for all job invoices
		if($invoice->isJobInvoice() && isset($meta['proposal_id'])) {
			$proposalId = issetRetrun($meta, 'proposal_id') ?: null;
			JobInvoice::where('job_id', $invoice->job_id)->whereType(JobInvoice::JOB)
					->update(['proposal_id' => $proposalId]);
			$invoice->proposal_id = $proposalId;
		}

		return $invoice;
    }

    /**
     * Get latest invoice id
     * @return invoice id
     */
    public function getLatestInvoiceId()
    {
        $invoice = JobInvoice::join(DB::raw('(SELECT jobs.id, company_id from jobs)AS jobs'), 'jobs.id', '=', 'job_invoices.job_id')
            ->where('jobs.company_id', getScopeId())
            ->orderBy('job_invoices.id', 'desc')
            ->select('job_invoices.id')
            ->first();

        return ($invoice) ? $invoice->id : 0;
    }

    /**
     * Get recent job invoice
     * @param  int $jobId job id
     * @return jobInvoice
     */
    public function getJobInvoiceOrder($jobId)
    {
        $invoice = JobInvoice::whereJobId($jobId)
            ->whereType(JobInvoice::JOB)
            ->orderBy('order', 'desc')
            ->first();
        if (!$invoice) {
            return 1;
        }

        return $invoice->order + 1;
    }

    /**
     * Get job invoice sum
     * @param  int $jobId Job Id
     * @return invoice
     */
    public function getJobInvoiceSum($jobId)
    {
        $invoice = JobInvoice::where('job_id', $jobId)
            ->whereType('job')
            ->selectRaw('SUM(total_amount - amount) as tax_amount, SUM(amount) as job_amount')
            ->first();

        return $invoice;
    }

    public function getOpenInvoicesByIds($ids)
    {
        return $this->model->whereIn('id', $ids)->where('status', 'open')->with('payments')->get()->toArray();
    }

    private function applyfilters($query, $filters = [])
    {
        if (ine($filters, 'invoice_id')) {
            $invoiceId = str_replace(JobInvoice::QUICKBOOK_INVOICE_PREFIX, '', strtoupper($filters['invoice_id']));
            $query->where('job_invoices.invoice_number', 'like', '%' . $invoiceId . '%');
        }

        if(ine($filters, 'keyword')) {
            $invoiceId = str_replace(JobInvoice::QUICKBOOK_INVOICE_PREFIX, '', strtoupper($filters['keyword']));
            $query->where(function($query) use($invoiceId) {
                $query->where('job_invoices.invoice_number', 'like', '%'.$invoiceId.'%');
                $query->orWhere('job_invoices.unit_number', 'like', '%'.$invoiceId.'%');
            });
        }

        if (ine($filters, 'type')) {
            $query->where('job_invoices.type', $filters['type']);
        }

        if (ine($filters, 'job_id')) {
            $query->where('job_invoices.job_id', $filters['job_id']);
        }

        if (ine($filters, 'status')) {
            $query->where('job_invoices.status', $filters['status']);
        }
        // job total amount range
		$query = $this->appendRangeFilter(
            $query,
            'job_invoices.total_amount',
            isSetNotEmpty($filters, 'start_total_amount_range'),
            isSetNotEmpty($filters, 'end_total_amount_range')
        );

        // date range filters
        if((ine($filters,'start_date') || ine($filters,'end_date'))
        && ine($filters, 'date_range_type')) {
            $startDate = isSetNotEmpty($filters, 'start_date') ?: null;
            $endDate   = isSetNotEmpty($filters, 'end_date') ?: null;
            switch ($filters['date_range_type']) {
                case 'invoice_created_date':
                    $query = $this->appendRangeFilter($query, "DATE_FORMAT(".buildTimeZoneConvertQuery('job_invoices.created_at').", '%Y-%m-%d')", $startDate, $endDate);
                break;
                case 'invoice_due_date':
                    $query = $this->appendRangeFilter($query, 'DATE_FORMAT(job_invoices.due_date, "%Y-%m-%d")', $startDate, $endDate);
                break;
                case 'invoice_updated_date':
                    $query = $this->appendRangeFilter($query, "DATE_FORMAT(".buildTimeZoneConvertQuery('job_invoices.updated_at').", '%Y-%m-%d')", $startDate, $endDate);
                break;
            }
        }

        if(ine($filters, 'title')) {
            $query->where('job_invoices.title', $filters['title']);
        }

        if(ine($filters, 'name')) {
            $query->where('job_invoices.name','Like','%'.$filters['name'].'%');
        }

        if(ine($filters, 'amount')) {
            $invoiceAmount = $filters['amount'];

            if(strpos($invoiceAmount, ".") !== false) {
                $query->where("job_invoices.total_amount", $invoiceAmount);
            }else{
                $query->whereRaw("TRUNCATE(job_invoices.total_amount, 0) = {$invoiceAmount}");
            }
        }
    }

    /**
     * includeData
     * @param  Array $input | Input Array
     * @return Array
     */
    private function includeData($input = [])
    {
        $with = ['payments', 'job.customer.phones', 'job.jobWorkflow', 'job.jobMeta', 'job.address', 'job.address.state', 'job.address.country'];

        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('lines', $includes)) {
            $with[] = 'lines';
            $with[] = 'lines.workType';
            $with[] = 'lines.trade';
        }

        return $with;
    }

    private function updateCredits($invoice, $amount)
    {
        $creditPayments = InvoicePayment::whereInvoiceId($invoice->id)->whereNotNull('credit_id')->orderBy('id', 'desc')->get();
        foreach ($creditPayments as $creditPayment) {
            if($amount == 0) continue;
            $amount = $creditPayment->amount - abs($amount);
            if($amount > 0){
                $unappliedCredit = $creditPayment->amount - $amount;
                $creditPayment->amount = $amount;
                $creditPayment->save();
                $creditPayment->jobPayment()->update(['payment'=> $amount]);
                $jobCredit = JobCredit::find($creditPayment->credit_id);
                $jobCredit->unapplied_amount += $unappliedCredit;
                $jobCredit->status = JobCredit::UNAPPLIED;
                $jobCredit->save();
            }elseif($amount <= 0) {
                $creditPayment->jobPayment()->update(['canceled'=> Carbon::now()->toDateTimeString()]);
                $jobCredit = JobCredit::find($creditPayment->credit_id);
                $jobCredit->unapplied_amount += $creditPayment->amount;
                $jobCredit->status = JobCredit::UNAPPLIED;
                $jobCredit->save();
                $creditPayment->delete();
            }
        }
    }
}
