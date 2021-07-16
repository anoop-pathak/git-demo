<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ProductionBoardColumn;
use Illuminate\Console\Command;

class ProductionBoardSetUp extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:production_board_setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add default production board column of company into production board column table';

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
        $ids = Company::pluck('id')->toArray();
        foreach ($ids as $id) {
            // get default columns list
            $columns = config('jp.default_production_board_columns');

            // create columns..
            foreach ($columns as $column) {
                $productionBoard = ProductionBoardColumn::firstOrNew([
                    'name' => $column,
                    'default' => true,
                    'company_id' => $id
                ]);

                $productionBoard->created_by = 1;
                $productionBoard->save();
            }
        }
    }
}
