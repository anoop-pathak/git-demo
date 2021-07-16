<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageOldPaymentStatus extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_old_payment_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage old payment status.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        DB::statement("
			UPDATE job_payments
			LEFT JOIN (
				SELECT payment_id, sum(invoice_payments.amount) AS total_applied_payment 
				FROM invoice_payments 
				GROUP BY payment_id
			) as invoice_payments
			ON job_payments.id  = invoice_payments.payment_id
			SET status = 'unapplied'
			WHERE job_payments.payment > IFNULL(total_applied_payment, 0)");

        DB::statement("
				UPDATE job_payments
				LEFT JOIN (
					SELECT payment_id, sum(invoice_payments.amount) AS total_applied_payment 
					FROM invoice_payments 
					GROUP BY payment_id
				) as invoice_payments
				ON job_payments.id  = invoice_payments.payment_id
				SET status = 'closed'
				WHERE job_payments.payment = IFNULL(total_applied_payment, 0)");
    }
}
