<?php
namespace App\Console\Commands;

use App\Models\CompanySupplier;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\Supplier;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JobQueue;
use Exception;

class UpdateSrsCustomerProducts extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update_srs_customer_products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    protected $supplierRepo;
    protected $request;
    protected $supplier;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $srs = Supplier::srs();
        try {
            $date = Carbon::now()->subDays(5)->toDateString();
            $srsSupplierList = CompanySupplier::where('supplier_id', $srs->id)
                ->whereDate('updated_at', '<=', $date)
                ->get();

            foreach ($srsSupplierList as $key => $supplier) {
                $data = ['company_supplier_id' => $supplier->id,];
                JobQueue::enqueue(JobQueue::SRS_SYNC_DETAILS, $supplier->company_id, $supplier->id, $data);
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine();
            Log::info('Update SRS Customer Products: '. $errorMsg);
        }
    }
}
