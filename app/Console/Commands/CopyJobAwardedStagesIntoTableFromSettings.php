<?php

namespace App\Console\Commands;

use App\Models\JobAwardedStage;
use App\Models\Setting;
use Illuminate\Console\Command;

class CopyJobAwardedStagesIntoTableFromSettings extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:copy_job_awarded_stages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'copy job awarded stages into job_awarded_sages table from settings table';

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
        Setting::where('key', 'JOB_AWARDED_STAGE')
            ->chunk(100, function ($settings) {
                foreach ($settings as $setting) {
                    JobAwardedStage::create([
                        'company_id' => $setting->company_id,
                        'stage' => $setting->value,
                        'active' => 1,
                    ]);
                }
            });
    }
}
