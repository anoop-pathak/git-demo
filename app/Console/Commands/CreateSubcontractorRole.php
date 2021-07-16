<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class CreateSubcontractorRole extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_subcontractor_role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        $role = Role::firstOrCreate([
            'name' => 'sub-contractor'
        ]);

        $permission = Permission::firstOrCreate([
            'name' => 'subcontractor_page_apis',
            'display_name' => ucwords(str_replace('_', ' ', 'subcontractor_page_apis')),
        ]);

        $role->perms()->sync((array)$permission->id);

        $subcontractors = User::whereGroupId(User::GROUP_SUB_CONTRACTOR)->get();

        foreach ($subcontractors as $subcontractor) {
            $subcontractor->attachRole($role);
        }
    }
}
