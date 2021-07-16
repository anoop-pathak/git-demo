<?php
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class CustomersTableSeeder extends Seeder
{
    private $faker;

    public function run()
    {
        Address::truncate();
        Customer::truncate();
        Job::truncate();
        JobWorkFlow::truncate();

        $this->faker = $faker = Faker::create();
        $states = State::pluck('id');
        $countries = Country::pluck('id');
        $trades = Trade::pluck('id');
        $rep = User::pluck('id');
        foreach (range(1, 5) as $index) {
            $address = Address::create([
                'address'       =>  $faker->address,
                'city'          =>  $faker->city,
                'state_id'      =>  $faker->randomElement($states),
                'zip'           =>  $faker->postcode,
                'country_id'    =>  $faker->randomElement($countries),
            ]);
            $customer = Customer::create([
                'company_id'            =>  1,
                'first_name'        =>  $faker->firstName,
                'last_name'         =>  $faker->lastName,
                'email'             =>  $faker->email,
                'rep_id'            =>  $faker->randomElement($rep),
                'address_id'        =>  $address->id,
                'billing_address_id'=>  $address->id,
                'created_by'        => 1,
            ]);

            foreach (range(1, 2) as $index) {
                $job = Job::create([
                    'company_id'        =>  1,
                    'customer_id'       =>  $customer->id,
                    'workflow_id'       =>  1,
                    'name'              => $faker->name,
                    'description'       => $faker->sentence(15),
                    'number'            => $faker->unique()->randomDigit,
                    'address_id'        => $address->id,
                    'created_by'        => 1,
                    'last_modified_by'  => 1,
                    'same_as_customer_address' => 1,
                ]);

                JobWorkFlow::create([
                    'company_id'        =>  1,
                    'job_id'            =>  $job->id,
                    'current_stage'     =>  123456,
                    'current_step'     =>   0,
                    'modified_by'  => 1,
                    'stage_last_modified'  => '2015-02-06 20:35:58'
                ]);
            }
        }
    }
}
