<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\Resources\ResourceServices;
use App\Exceptions\InvalidResourcePathException;
use App\Exceptions\DirExistsException;
use App\Models\Job;
use App\Models\JobMeta;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;
use Exception;

class AddPhotoDirectoryInMissingJobs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_photo_directory_in_missing_jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will be used to add Photo directory in missing jobs.';

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
		$this->canceledIds = [];

		$this->resourceService = app(ResourceServices::class);

		$now = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$now);

		$query = Job::with(['jobMeta'])
			->withTrashed()
			->where('number', '<>', '')
			->orderBy('id')
			->whereIn('id', function($query) {
				$query->select('job_id')
					->from('job_meta')
					->whereIn('meta_key', [JobMeta::RESOURCE_ID, JobMeta::DEFAULT_PHOTO_DIR])
					->groupBy('job_id')
					->havingRaw("COUNT(*) = 1");
			});

		$this->info("\nTotal records will be processed: ".$query->count());

		$this->addPhotoDirectory($query);

		$now = Carbon::now()->toDateTimeString();
		$this->info("\nCommand completed at: ".$now);
	}

	private function addPhotoDirectory($query)
	{
		$queryClone = clone $query;

		$queryClone->chunk(100, function($jobs) {
			foreach ($jobs as $key => $job) {
				$this->saveDirectory($job);
			}
		});

		$query->whereNotIn('id', $this->canceledIds);

		$this->info("Pending records: ".$query->count());

		if($query->count()) {
			$this->addPhotoDirectory($query);
		}

	}

	private function saveDirectory($job)
	{
		$errMsg = null;
		DB::beginTransaction();

		try {
			$parentId = $job->getResourceId();
			if($parentId) {
				$photoDir = $this->resourceService->getDirWithName(Resource::PHOTOS_DIR, $parentId);

				if($photoDir) {
					if($photoDir->locked) {
						$photoDir->locked = true;
					}
					$photoDir->save();
				} else {
					$meta['stop_transaction'] = true;

					$photoDir = $this->resourceService->createDir(
						Resource::PHOTOS_DIR,
						$parentId,
						true,
						null,
						$meta
					);
				}

				$job->saveMeta(JobMeta::DEFAULT_PHOTO_DIR, $photoDir->id);
			}

			DB::commit();
		} catch (InvalidResourcePathException $e) {
			DB::rollback();
			$this->canceledIds[] = $job->id;
			$errMsg = getErrorDetail($e);
		} catch (DirExistsException $e) {
			DB::rollback();
			$this->canceledIds[] = $job->id;
			$errMsg = getErrorDetail($e);
		} catch (Exception $e) {
			DB::rollback();
			$errMsg = getErrorDetail($e);

			$this->canceledIds[] = $job->id;
		}

		if($errMsg) {
			$this->info("\nError occurred for job id: ".$job->id."\nError detail.".$errMsg);
		}
	}

}
