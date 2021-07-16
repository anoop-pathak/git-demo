<?php /** @noinspection PhpUndefinedClassInspection */

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Job;
use App\Models\JobMeta;
use App\Models\JobWorkflow;
use App\Models\JobWorkflowHistory;
use App\Models\Phone;
use App\Services\Jobs\JobNumber;
use App\Services\JobSchedules\JobSchedulesService;
use App\Services\Solr\Solr;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Excel;

class BellCustomerJobImport extends Command
{


    protected $user = 5099;
    protected $companyId = 349;
    protected $scope;
    protected $context;
    protected $resourceRepo;
    protected $resourceService;
    protected $parentDir;
    protected $jobResources;
    protected $currentWorkflow;
    protected $jobSchedulesService;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:bell_customer_job_import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Customers and Jobs.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // DB::beginTransaction();

        try {
            $filename2005 = storage_path() . '/data/Bell Roofing DB 2005.xls';
            $filename2006 = storage_path() . '/data/Bell Roofing DB 2006.xls';
            $filename2007 = storage_path() . '/data/Bell Roofing DB 2007.xls';
            $filename2008 = storage_path() . '/data/Bell Roofing DB 2008.xls';
            $filename2009 = storage_path() . '/data/Bell Roofing DB 2009.xls';
            $filename2010 = storage_path() . '/data/Bell Roofing DB 2010.xls';
            $filename2011 = storage_path() . '/data/Bell Roofing DB 2011.xls';
            $filename2012 = storage_path() . '/data/Bell Roofing DB 2012.xls';
            $filename2013 = storage_path() . '/data/Bell Roofing DB 2013.xls';
            $filename2014 = storage_path() . '/data/Bell Roofing DB 2014.xls';

            $this->importFiles($filename2005);
            $this->info('FILE 2005 COMPLETED.');
            $this->importFiles($filename2006);
            $this->info('FILE 2006 COMPLETED.');
            $this->importFiles($filename2007);
            $this->info('FILE 2007 COMPLETED.');
            $this->importFiles($filename2008);
            $this->info('FILE 2008 COMPLETED.');
            $this->importFiles($filename2009);
            $this->info('FILE 2009 COMPLETED.');
            $this->importFiles($filename2010);
            $this->info('FILE 2010 COMPLETED.');
            $this->importFiles($filename2011);
            $this->info('FILE 2011 COMPLETED.');
            $this->importFiles($filename2012);
            $this->info('FILE 2012 COMPLETED.');
            $this->importFiles($filename2013);
            $this->info('FILE 2013 COMPLETED.');
            $this->importFiles($filename2014);
            $this->info('FILE 2014 COMPLETED.');

            // Excel::load($filename, function($reader) {
            // 	$reader->each(function($sheet){
            // 		// dd('sheet');
            // 		$sheet->each(function($record){
            // 			if ($record) {
            // 				$this->saveData($record);
            // 			}
            // 		});
            // 	});
            // });
        } catch (\Exception $e) {
            // DB::rollBack();
            Log::warning($e);
            return $e->getMessage();
        }
        // DB::commit();

        return true;
    }

    private function importFiles($filename)
    {
        Excel::load($filename, function ($reader) {
            $reader->each(function ($record) {
                if ($record) {
                    $this->saveData($record);
                }
            });
        });
    }

    private function saveData($record)
    {
        $completedDate = $this->getValidDate($record->date);

        $firstName = strstr($record->name, ' ', true);
        $lastName = trim((strstr($record->name, ' ')));

        // save address
        $address = new Address([
            'address' => $record->address,
            'company_id' => $this->scope->id(),
        ]);

        $location = geocode($address->address);

        $address->lat = isset($location['lat']) ? $location['lat'] : null;
        $address->long = isset($location['lng']) ? $location['lng'] : null;
        $address->save();

        // save customers
        $customerData = [
            'company_id' => $this->scope->id(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address_id' => $address->id,
            'billing_address_id' => $address->id,
        ];

        $customer = Customer::create($customerData);

        // add customer phones
        $phones = Phone::create([
            'customer_id' => $customer->id,
            'number' => '0000000000',
            'label' => 'phone',
        ]);

        $description = <<<DESCRIPTION
Shingle: $record->shingle
Crew: $record->crew
No. of squares: $record->number_of_squares
Warranty: $record->warranty
DESCRIPTION;

        // save jobs
        $job = Job::create([
            'customer_id' => $customer->id,
            'company_id' => $this->scope->id(),
            'address_id' => $address->id,
            'description' => $description,
            'share_token' => generateUniqueToken(),
            'workflow_id' => $this->currentWorkflow->id,
            'completion_date' => $completedDate,
            'same_as_customer_address' => 1,
        ]);

        $job->trades()->sync(['8']);
        $jobNumber = new JobNumber;
        $jobNumber = $jobNumber->generate($job);
        Job::where('id', $job->id)->update(['number' => $jobNumber]);

        // create resources
        if ($this->parentDir) {
            $this->createResources($job);
        }

        // create workflow
        if ($this->currentWorkflow) {
            $this->createWorkflow($job);
        }

        Solr::customerIndex($customer->id);

        // create job schedule
        if ($completedDate) {
            $this->addJobSchedule($completedDate, $customer, $job);
        }
    }

    private function getValidDate($date)
    {
        if ($date instanceof Carbon) {
            return $date->toDateString();
        }

        $formatChanged = false;
        $count = 0;
        $date = preg_replace('/\s+/', '', $date);

        if (empty(trim($date))) {
            return null;
        }

        if (strpos($date, "\\")) {
            $date = str_replace("\\", "/", $date);
            $count = substr_count($date, "/");
            $formatChanged = true;
        }

        if ($formatChanged && $count < 2) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d'); //mdy
        } catch (\Exception $e) {
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d'); //dmy
        } catch (\Exception $e) {
            $this->error(getErrorDetail($e));
            Log::warning($e);
            return null;
        }
    }

    private function createResources($job)
    {
        try {
            $job = Job::find($job->id);

            $resource = $this->resourceService->createDir($job->number, $this->parentDir->id, true);

            $job->saveMeta('resource_id', $resource->id);

            foreach ($this->jobResources as $jobResource) {
                if (isTrue($jobResource['locked'])) {
                    $photoDir = $this->resourceService->createDir($jobResource['name'], $resource->id, true);
                    $job->saveMeta(JobMeta::DEFAULT_PHOTO_DIR, $photoDir->id);
                } else {
                    $this->resourceService->createDir($jobResource['name'], $resource->id);
                }
            }

            // create admin only dir
            $this->resourceService->createDir(
                config('jp.job_admin_only'),
                $resource->id,
                $locked = true,
                $name = config('jp.job_admin_only'),
                $meta = ['admin_only' => true]
            );
        } catch (\Exception $e) {
            $this->error(getErrorDetail($e));
            Log::info($job->id);
            Log::warning($e);
        }
    }

    private function createWorkflow($job)
    {
        try {
            $stages = $this->currentWorkflow->stages;
            $lastStage = $this->currentWorkflow->stages->last();

            foreach ($stages as $key => $stage) {
                if ($stage === $lastStage) {
                    JobWorkflow::create([
                        'company_id' => $this->scope->id(),
                        'job_id' => $job->id,
                        'current_stage' => $stage->code,
                        'modified_by' => \Auth::id(),
                        'stage_last_modified' => Carbon::now(),
                    ]);
                } else {
                    JobWorkflowHistory::create([
                        'company_id' => $this->scope->id(),
                        'job_id' => $job->id,
                        'stage' => $stage->code,
                        'modified_by' => \Auth::id(),
                        'start_date' => Carbon::now(),
                        'completed_date' => Carbon::now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->error(getErrorDetail($e));
            Log::info($job->id);
            Log::warning($e);
        }
    }

    private function addJobSchedule($date, $customer, $job)
    {
        $date = Carbon::parse($date);
        $startDate = $date->toDateTimeString();
        $endDate = $date->addHours(23)->addMinutes(59)
            ->toDateTimeString();

        try {
            $meta = [];
            $meta['job_id'] = $job->id;
            $title = $customer->full_name . ' / ROOFING';
            $this->jobSchedulesService = App::make(JobSchedulesService::class);
            $schedule = $this->jobSchedulesService->makeSchedule(
                $title,
                $startDate,
                $endDate,
                \Auth::id(),
                $meta
            );
        } catch (\Exception $e) {
            $this->error(getErrorDetail($e));
            Log::info($job->id);
            Log::warning($e);
        }
    }
}
