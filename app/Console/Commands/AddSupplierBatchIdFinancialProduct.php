<?php

namespace App\Console\Commands;

use App\Models\FinancialProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddSupplierBatchIdFinancialProduct extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_supplier_batch_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add supplier batch id in financial products.';

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
        $companies = FinancialProduct::whereNotNull('supplier_id')
            ->groupBy('company_id')
            ->pluck('company_id')->toArray();
        foreach ($companies as $companyId) {
            DB::table('financial_products')->whereNotNull('supplier_id')
                ->where('company_id', $companyId)
                ->whereNull('batch_id')
                ->update(['batch_id' => uniqueTimestamp()]);
        }
    }
}
