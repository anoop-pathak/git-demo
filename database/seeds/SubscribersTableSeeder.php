<?php

use Faker\Factory as Faker;
use JobProgress\Repositories\WorkflowRepository;
use JobProgress\Setup\CompanySetup;
use Illuminate\Database\Seeder;

class SubscribersTableSeeder extends Seeder
{
    private $faker;
    protected $workflowRepo;
    protected $companySetup;

    public function __construct(WorkflowRepository $repo, CompanySetup $companySetup)
    {
        $this->workflowRepo = $repo;
        $this->companySetup = $companySetup;
    }
    
    public function run()
    {
        $this->faker = $faker = Faker::create();
        
        $states = State::pluck('id');
        $countries = Country::pluck('id');
        $trades = Trade::pluck('id');
        $accountManagers = AccountManager::pluck('id');
        foreach (range(1, 1) as $index) {
            $company = Company::create([
                'name'                  =>  $faker->company,
                'office_address'        =>  $faker->address,
                'office_address_line_1' =>  $faker->address,
                'office_city'           =>  $faker->city,
                'office_state'          =>  $faker->randomElement($states),
                'office_zip'            =>  $faker->postcode,
                'office_country'        =>  $faker->randomElement($countries),
                'office_phone'          =>  $faker->phoneNumber,
                'office_email'          =>  $faker->email,
                'office_fax'            =>  $faker->phoneNumber,
                'additional_email'      =>  [$faker->email, $faker->email],
                'additional_phone'      =>  [$faker->phoneNumber, $faker->phoneNumber],
                'account_manager_id'    =>  1,
                'company_country'       =>  1,
            ]);

            $company->trades()->attach($faker->randomElement($trades));
            $company->states()->attach($faker->randomElement($states));
            $company->save();
            $this->createSubscriberUser($company, 2, 'basic-admin');
            $this->createSubscriberUser($company, 3, 'basic-standard');
            $this->createSubscriberUser($company, 2, 'plus-admin');
            $this->createSubscriberUser($company, 3, 'plus-standard');
            try {
                $this->companySetup->run($company);
                $this->workflowRepo->setupDefault($company->id, 1, 2);
            } catch (\Exception $e) {
                //exception handle..
            }
        }
    }

    private function createSubscriberUser($company, $group, $role)
    {
        $faker = $this->faker;
        $states = State::pluck('id');
        $countries = Country::pluck('id');
        $userData = [
            'first_name'        =>  $faker->firstName,
            'last_name'         =>  $faker->lastName,
            'password'          =>  'dummy123',
            'email'             =>  $faker->email,
            'company_id'        =>  $company->id,
            'group_id'          =>  $group,
            'admin_privilege'   =>  $faker->boolean(25),
        ];
        $user = User::create($userData);
        $user->attachRole(Role::byName($role));
                
        $userProfileData = [
            'user_id'       =>  $user->id,
            'address'       =>  $faker->address,
            'city'          =>  $faker->city,
            'state_id'      =>  $faker->randomElement($states),
            'zip'           =>  $faker->postcode,
            'country_id'    =>  $faker->randomElement($countries),
            'position'      =>  $faker->sentence(4)
        ];
        
        $profile = UserProfile::create($userProfileData);
        $profile->user()->associate($user);
    }
}
