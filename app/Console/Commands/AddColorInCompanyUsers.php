<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddColorInCompanyUsers extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:assign_users_color';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign colors to users';

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
        $userByCompanies = User::where('group_id', '!=', User::GROUP_SUPERADMIN)
            ->withTrashed()
            ->get(['id', 'company_id'])
            ->groupBy('company_id');

        foreach ($userByCompanies as $companyUsers) {
            foreach ($companyUsers as $key => $user) {
                DB::table('users')
                    ->whereId($user->id)
                    ->update(['color' => config('colors.' . $key)]);
            }
        }
    }
}
