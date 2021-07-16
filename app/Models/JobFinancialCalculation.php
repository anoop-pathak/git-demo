<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JobFinancialCalculation extends Model
{

    protected $fillable = [
        'job_id',
        'total_job_amount',
        'total_change_order_amount',
        'total_amount',
        'total_received_payemnt',
        'pending_payment',
        'total_commission',
        'multi_job',
        'multi_job_sum',
        'total_credits',
        'total_invoice_received_payment',
        'total_change_order_invoice_amount',
        'unapplied_credits',
        'paid_commission',
        'company_id',
        'total_refunds',
        'total_account_payable_amount'
    ];


    public function job()
    {
        return $this->belongsTo(job::class);
    }

    protected function updateFinancials($jobId)
    {

        $job = Job::where('jobs.id', $jobId)->where('jobs.company_id', getScopeId())
        ->leftJoin(
            // calculate payment received..
            DB::raw(
                "(select sum(payment) as total_received_payemnt, job_id from job_payments where canceled IS NULL and credit_id IS NULL AND ref_to IS NULL AND deleted_at IS NULL AND job_id = {$jobId} GROUP BY job_id) as payments"
            ),
            'jobs.id',
            '=',
            'payments.job_id'
        )->leftJoin(
            DB::raw(
                "(select sum(payment) as total_ref_payemnt, job_id from job_payments where canceled IS NULL and ref_to IS NOT NULL AND deleted_at IS NULL AND job_id = {$jobId} GROUP BY job_id) as job_payment_ref"
            ),
            'jobs.id', '=', 'job_payment_ref.job_id'
        )->leftJoin(
            // calculate payment received on invoice.
            DB::raw(
                "(select sum(amount) as total_invoice_received_payment, job_id from invoice_payments where credit_id IS NULL  AND job_id = {$jobId} GROUP BY job_id) as invoice_payments"
            ),
            'jobs.id','=','invoice_payments.job_id'
        )->leftJoin(
            // calculate payment received..
            DB::raw(
                "(select sum(amount) as total_credits, sum(unapplied_amount) as unapplied_credits, job_id from job_credits where canceled IS NULL  AND deleted_at IS NULL and job_id = {$jobId} GROUP BY job_id) as credits"
            ),
            'jobs.id',
            '=',
            'credits.job_id'
        )->leftJoin(
            // calculate job amount and change orders sum..
            DB::raw(
                "(select sum(IF(taxable = 1, CAST((total_amount + ((total_amount * tax_rate) / 100)) AS decimal(16, 2)), total_amount)) as change_order_sum, job_id from change_orders where canceled IS NULL and deleted_at IS NULL  AND job_id = {$jobId} GROUP BY job_id) as change_orders"
            ),
            'jobs.id',
            '=',
            'change_orders.job_id'
        )->leftJoin(
            // calculate job amount and change orders sum in invoice..
            DB::raw(
                "(select sum(IF(taxable = 1, CAST((total_amount + ((total_amount * tax_rate) / 100)) AS decimal(16, 2)), total_amount)) as total_change_order_invoice_amount, job_id from change_orders where canceled IS NULL and deleted_at IS NULL and invoice_id IS NOT NULL  AND job_id = {$jobId} GROUP BY job_id) as change_orders_invoice"
            ),
            'jobs.id','=','change_orders_invoice.job_id'
        )->leftJoin(
            // calculate commissions..
            DB::raw(
                "(select sum(amount) as total_commission, job_id from job_commissions where canceled_at IS NULL  AND job_id = {$jobId} GROUP BY job_id) as commissions"
            ),
            'jobs.id', '=', 'commissions.job_id'
        )->leftJoin(
            // calculate paid commissions..
            DB::raw(
                "(select sum(amount) as paid_commission, job_id from job_commission_payments where canceled_at IS NULL  AND job_id = {$jobId} GROUP BY job_id) as paid_commissions"
            ),
            'jobs.id','=','paid_commissions.job_id'
        )->leftJoin(
            // calculate paid commissions..
            DB::raw(
                "(select sum(total_amount) as total_refunds, job_id from job_refunds where canceled_by IS NULL and canceled_at IS NULL and deleted_at IS NULL and job_id = {$jobId} GROUP BY job_id) as refunds"
            ),
            'jobs.id','=','refunds.job_id'
        )->leftJoin('vendor_bills', function($join){
            $join->on('vendor_bills.job_id', '=', 'jobs.id');
            $join->whereNull('vendor_bills.deleted_at');
        })->select('jobs.id','jobs.multi_job', 'jobs.parent_id', DB::raw('
            IFNULL(refunds.total_refunds, 0) as total_refunds,
            (IFNULL(payments.total_received_payemnt, 0) - IFNULL(job_payment_ref.total_ref_payemnt, 0)) as total_received_payemnt,
			IFNULL(invoice_payments.total_invoice_received_payment, 0) as total_invoice_received_payment,
			IFNULL(credits.total_credits, 0) as total_credits,
            sum(vendor_bills.total_amount) as total_account_payable_amount,
            IFNULL(credits.unapplied_credits, 0) as unapplied_credits,
			IFNULL(change_orders.change_order_sum,0) as total_change_order_amount,
            IFNULL(change_orders_invoice.total_change_order_invoice_amount,0) as total_change_order_invoice_amount,
			IFNULL(commissions.total_commission, 0) as total_commission,
            IFNULL(paid_commissions.paid_commission, 0) as paid_commission,
			IFNULL(IF(taxable = 1, CAST((amount + ((amount * tax_rate) / 100)) AS decimal(16, 2)), amount), 0) as total_job_amount,
			(IFNULL(change_orders.change_order_sum,0) + IFNULL(IF(taxable = 1, (amount + ((amount * tax_rate) / 100)), amount),0)) as total_amount,
			((IFNULL(change_orders.change_order_sum,0) + IFNULL(IF(taxable = 1, CAST((amount + ((amount * tax_rate) / 100)) AS decimal(16, 2)), amount),0)) - (IFNULL(payments.total_received_payemnt, 0) - IFNULL(job_payment_ref.total_ref_payemnt, 0)) - IFNULL(credits.total_credits, 0)) as pending_payment
			'))->first();

        if (!$job) {
            return;
        }

        $jobFinancial = self::firstOrNew(['job_id' => $jobId, 'multi_job' => $job->multi_job]);
        $jobFinancial->company_id        = getScopeId();
        $jobFinancial->total_job_amount = $job->total_job_amount;
        $jobFinancial->total_change_order_amount = $job->total_change_order_amount;
        $jobFinancial->total_amount = $job->total_amount;
        $jobFinancial->total_credits = $job->total_credits;
        $jobFinancial->total_refunds = $job->total_refunds;
        $jobFinancial->unapplied_credits = $job->unapplied_credits;
        $jobFinancial->total_received_payemnt = $job->total_received_payemnt;
        $jobFinancial->pending_payment = $job->pending_payment;
        $jobFinancial->total_commission = $job->total_commission;
        $jobFinancial->total_invoice_received_payment    = $job->total_invoice_received_payment;
        $jobFinancial->total_change_order_invoice_amount = $job->total_change_order_invoice_amount;
        $jobFinancial->paid_commission = $job->paid_commission;
        $jobFinancial->total_account_payable_amount  = (float)$job->total_account_payable_amount;
        $jobFinancial->save();

        //update multi job
        if($job->parent_id) {
            $job = Job::find($job->parent_id);
            self::calculateSumForMultiJob($job);
        }
    }

    /**
     * Update job invoice amount
     * @param  Job $job Job
     * @return Boolean
     */
    protected function updateJobInvoiceAmount($job, $jobAmount, $taxAmount)
    {
        if ($job->isMultiJob()) {
            return false;
        }

        self::where('job_id', $job->id)->update([
            'job_invoice_amount' => $jobAmount,
            'job_invoice_tax_amount' => $taxAmount
        ]);

        if (!$job->isProject()) {
            return;
        }

        $parentJob = $job->parentJob;
        $projectIds = $parentJob->projects()->awardedProjects()->pluck('id')->toArray();

        if (empty($projectIds)) {
            return;
        }

        $invoice = self::whereIn('job_id', $projectIds)
            ->selectRaw('SUM(job_invoice_amount) as job_invoice_amount, SUM(job_invoice_tax_amount) as tax_amount')
            ->first();

        self::where('job_id', $parentJob->id)
            ->whereMultiJobSum(true)
            ->update([
                'job_invoice_amount' => $invoice->job_invoice_amount,
                'job_invoice_tax_amount' => $invoice->tax_amount
            ]);

        return true;
    }

    protected function updateProfitLossTotal($job, $cost)
    {
        self::where('job_id', $job->id)->update(['pl_sheet_total' => $cost]);

        if (!$job->isProject()) {
            return;
        }

        $parentJob = $job->parentJob;
        self::calculateSumForMultiJob($parentJob);
    }

    protected function getFinancialSum(array $jobIds)
    {
        return self::whereIn('job_id', $jobIds)->where('multi_job_sum', false)
            ->selectRaw('sum(total_job_amount) as total_job_amount,
					sum(total_change_order_amount) as total_change_order_amount,
					sum(total_amount) as total_amount,
					sum(total_received_payemnt) as total_received_payemnt,
					sum(total_credits) as total_credits,
                    sum(total_refunds) as total_refunds,
                    sum(unapplied_credits) as unapplied_credits,
					sum(total_commission) as total_commission,
					sum(pending_payment) as pending_payment,
					sum(job_invoice_amount) as job_invoice_amount,
					sum(job_invoice_tax_amount) as job_invoice_tax_amount,
					sum(pl_sheet_total) as pl_sheet_total,
                    sum(total_invoice_received_payment) as total_invoice_received_payment,
                    sum(total_change_order_invoice_amount) as total_change_order_invoice_amount,
                    sum(paid_commission) as paid_commission,
                    sum(total_account_payable_amount) as total_account_payable_amount'
                )->get();
    }

    protected function calculateSumForMultiJob($job)
    {
        if(!$job || (!$job->isMultiJob() && !$job->isProject()) ) return;

        $jobIds = [];
        if ($job->isMultiJob()) {
            $jobId = $job->id;
            $jobIds = $job->projects()->awardedProjects()->pluck('id')->toArray();
        } else {
            $jobId = $job->parent_id;
            $jobIds = Job::awardedProjects()->whereParentId($jobId)->pluck('id')->toArray();
        }
        $jobIds[] = $jobId;

        $financialInfo = $this->getFinancialSum($jobIds)->first();
        $jobFinancial = self::firstOrNew(['job_id' => $jobId, 'multi_job_sum' => true]);
        $jobFinancial->total_job_amount = $financialInfo->total_job_amount;
        $jobFinancial->total_change_order_amount = $financialInfo->total_change_order_amount;
        $jobFinancial->total_amount = $financialInfo->total_amount;
        $jobFinancial->total_received_payemnt = $financialInfo->total_received_payemnt;
        $jobFinancial->total_credits = $financialInfo->total_credits;
        $jobFinancial->total_refunds	= $financialInfo->total_refunds;
        $jobFinancial->unapplied_credits = $financialInfo->unapplied_credits;
        $jobFinancial->pending_payment = $financialInfo->pending_payment;
        $jobFinancial->total_commission = $financialInfo->total_commission;
        $jobFinancial->job_invoice_amount = $financialInfo->job_invoice_amount;
        $jobFinancial->job_invoice_tax_amount = $financialInfo->job_invoice_tax_amount;
        $jobFinancial->pl_sheet_total = $financialInfo->pl_sheet_total;
        $jobFinancial->total_invoice_received_payment    = $financialInfo->total_invoice_received_payment;
        $jobFinancial->total_change_order_invoice_amount = $financialInfo->total_change_order_invoice_amount;
        $jobFinancial->paid_commission = $financialInfo->paid_commission;
        $jobFinancial->company_id = getScopeId();
        $jobFinancial->total_account_payable_amount  = (float)$financialInfo->total_account_payable_amount;
        $jobFinancial->save();

        // DB::table('jobs')->whereId($jobId)
        // 	->update(['amount'	=> $financialInfo->total_job_amount]);
    }

    protected function addJobFinancials($job)
    {
        if(!getScopeId()) return;

        $jobFinancial = self::firstOrNew([
            'company_id'=> getScopeId(),
            'job_id'=> $job->id,
            'multi_job' => $job->isMultiJob()
        ]);

        $jobFinancial->save();

        if($job->isMultiJob()){
            $jobFinancial = self::firstOrNew([
                'company_id'=> getScopeId(),
                'job_id'=> $job->id,
                'multi_job_sum' => true
            ]);

            $jobFinancial->save();
        }
    }

    protected function updateJobFinancialbillAmount($job)
	{
		if(!$job) return false;

		$billAmount = VendorBill::where('job_id', $job->id)->sum('total_amount');
		JobFinancialCalculation::where('job_id', $job->id)->update(['total_account_payable_amount' => $billAmount]);

		if($job->isProject()) {
			$projectIds = Job::where('parent_id', $job->parent_id)->pluck('id')->toArray();
			$totalSum = JobFinancialCalculation::whereIn('job_id', $projectIds)->sum('total_account_payable_amount');
			JobFinancialCalculation::where('job_id', $job->parent_id)
				->whereMultiJobSum(true)
				->update(['total_account_payable_amount' => $totalSum]);
		}
	}
}
