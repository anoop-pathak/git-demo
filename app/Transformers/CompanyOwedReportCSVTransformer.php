<?php

namespace App\Transformers;

use App\Models\JobCommission;
use App\Models\JobPayment;
use Carbon\Carbon;
use Request;
use League\Fractal\TransformerAbstract;

class CompanyOwedReportCSVTransformer extends TransformerAbstract
{

    public function transform($job)
    {
        if ($job->isMultiJob()) {
            $this->projectsIds = $job->projects->pluck('id')->toArray();
        }

        $amountLabel = 'Total Amount';
        $amountOwedLabel = 'Amount Owed';
        $amount = $job->total_amount;
        $receivedPayment = $job->total_received_payemnt;
        $pendingPayment = $job->pending_payment;

        // //show invoice wise amount
        // if (Request::get('use_invoice_amount')) {
        //     $amountLabel = 'Total Invoice Amount';
        //     $amountOwedLabel = 'Invoice Amount Owed';
        //     $amount = $job->total_invoice_amount;
        //     $pendingPayment = $job->total_invoice_amount - ($job->total_received_payemnt + $job->total_credits);
        // }

        return [
            'Customer Name' => $job->customer->full_name,
            'Job Id' => $job->number,
            'Job #'	 => $job->alt_id,
            'E-mail' => $job->customer->email,
            'Phone' => $this->getPhones($job->customer->phones),
            $amountLabel => currencyFormat($amount),
            'Payment Received'      => currencyFormat($receivedPayment),
            'Commissions' => $this->getCommissions($job),
            $amountOwedLabel => currencyFormat($pendingPayment),
            'Total Credits'	   => currencyFormat($job->total_credits),
            'Total Refunds'	   => currencyFormat($job->total_refunds),
            'Aging' => $job->getAgeing(),
            'invoice #'		 => $this->getInvoicesNumber($job),
        ];
    }

    private function getPayments($job)
    {
        if ($job->isMultiJob()) {
            $ids = $this->projectsIds;
            $ids[] = $job->id;
            $payments = JobPayment::whereIn('job_id', $ids)
                ->excludeCanceled()
                ->get();
        } else {
            $payments = $job->payments;
        }

        if (!sizeof($payments)) {
            return "";
        }

        $methods = [
            'cc' => 'Credit Card',
            'echeque' => 'Check',
            'cash' => 'Cash',
            'paypal' => 'Paypal',
            'other' => 'other',
            'venmo'   		 => 'Venmo',
			'zelle'          => 'Zelle',
			'Digital Cash App' => 'Digital Cash App',
			'ACH/Online Payment' => 'ACH/Online Payment',
        ];

        $count = $payments->count();
        $i = 0;
        $paymentsString = "";
        foreach ($payments as $payment) {
            $paymentsString .= currencyFormat($payment->payment);
            $paymentsString .= ' / ';
            $paymentsString .= isset($methods[$payment->method]) ? $methods[$payment->method] : $payment->method;

            if (!empty($payment->echeque_number)) {
                $paymentsString .= ' / ';
                $paymentsString .= $payment->echeque_number;
            }
            if (++$i != $count) {
                $paymentsString .= "; ";
            }
        }
        return $paymentsString;
    }

    private function getCommissions($job)
    {
        if ($job->isMultiJob()) {
            $ids = $this->projectsIds;
            $ids[] = $job->id;
            $commissions = JobCommission::whereIn('job_id', $ids)->whereNull('canceled_at')->get();
        } else {
            $commissions = $job->commissions()->whereNull('canceled_at')->get();
        }

        if (!sizeof($commissions)) {
            return '';
        }

        $count = $commissions->count();
        $i = 0;
        $commissionsString = "";
        foreach ($commissions as $commission) {
            $commissionsString .= currencyFormat($commission->amount);
            $commissionsString .= ' / ';
            $commissionsString .= Carbon::parse($commission->updated_at)->format('m/d/Y');
            if (++$i != $count) {
                $commissionsString .= "; ";
            }
        }

        return $commissionsString;
    }

    private function getPhones($phones)
    {

        if (!sizeof($phones)) {
            return '';
        }

        return phoneNumberFormat($phones[0]['number'], config('company_country_code'));
    }

    private function getInvoicesNumber($job)
	{
		$data = "";
		$invoiceNumber = [];
		if($job->isMultiJob()){
			$projects = $job->projects;
			foreach ($projects as $key => $project) {
				$invoiceNumber = array_merge($invoiceNumber, $project->invoices->pluck('invoice_number')->toArray());
			}
		}else{
			$invoiceNumber = $job->invoices->pluck('invoice_number')->toArray();
		}
		$data = implode('; ', $invoiceNumber);
        return $data;
	}
}
