<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Solr\Solr;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteImportedCustomers extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:delete_imported_customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete imported customers of a date for a particular subscriber.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->currentDateTime = Carbon::now()->toDateTimeString();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $companyId = 201;// Capital Awing..
        $date = '2017-12-22';

        $customers = Customer::where('company_id', $companyId)
            ->has('jobs', '=', 0)
            ->where('created_at', 'Like', '%' . $date . '%')
            ->get();

        $this->info('Total Customer Delete: ' . $customers->count());

        if ($this->confirm('Do you wish to continue? [yes|no]')) {
            foreach ($customers as $key => $customer) {
                $this->deleteCustomer($customer);
            }
        }
    }

    private function deleteCustomer($customer)
    {
        DB::beginTransaction();
        try {
            // $customer->phones()->delete();
            // $customer->address()->delete();
            //$customer->billing()->delete();
            //DB::table('customers')->where('id', $customer->id)->delete();
            DB::table('customers')->where('id', $customer->id)->update(['deleted_at' => $this->currentDateTime]);
            Solr::customerJobDelete($customer->id);
        } catch (\Exception $e) {
            DB::rollback();
            $this->error($e->getMessage());
            return false;
        }
        DB::commit();

        return true;
    }
}
