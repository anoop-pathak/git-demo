<?php
use Illuminate\Database\Seeder;

class AddTaxRateJobInvoiceSeeder extends Seeder
{

    public function run()
    {
        $jobInvoices = JobInvoice::with('Job')
            ->whereTitle('Job Invoice')
            ->get();

        foreach ($jobInvoices as $jobInvoice) {
            $job = $jobInvoice->job;

            if ($job && $job->taxable && !empty($job->tax_rate)) {
                $taxArray = [
                    'tax_rate' => $job->tax_rate
                ];

                $jobInvoice->update($taxArray);
            }
        }
    }
}
