<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkSheetsAddTotal extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:worksheets_add_total';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Total of line items';

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
        $worksheets = DB::statement('UPDATE worksheets SET total=(
			select IF(worksheets.enable_actual_cost = 1, (SUM(actual_quantity * actual_unit_cost)), (SUM(quantity * unit_cost))) AS cost FROM financial_details WHERE financial_details.worksheet_id = worksheets.id GROUP BY financial_details.worksheet_id)
		');
    }
}
