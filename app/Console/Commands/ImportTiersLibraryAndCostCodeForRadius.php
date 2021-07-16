<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\TierLibrary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ImportTiersLibraryAndCostCodeForRadius extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:import_tiers_library_and_cost_code_for_redius';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add tiers library, cost-code category and financial products for Radius.';


    protected $companyId = 427;
    protected $categoryName = 'COST CODE';

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
        try {
            $company = Company::find($this->companyId);

            if (!$company) {
                return;
            }

            DB::beginTransaction();

            $category = FinancialCategory::whereCompanyId($company->id)
                ->whereName($this->categoryName)
                ->first();

            if (!$category) {
                // get last category order
                $lastCategory = FinancialCategory::whereCompanyId($company->id)
                    ->orderBy('order', 'desc')
                    ->select('order')
                    ->first();

                $category = FinancialCategory::create([
                    'name' => $this->categoryName,
                    'default' => false,
                    'company_id' => $company->id,
                    'order' => $lastCategory ? ($lastCategory->order + 1) : 1,
                ]);
            }

            $this->addRadiusTiers();
            $this->addRadiusFinancialProducts($category);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            $errorMsg = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();

            $this->error($errorMsg);
        }

        return true;
    }

    private function addRadiusTiers()
    {
        $data = [];
        $filename = storage_path('data/radius_tiers.csv');
        $excel = App::make('excel');
        $import = $excel->load($filename);
        $records = $import->get();
        $now = Carbon::now()->toDateTimeString();

        foreach ($records->toArray() as $key => $record) {
            $data[$key] = [
                'name' => $record['name'],
                'company_id' => $this->companyId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        TierLibrary::insert($data);
    }

    private function addRadiusFinancialProducts($category)
    {
        $data = [];
        $filename = storage_path('data/radius_products.csv');
        $excel = App::make('excel');
        $import = $excel->load($filename);
        $records = $import->get();
        $now = Carbon::now()->toDateTimeString();

        foreach ($records->toArray() as $key => $record) {
            $data[$key] = [
                'name' => $record['all'],
                'company_id' => $this->companyId,
                'category_id' => $category->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        FinancialProduct::insert($data);
    }
}
