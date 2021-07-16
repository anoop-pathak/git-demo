<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\Resource;
use Illuminate\Console\Command;

class ManageOldSharedResourceHOP extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_old_shared_resource_home_owner_page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Old Shared Resource.';

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
        $this->manageOldSharedResource();
    }

    private function manageOldSharedResource()
    {
        Job::with(['jobMetaHOP'])->chunk(100, function ($jobs) {
            foreach ($jobs as $job) {
                if (!$job->jobMetaHOP) {
                    continue;
                }

                $meta = $job->jobMetaHOP;
                $root = Resource::where('id', $meta->meta_value)
                    ->first();

                if (!$root) {
                    continue;
                }

                $root->descendants()->update(['share_on_hop' => true]);
            }
        });
    }
}
