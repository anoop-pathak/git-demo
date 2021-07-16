<?php

class ChangeAdminsToOwnersSeeder extends Seeder
{

    public function run()
    {
        User::whereGroupId(User::GROUP_ADMIN)->update(['group_id' => User::GROUP_OWNER]);
    }
}
