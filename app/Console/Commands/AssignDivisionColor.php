<?php

namespace App\Console\Commands;

use App\Models\Division;
use Illuminate\Console\Command;

class AssignDivisionColor extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:assign_division_colors';

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
        $companyDivisions = Division::withTrashed()
            ->get(['id', 'company_id'])
            ->groupBy('company_id');

        foreach ($companyDivisions as $divisions) {
            foreach ($divisions as $key => $division) {
                $division->update(['color' => config('default-colors.' . $key)]);
            }
        }
    }
}
