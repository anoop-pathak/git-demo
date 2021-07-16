<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Services\Resources\ResourceServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class CreateJobsAdminOnlyDirectory extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_jobs_admin_only_directory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create jobs admin only directory.';

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
        $dirName = config('jp.job_admin_only');
        $resourceService = App::make(ResourceServices::class);

        $exclude = [];

        //get jobs id of exists admin only dir
        if (File::exists('admin_only_dir_exists_for_jobs.txt')) {
            $exclude = explode(',', rtrim(File::get('admin_only_dir_exists_for_jobs.txt'), ','));
        }

        Job::withTrashed()
            ->with('jobMeta')
            ->whereNotIn('id', $exclude)
            ->orderBy('id', 'desc')
            ->chunk(200, function ($jobs) use ($dirName, $resourceService) {
                foreach ($jobs as $job) {
                    $resourceId = $job->getResourceId();
                    $resourceService->createDir(
                        $dirName,
                        $resourceId,
                        $locked = true,
                        $dirName,
                        $meta = ['admin_only' => true, 'stop_exception' => true,]
                    );

                    //job id append in text file.
                    File::append('admin_only_dir_exists_for_jobs.txt', $job->id . ',');
                }
            });
    }
}
