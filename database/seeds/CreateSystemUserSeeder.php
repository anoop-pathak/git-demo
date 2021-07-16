<?php
use Illuminate\Database\Seeder;

class CreateSystemUserSeeder extends Seeder
{

    public function run()
    {
        $companies = Company::has('anonymous', '=', 0)->get();
        foreach ($companies as $company) {
            $this->createSystemUser($company);
        }
    }

    private function createSystemUser($company)
    {
        try {
            $userRepo = App::make('JobProgress\Repositories\UserRepository');
            $userDetails = [];
            $subscriber = $company->subscriber;
            $profile = $subscriber->profile;
            $profileData = [
                'address'        => $profile->address,
                'address_line_1' => $profile->address_line_1,
                'city'           => $profile->city,
                'state_id'       => $profile->state_id,
                'country_id'     => $profile->country_id,
                'zip'            => $profile->zip
            ];
            $productId = $company->subscription->product_id;
            $userRepo->createAnonymous(
                $company,
                $userDetails,
                $profileData,
                $productId
            );
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
