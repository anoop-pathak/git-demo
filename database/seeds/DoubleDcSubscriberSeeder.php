<?php

use Faker\Factory as Faker;
use JobProgress\Repositories\WorkflowRepository;
use JobProgress\Setup\CompanySetup;
use Illuminate\Database\Seeder;

class DoubleDcSubscriberSeeder extends Seeder
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
        Company::truncate();
        DB::table('company_billing')->truncate();
        DB::table('company_contacts')->truncate();
        DB::table('company_job_type')->truncate();
        DB::table('company_notes')->truncate();
        DB::table('company_setup_action')->truncate();
        DB::table('company_state')->truncate();
        DB::table('company_trade')->truncate();
        Resource::truncate();

        $this->faker = $faker = Faker::create();
        $trades = Trade::pluck('id');
        
        $company = Company::create([
            'name'                  =>  'DoubleD Construction',
            'office_address'        =>  '480 Sidney Road',
            'office_city'           =>  'Piscataway',
            'office_state'          =>  30,
            'office_zip'            =>  'NJ 08854',
            'office_country'        =>  1,
            'office_phone'          =>  $faker->phoneNumber,
            'office_email'          =>  'admin@doubledconstruction.com',
            'office_fax'            =>  $faker->phoneNumber,
            'account_manager_id'    =>  1,
            'company_country'       =>  1,
        ]);
        
        $company->trades()->attach([1,2,4,6,7,8]);
        $company->states()->attach([1,5,10,30]);
        $company->save();
        $this->createSubscriberUser($company);

        try {
            $this->companySetup->run($company);
        } catch (\Exception $e) {
            //exception handle..
        }
    }

    private function createSubscriberUser($company)
    {
        $faker = $this->faker;

        $userData = [
            'first_name'        =>  'David',
            'last_name'         =>  'Buzzelli',
            'password'          =>  'dummy123',
            'email'             =>  'admin@doubledconstruction.com',
            'company_id'        =>  $company->id,
            'group_id'          =>  2,
            'admin_privilege'   =>  1,
        ];

        $user = User::create($userData);
        $user->attachRole(Role::byName('plus-admin'));
        $userProfileData = [
            'user_id'       =>  $user->id,
            'address'       =>  '480 Sidney Road',
            'city'          =>  'Piscataway',
            'state_id'      =>  30,
            'zip'           =>  'NJ 08854',
            'country_id'    =>  1,
            'position'      =>  'admin'
        ];
        
        $profile = UserProfile::create($userProfileData);
        $profile->user()->associate($user);

        $this->workflowRepo->setupDefault($company->id, 1);
    }
}
