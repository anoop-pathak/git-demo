<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\SerialNumber;
use Illuminate\Console\Command;

class AddCompanySerialNumbers extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_company_serial_numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Company serial numbers of proposal, estimate.';

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
        $company = Company::find(113);

        if (!$company) {
            return;
        }

        $data = [
            'company_id' => $company->id,
            'type' => SerialNumber::PROPOSAL,
            'start_from' => 2382,
            'last_record_id' => 0,
            'is_active' => true
        ];

        SerialNumber::create($data);
    }
}
