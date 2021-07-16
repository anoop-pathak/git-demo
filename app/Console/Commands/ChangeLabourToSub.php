<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeLabourToSub extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:change_labour_to_sub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert Labour To Sub Contractors.';

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
        try {
            DB::beginTransaction();

            User::whereGroupId(User::GROUP_LABOR)->chunk(100, function ($labours) {

                foreach ($labours as $key => $labour) {
                    // change user role
                    $role = Role::byName('sub-contractor');
                    $labour->detachRole($role);
                    $labour->attachRole($role);

                    // save job_labour data into job_sub_contractor
                    $labour->jobsAsLabor()->chunk(100, function ($jobLabor) use ($labour) {
                        $data = [];
                        foreach ($jobLabor as $key => $jl) {
                            $data[] = [
                                'sub_contractor_id' => $labour->id,
                                'job_id' => $jl->job_id,
                                'schedule_id' => $jl->schedule_id,
                                'work_crew_note_id' => $jl->work_crew_note_id,
                            ];
                        }

                        DB::table('job_sub_contractor')->insert($data);
                    });

                    $labour->jobsAsLabor()->detach();

                    // update user group
                    $labour->update([
                        'company_name'  => $labour->full_name." ($labour->company_name)",
                        'group_id'      => User::GROUP_SUB_CONTRACTOR,
                    ]);
                }
            });
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        DB::commit();

        return true;
    }
}
