<?php
use Illuminate\Database\Seeder;
use App\Models\SetupAction;
use App\Models\Product;

class SetupActionTableSeeder extends Seeder
{

    public function run()
    {
        SetupAction::truncate();
        $actions = [

            /*
			|--------------------------------------------------------------------------
			| JobProgress Basic's actions
			|--------------------------------------------------------------------------
			*/
            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS,
                'required'  => false
            ],

            /*
			|--------------------------------------------------------------------------
			| JobProgress Plus's actions
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS,
                'required'  => false
            ],
            /*
			|--------------------------------------------------------------------------
			| JobProgress Plus's actions
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'required'  => false
            ],

            /*
			|--------------------------------------------------------------------------
			| GAF Plus's actions
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_GAF_PLUS,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_GAF_PLUS,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_GAF_PLUS,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_GAF_PLUS,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_GAF_PLUS,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_GAF_PLUS,
                'required'  => false
            ],

            /*
			|--------------------------------------------------------------------------
			| JobProgress Standard
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'required'  => false
            ],

            /*
			|--------------------------------------------------------------------------
			| JobProgress Partner
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'required'  => false
            ],

            /*
			|--------------------------------------------------------------------------
			| JobProgress Multi
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_MULTI,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_MULTI,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_MULTI,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_MULTI,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_MULTI,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_MULTI,
                'required'  => false
            ],
            /*
			|--------------------------------------------------------------------------
			| JobProgress 25
			|--------------------------------------------------------------------------
			*/

            [
                'action' => 'Company Setup',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_25,
                'required'  => true
            ],
            [
                'action' => 'Billing Details',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_25,
                'required'  => true
            ],
            [
                'action' => 'Trade Types',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_25,
                'required'  => true
            ],
            [
                'action' => 'States',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_25,
                'required'  => true
            ],
            [
                'action' => 'Users',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_25,
                'required'  => false
            ],
            [
                'action' => 'Workflow',
                'product_id'    => Product::PRODUCT_JOBPROGRESS_25,
                'required'  => false
            ],
        ];
        foreach ($actions as $key => $action) {
            SetupAction::create($action);
        }
    }
}
