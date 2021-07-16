<?php

namespace App\Console\Commands;

use App\Models\FinancialProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateFinancialProduct extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:remove_duplicate_financial_product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate financial product.';

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
        $products = FinancialProduct::select('id', 'company_id', 'category_id', 'name', 'unit', 'unit_cost', 'description', 'selling_price', 'labor_id', DB::raw('COUNT(*) as count'))
            ->whereNull('supplier_id')
            ->orderBy('count', 'desc')
            ->groupBy(DB::raw('company_id, category_id, name, unit, unit_cost,  description, selling_price'))
            ->havingRaw('COUNT(*) > 1')
            ->get();
        $total = $products->count();
        $this->info("Total duplicate products: {$total}");
        foreach ($products as $product) {
            $productQuery = FinancialProduct::where('company_id', $product->company_id)
                ->whereNull('supplier_id')
                ->where('name', $product->name)
                ->where('category_id', $product->category_id)
                ->where('unit', $product->unit)
                ->where('description', $product->description)
                // ->where('selling_price', $product->selling_price)
                // ->where('unit_cost', $product->unit_cost)
                ->where('id', '!=', $product->id);

            if ($product->selling_price) {
                $productQuery->whereRaw("FORMAT(`selling_price`,2) = FORMAT($product->selling_price,2)");
            }

            if ($product->unit_cost) {
                $productQuery->whereRaw("FORMAT(`unit_cost`,2) = FORMAT($product->unit_cost,2)");
            }

            $duplicateProducts = $productQuery->pluck('labor_id', 'id')->toArray();
            $labors = arry_fu(array_values($duplicateProducts));
            $productIds = arry_fu(array_keys($duplicateProducts));

            DB::table('macro_details')
                ->whereIn('product_id', $productIds)
                ->update(['product_id' => $product->id]);

            DB::table('financial_details')
                ->whereIn('product_id', $productIds)
                ->update(['product_id' => $product->id]);

            if (!empty($labors)) {
                if (($key = array_search($product->labour_id, $labors)) !== false) {
                    unset($labors[$key]);
                }
            }

            if (!empty($labors)) {
                DB::table('job_labour')->whereIn('labour_id', $labors)
                    ->update(['labour_id' => $product->labor_id]);
                // DB::table('job_labour')->whereIn('labour_id', $labors)->delete();
                DB::Table('users')->whereIn('id', $labors)->delete();
                DB::Table('user_profile')->whereIn('user_id', $labors)->delete();
            }

            DB::table('financial_products')->whereIn('id', $productIds)->delete();

            --$total;
            $this->info("Pending: {$total}");
        }
    }
}
