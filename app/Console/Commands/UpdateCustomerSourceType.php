<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class UpdateCustomerSourceType extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:zapier_update_customer_source_type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update source type coloum';

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
        DB::table('customers')->where('source_type', 'Spotio')->update([
            'source_type'      => Customer::TYPE_ZAPIER,
            'referred_by_type' => Customer::REFERRED_BY_TYPE
        ]);
    }
}
