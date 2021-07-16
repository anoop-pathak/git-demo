<?php
use Illuminate\Database\Seeder;
use App\Models\Group;

class GroupTableSeeder extends Seeder
{

    public function run()
    {
        Group::truncate();

        $groups = ['super admin','admin','standard','sub contractor','owner','anonymous','labor', 'sub contractor prime', 'open api user'];

        foreach ($groups as $key => $value) {
            Group::create([
                'name' => $value
            ]);
        }
    }
}
