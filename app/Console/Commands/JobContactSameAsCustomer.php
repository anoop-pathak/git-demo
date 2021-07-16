<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

class JobContactSameAsCustomer extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:job_contact_same_as_customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'set false if they have not any contact of job';

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
        Job::has('jobContact')->update(['contact_same_as_customer' => false]);
    }
}
