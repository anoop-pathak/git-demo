<?php

namespace App\Console\Commands;

use App\Models\FinancialProduct;
use App\Models\MacroDetail;
use Illuminate\Console\Command;

class LinkMacroToProduct extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:link_macro_to_product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link macro to product.';

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
        $this->createProduct();
    }

    public function createProduct()
    {
        $details = MacroDetail::where('created_at', '!=', '0000-00-00 00:00:00')->get();

        $count = $details->count();
        foreach ($details as $detail) {
            $this->info('Pending:' . $count--);

            //1 name_category_unit
            // $product = FinancialProduct::where('name', $detail->product_name)
            // 	->where('company_id', $detail->company_id)
            // 	->where('category_id', $detail->category_id)
            // 	->where('unit', $detail->unit)
            // 	->first();
            // if($product) {
            //  $this->linkProduct($detail, $product);
            // 	continue;
            // }

            //2 name_cate_unit_uc_sp_desc
            $product = FinancialProduct::where('company_id', $detail->company_id)
                ->where('name', 'like', '%' . $detail->product_name . '%')
                ->where('category_id', $detail->category_id)
                ->where('unit', $detail->unit)
                ->where('unit_cost', $detail->unit_cost)
                ->where('selling_price', $detail->selling_price)
                ->where('description', $detail->description)
                ->first();
            // if($product) {
            // 	$this->linkProduct($detail, $product);
            // 	continue;
            // }

            // // 3 name_cat_unit_uc_desc
            // $product = FinancialProduct::where('company_id', $detail->company_id)
            // 	->where('name', 'like', '%'.$detail->product_name . '%')
            // 	->where('category_id', $detail->category_id)
            // 	->where('unit', $detail->unit)
            // 	->where('unit_cost', $detail->unit_cost)
            // 	->where('description', $detail->description)
            // 	->first();
            // if($product) {
            //  $this->linkProduct($detail, $product);
            // 	continue;
            // }

            // // 4 name_cat_unit_sp_desc
            // $product = FinancialProduct::where('company_id', $detail->company_id)
            // 	->where('name', 'like', '%'.$detail->product_name . '%')
            // 	->where('category_id', $detail->category_id)
            // 	->where('unit', $detail->unit)
            // 	->where('selling_price', $detail->selling_price)
            // 	->where('description', $detail->description)
            // 	->first();
            // if($product) {
            //  $this->linkProduct($detail, $product);
            // 	continue;
            // }

            // // name_cat_unit_desc
            // $product = FinancialProduct::where('company_id', $detail->company_id)
            // 	->where('name', 'like', '%'.$detail->product_name . '%')
            // 	->where('category_id', $detail->category_id)
            // 	->where('unit', $detail->unit)
            // 	->where('description', $detail->description)
            // 	->first();
            // if($product) {
            //  $this->linkProduct($detail, $product);
            // 	continue;
            // }

            $this->linkProduct($detail, $product);
        }
    }


    public function linkProduct($detail, $financialProduct = null)
    {
        if (!$financialProduct) {
            $financialProduct = new FinancialProduct;
            $financialProduct->company_id = $detail->company_id;
            $financialProduct->category_id = $detail->category_id;
            $financialProduct->supplier_id = $detail->supplier_id;
            $financialProduct->name = $detail->product_name;
            $financialProduct->unit = $detail->unit;
            $financialProduct->unit_cost = $detail->unit_cost;
            $financialProduct->description = $detail->description;
            $financialProduct->ref_id = $detail->id;
            $financialProduct->created_at = $detail->created_at;
            $financialProduct->updated_at = $detail->updated_at;
            $financialProduct->save();
        }
        $detail->update(['product_id' => $financialProduct->id]);
    }
}
