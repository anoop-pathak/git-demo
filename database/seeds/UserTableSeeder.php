<?php

// Composer: "fzaninotto/faker": "v1.3.0"
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Role;
use App\Models\UserProfile;

class UserTableSeeder extends Seeder
{

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // User::truncate();
        // UserProfile::truncate();
        
        $user = User::create([
            'email' => 'superadmin@jobprogress.com',
            'password' => Hash::make('dummy123'),
            'first_name' => "David",
            'last_name' => 'Buzzelli',
            'group_id' => 1
        ]);
        $user->attachRole(Role::byName('super-admin'));

        $userProfileData = [
            'user_id'       =>  $user->id,
            'address'       =>  '480 Sidney Road',
            'city'          =>  'Piscataway',
            'state_id'      =>  30,
            'zip'           =>  'NJ 08854',
            'country_id'    =>  1,
            'position'      =>  'super admin'
        ];
        
        $profile = UserProfile::create($userProfileData);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
