<?php
use Illuminate\Database\Seeder;
use App\Models\EVStatus;

class EVStatusTableSeeder extends Seeder
{

    public function run()
    {
        EVStatus::truncate();
        $status = [
            [
                'id'   => 1,
                'name' => 'Order Placed'
            ],
            [
                'id'   => 2,
                'name' => 'In Process'
            ],
            [
                'id'   => 3,
                'name' => 'Pending'
            ],
            [
                'id'   => 4,
                'name' => 'Closed'
            ],
            [
                'id'   => 5,
                'name' => 'Completed'
            ]

        ];
        EVStatus::insert($status);
    }
}
