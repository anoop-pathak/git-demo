<?php
use Illuminate\Database\Seeder;

class EVMeasurmentsTableSeeder extends Seeder{

    public function run(){
        DB::table('ev_measurments')->truncate();
        $status = [
            [
                'id'   => 1,
                'name' => 'Primary Structure + Detached Garage'
            ],
            [
                'id'   => 2,
                'name' => 'Primary Structure Only'
            ],
            [
                'id'   => 3,
                'name' => 'All Structures on Parcel'
            ],
            [
                'id'   => 4,
                'name' => 'Commercial Complex'
            ],
            [
                'id'   => 5,
                'name' => 'Other (please provide instructions)'
            ],

        ];
        DB::table('ev_measurments')->insert($status);
    }
}
