<?php

// Composer: "fzaninotto/faker": "v1.3.0"
// use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

use App\Models\Supplier;

class SuppliersTableSeeder extends Seeder
{

    public function run()
    {
        $suppliers = [
            'ABC Supply',
            'SRS',
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate([
                'name' => $supplier,
            ]);
        }
    }
}
