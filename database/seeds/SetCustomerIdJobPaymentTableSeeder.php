<?php
use Illuminate\Database\Seeder;

class SetCustomerIdJobPaymentTableSeeder extends Seeder
{

    public function run()
    {
        
        $jobPayments = JobPayment::whereCustomerId(0)
            ->get();

        foreach ($jobPayments as $jobPayment) {
            $job = $jobPayment->job;
            if ($job) {
                $jobPayment->update(['customer_id' => $job->customer_id]);
            }
        }
    }
}
