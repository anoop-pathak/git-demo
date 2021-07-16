<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

class UpdateProjectDisplayOrderInJobs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_project_display_order';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update projects display order in jobs.';

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
	public function fire()
	{
		$this->info("Start Time For Update Project Order: ".Carbon::now()->toDateTimeString());

		Job::on('mysql2')
			->where('multi_job', true)
			->select('id')
			->whereNull('parent_id')
			->chunk(100, function ($jobs) {

			foreach ($jobs as $job) {
				$ids = Job::on('mysql2')
							->where('parent_id', $job->id)
							->withTrashed()
							->orderBy('id', 'asc')
							->pluck('id')
							->toArray();
				foreach($ids as $key => $id) {
					DB::table('jobs')->where('id', $id)->update(['display_order' => $key+1]);
				}
			}

		});

		$this->info("End Time For Update Project Order: ".Carbon::now()->toDateTimeString());
	}
}
