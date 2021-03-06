<?php
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;
use App\Models\Product;

class SubscriptionPlanTableSeeder extends Seeder
{

    public function run()
    {
        SubscriptionPlan::truncate();

        $plans = [
            [
                'id'        => 1,
                'title'     => 'Single User',
                'code'      => 'single',
                'min'       =>  '1',
                'max'       =>  '1',
                'amount'    =>  99,
                'setup_fee' =>  null,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PRO,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 2,
                'title'     => '2-5 Users',
                'code'      => '2-5',
                'min'       =>  '2',
                'max'       =>  '5',
                'amount'    =>  79,
                'setup_fee' =>  null,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PRO,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 3,
                'title'     => '6-15 Users',
                'code'      => '6-15',
                'min'       =>  '6',
                'max'       =>  '15',
                'amount'    =>  69,
                'setup_fee' =>  null,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PRO,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 4,
                'title'     => '16-50 Users',
                'code'      => '16-50',
                'min'       =>  '16',
                'max'       =>  '50',
                'amount'    =>  59,
                'setup_fee' =>  null,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PRO,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 5,
                'title'     => '50+ User',
                'code'      => '50+',
                'min'       =>  '50',
                'max'       =>  '1000000',
                'amount'    =>  49,
                'setup_fee' =>  null,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PRO,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 6,
                'title'     => 'JobProgress Plus',
                'code'      => 'jp-plus',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  79,
                'setup_fee' =>  1495,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PLUS,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 7,
                'title'     => 'JobProgress Basic',
                'code'      => 'jp',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  59,
                'setup_fee' =>  995,
                'product_id' => Product::PRODUCT_JOBPROGRESS,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 8,
                'title'     => 'JobProgress Plus Free',// $0 per month
                'code'      => 'jp-plus-free',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  0,
                'setup_fee' =>  0,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PLUS_FREE,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 9,
                'title'     => 'JobProgress Basic',//$1 for 1 month
                'code'      => 'jp-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  995,
                'product_id' => Product::PRODUCT_JOBPROGRESS,
                'cycles'    => '1',
            ],
            [
                'id'        => 10,
                'title'     => 'JobProgress Basic',//$1 for 2 month
                'code'      => 'jp-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  995,
                'product_id' => Product::PRODUCT_JOBPROGRESS,
                'cycles'    => '2',
            ],
            [
                'id'        => 11,
                'title'     => 'JobProgress Basic',//$1 for 3 month
                'code'      => 'jp-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  995,
                'product_id' => Product::PRODUCT_JOBPROGRESS,
                'cycles'    => '3',
            ],
            [
                'id'        => 12,
                'title'     => 'JobProgress Plus',//$1 for 1 month
                'code'      => 'jp-plus-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  1495,
                'product_id'=> Product::PRODUCT_JOBPROGRESS_PLUS,
                'cycles'    => '1',
            ],
            [
                'id'        => 13,
                'title'     => 'JobProgress Plus',//$1 for 2 month
                'code'      => 'jp-plus-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  1495,
                'product_id'=> Product::PRODUCT_JOBPROGRESS_PLUS,
                'cycles'    => '2',
            ],
            [
                'id'        => 14,
                'title'     => 'JobProgress Plus',//$1 for 3 month
                'code'      => 'jp-plus-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  1495,
                'product_id'=> Product::PRODUCT_JOBPROGRESS_PLUS,
                'cycles'    => '3',
            ],
            [
                'id'        => 15,
                'title'     => 'JobProgress Basic',//$1 for 2 weeks
                'code'      => 'jp-1dollar-2weeks',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  995,
                'product_id' => Product::PRODUCT_JOBPROGRESS,
                'cycles'    => '1',
            ],
            [
                'id'        => 16,
                'title'     => 'JobProgress Plus',//$1 for 2 weeks
                'code'      => 'jp-plus-1dollar-2weeks',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  1495,
                'product_id'=> Product::PRODUCT_JOBPROGRESS_PLUS,
                'cycles'    => '1',
            ],
            [
                'id'        => 17,
                'title'     => 'JobProgress Basic Free',// $0 per month
                'code'      => 'jp-basic-free',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  0,
                'setup_fee' =>  0,
                'product_id' => Product::PRODUCT_JOBPROGRESS_BASIC_FREE,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 18,
                'title'     => 'JobProgress Pro Free',// $0 per month
                'code'      => 'jp-pro-free',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  0,
                'setup_fee' =>  0,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PRO_FREE,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 19,
                'title'     => 'Partner',
                'code'      => 'gaf-plus',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  50,
                'setup_fee' =>  500,
                'product_id' => Product::PRODUCT_GAF_PLUS,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 20,
                'title'     => 'Partner', //$1 for 1 month
                'code'      => 'gaf-plus-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  500,
                'product_id' => Product::PRODUCT_GAF_PLUS,
                'cycles'    => '1',
            ],
            [
                'id'        => 21,
                'title'     => 'Partner', //$1 for 2 month
                'code'      => 'gaf-plus-1dollar',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  1,
                'setup_fee' =>  500,
                'product_id' => Product::PRODUCT_GAF_PLUS,
                'cycles'    => '2',
            ],
            [
                'id'        => 22,
                'title'     => 'JobProgress Standard',
                'code'      => 'jp-standard',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  70,
                'setup_fee' =>  1000,
                'product_id' => Product::PRODUCT_JOBPROGRESS_STANDARD,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 23,
                'title'     => 'PRO',
                'code'      => 'jp-partner',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  60,
                'setup_fee' =>  750,
                'product_id' => Product::PRODUCT_JOBPROGRESS_PARTNER,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 24,
                'title'     => 'JobProgress Multi',
                'code'      => 'jp-multi',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  40,
                'setup_fee' =>  750,
                'product_id' => Product::PRODUCT_JOBPROGRESS_MULTI,
                'cycles'    => 'unlimited',
            ],
            [
                'id'        => 25,
                'title'     => 'JobProgress 25',
                'code'      => 'jp-25',
                'min'       =>  '1',
                'max'       =>  '1000000',
                'amount'    =>  25,
                'setup_fee' =>  0,
                'product_id' => Product::PRODUCT_JOBPROGRESS_25,
                'cycles'    => 'unlimited',
            ],
        ];
    
        foreach ($plans as $key => $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
