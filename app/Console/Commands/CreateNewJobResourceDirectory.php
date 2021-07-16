<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Job;
use App\Services\Resources\ResourceServices;
use Carbon\Carbon;
use App\Exceptions\DirExistsException;

class CreateNewJobResourceDirectory extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_new_job_resource_directory';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create new Directory in Job Resources';

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
		$companyId =  $this->ask('Enter company id:');
		$company = Company::find($companyId);

		if(!$company){
			$this->info('Please enter valid company id.');
			return;
		}

		$dirName = $this->ask('Enter directory name:');
		$resourceService = app(ResourceServices::class);

		$this->info("Start Time: ".Carbon::now()->toDateTimeString());
		$totalJobs = Job::where('company_id', $company->id)->count();

		$jobs = Job::where('company_id', $company->id)->with('jobMeta')
			->chunk(50, function($jobs) use(&$totalJobs, $resourceService, $dirName) {

				foreach ($jobs as $job) {
					$this->info('Pending Records: '. --$totalJobs . ' Job Id:'. $job->id);

					$parentDir = $job->getResourceId();

					if(!$parentDir) {
						$this->info('Warning-----------Job Id:'. $job->id);
						continue;
					}

					try{
						$resourceService->createDIr($dirName, $parentDir);
					} catch(DirExistsException $e){
					} catch(\Exception $e){
						$this->info($e->getMessage());
					}
				}
            });

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}

}