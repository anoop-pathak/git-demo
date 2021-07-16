<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Customer;
use App\Services\Solr\Solr;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteDuplicateCustomers extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:delete_duplicate_customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        $companyId =  $this->ask('Enter company id:');
		$company = Company::find($companyId);

		if(!$company){
			$this->info('Please enter valid company id.');
			return;
		}
        $systemUser = $company->anonymous;
        // \Auth::guard('web)->login($systemUser);
        $duplicates = Customer::whereCompanyId($company->id)
            ->leftJoin('phones', 'phones.customer_id', '=', 'customers.id')
            ->select('first_name', 'last_name', 'phones.number', DB::raw('COUNT(*) as count'))
            ->groupBy('first_name', 'last_name', 'number')
            ->having('count', '>', 1)
            ->get();

        $currentDateTime = Carbon::now()->toDateTimeString();
        $this->info("Start Time: ".Carbon::now()->toDateTimeString());

        foreach ($duplicates as $key => $duplicate) {
            $customers = Customer::whereCompanyId($company->id)
                ->whereFirstName($duplicate->first_name)
                ->whereLastName($duplicate->last_name);
            if($duplicate->number) {
                $customers = $customers->join('phones', function($join) use($duplicate) {
					$join->on('phones.customer_id' , '=', 'customers.id')
				 		->where('phones.number', '=', $duplicate->number);
				});
			}
            $customers = $customers->orderBy('id', 'asc')
                ->selectRaw('id, (select count(*) from `jobs` where `jobs`.`customer_id` = `customers`.`id` and `jobs`.`deleted_at` is null and `jobs`.`parent_id` is null) job_count')
                ->having('job_count', '<', 1)
                ->get();

            if (!$customers->count()) {
                continue;
            }

            $customersIds = $customers->pluck('id')->toArray();
            $firstCustomer = $customers->first();

            $customersIds = array_diff($customersIds, (array) $firstCustomer->id);

            if (empty($customersIds)) {
                continue;
            }

            Customer::whereIn('id', $customersIds)->update([
                'deleted_at' => $currentDateTime,
                'deleted_by' => $systemUser->id,
                'delete_note' => 'Duplicate',
            ]);

            foreach ($customersIds as $customersId) {
                Solr::customerDelete($customersId);
            }

            $this->info("End Time: ".Carbon::now()->toDateTimeString());
        }
    }
}
