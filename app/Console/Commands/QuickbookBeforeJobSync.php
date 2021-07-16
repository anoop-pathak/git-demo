<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Console\Command;

class QuickbookBeforeJobSync extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:quickbook_before_job_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickbook jobs sync';

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

        $companies = Company::has('quickbook')->with([
            'customers' => function ($query) {
                $query->whereNotNull('quickbook_id');
            }
        ])->get();

        if (!$companies->count()) {
            return false;
        }
        try {
            foreach ($companies as $company) {
                $customerIds = $company->customers->pluck('id')->toArray();
                Job::whereIn('customer_id', $customerIds)
                    ->whereNotNull('quickbook_id')
                    ->update(['quickbook_sync' => false]);
            }
        } catch (\Exception $e) {
            echo 'Quickbook before Customer Sync Command:';
        }
    }
}
