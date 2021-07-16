<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FinancialCategory;
use Illuminate\Console\Command;

class AddCompnayMiscCategory extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_company_misc_category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add MISC category for companies.';

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
        $companies = Company::all();

        foreach ($companies as $key => $value) {
            FinancialCategory::firstOrCreate([
                'name' => 'MISC',
                'company_id' => $value->id
            ]);
        }
    }
}
