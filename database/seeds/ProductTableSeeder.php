<?php
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductTableSeeder extends Seeder
{

    public function run()
    {
        Product::truncate();
        $products = [
                        [
                            'id'     => 1,
                            'title'  => 'JobProgress Basic',
                            'public' => false,
                            'active' => false,
                            'order'  => 1,
                        ],
                        [
                            'id'     => 2,
                            'title'  => 'JobProgress Plus',
                            'public' => false,
                            'active' => false,
                            'order'  => 2,
                        ],
                        [
                            'id'     => 3,
                            'title'  => 'JobProgress Pro',
                            'public' => false,
                            'active' => false,
                            'order'  => 3,
                        ],
                        [
                            'id'     => 4,
                            'title'  => 'JobProgress Plus Free',
                            'public' => false,
                            'active' => true,
                            'order'  => 4,
                        ],
                        [
                            'id'     => 5,
                            'title'  => 'JobProgress Basic Free',
                            'public' => false,
                            'active' => false,
                            'order'  => 5,
                        ],
                        [
                            'id'     => 6,
                            'title'  => 'JobProgress Pro Free',
                            'public' => false,
                            'active' => false,
                            'order'  => 6,
                        ],
                        [
                            'id'     => 7,
                            'title'  => 'Partner',
                            'public' => true,
                            'active' => true,
                            'order'  => 11,
                        ],
                        [
                            'id'     => 8,
                            'title'  => 'JobProgress Standard',
                            'public' => true,
                            'active' => false,
                            'order'  => 7,
                        ],
                        [
                            'id'     => 9,
                            'title'  => 'PRO',
                            'public' => true,
                            'active' => true,
                            'order'  => 8,
                        ],
                        [
                            'id'     => 10,
                            'title'  => 'JobProgress Multi',
                            'public' => false,
                            'active' => true,
                            'order'  => 9,
                        ],
                        [
                            'id'     => 11,
                            'title'  => 'JobProgress 25',
                            'public' => false,
                            'active' => true,
                            'order'  => 10,
                        ],
                    ];

        foreach ($products as $key => $product) {
            Product::create($product);
        }
    }
}
