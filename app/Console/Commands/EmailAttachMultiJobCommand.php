<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmailAttachMultiJobCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:email_attach_multi_job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Multiple job attach to email.';

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
        $query = \App\Models\Email::whereNotNull('job_id');
        $list = $query->pluck('job_id', 'id')->toArray();
        $data = [];
        foreach ($list as $emailId => $jobId) {
            $data[] = ['email_id' => $emailId, 'job_id' => $jobId];
        }
        if (!empty($data)) {
            DB::table('email_job')->insert($data);
        }
        $query->update(['job_id' => null]);
    }
}
