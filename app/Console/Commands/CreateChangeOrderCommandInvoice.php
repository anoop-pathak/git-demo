<?php

namespace App\Console\Commands;

use App\Models\ChangeOrder;
use App\Models\JobInvoice;
use App\Services\QuickBooks\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class CreateChangeOrderCommandInvoice extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_change_order_invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create change order invoice.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = App::make(Client::class);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $changeOrders = ChangeOrder::whereNull('invoice_id')
            ->with('job', 'job.customer')
            ->get();
        if (empty($changeOrders)) {
            return false;
        }
        foreach ($changeOrders as $key => $changeOrder) {
            $job = $changeOrder['job'];
            $customer = $job['customer'];
            $detail = [
                'description' => 'Change Order #' . $changeOrder['order'],
                'amount' => $changeOrder['total_amount'],
            ];

            $data = [
                'customer_id' => $changeOrder['job']['customer_id'],
                'job_id' => $changeOrder['job']['id'],
                'title' => 'Change Order #' . $changeOrder['order'],
                'amount' => $changeOrder['total_amount'],
                'detail' => $detail,
            ];

            $invoice = JobInvoice::create($data);
            $changeOrder->invoice_id = $invoice->id;
            $changeOrder->invoice()->associate($invoice);
            $changeOrder->save();
        }
    }
}
