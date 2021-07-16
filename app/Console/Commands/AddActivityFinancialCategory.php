<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FinancialCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddActivityFinancialCategory extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_activty_financial_category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add activiy category in financial categories';

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
        $companyIds = Company::pluck('id')->toArray();
        foreach ($companyIds as $key => $companyId) {
            $order = 1;

            $categoryIds = FinancialCategory::whereCompanyId($companyId)
                ->orderBy('id', 'asc')
                ->pluck('id')->toArray();

            foreach ($categoryIds as $key => $categoryId) {
                if ($order == 3) {
                    FinancialCategory::create([
                        'name' => 'ACTIVITY',
                        'company_id' => $companyId,
                        'order' => $order,
                    ]);
                    $order++;
                }

                DB::table('financial_categories')->whereCompanyId($companyId)
                    ->whereId($categoryId)
                    ->update(['order' => $order]);

                $order++;
            }
        }
    }
}
