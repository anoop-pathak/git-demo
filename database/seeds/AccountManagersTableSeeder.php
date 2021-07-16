<?php

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class AccountManagersTableSeeder extends Seeder
{
    private $faker;
    
    public function run()
    {
        AccountManager::truncate();
        $this->faker = $faker = Faker::create();

        $states = State::pluck('id');
        
        foreach (range(1, 5) as $index) {
            $company = AccountManager::create([
                'first_name'            =>  $faker->firstName,
                'last_name'             =>  $faker->lastName,
                'state_id'              =>  $faker->randomElement($states),
                'contact'               =>  $faker->phoneNumber,
                'email'                 =>  $faker->email,
                'notes'                 =>  $faker->paragraph(5)
            ]);
        }
    }
}
