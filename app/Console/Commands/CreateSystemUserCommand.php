<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class CreateSystemUserCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_system_user_command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create System User.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        EnterCompanyID :
        $companyID = $this->ask('Enter Company Id : ');
        $company = Company::whereId($companyID)->first();
        if (!$company) {
            $this->error('Invalid Company Id');
            goto EnterCompanyID;
        }

        if ($company->anonymous) {
            $anonymous = $company->anonymous;
            $anonymous->profile()->delete();
            $anonymous->delete();
        }
        $userRepo = App::make(\App\Repositories\UserRepository::class);
        $userDetails = [];
        $subscriber = $company->subscriber;
        $profile = $subscriber->profile;
        $profileData = [
            'address' => $profile->address,
            'address_line_1' => $profile->address_line_1,
            'city' => $profile->city,
            'state_id' => $profile->state_id,
            'country_id' => $profile->country_id,
            'zip' => $profile->zip
        ];
        $productId = $company->subscription->product_id;
        $userRepo->createAnonymous(
            $company,
            $userDetails,
            $profileData,
            $productId
        );
    }
}
