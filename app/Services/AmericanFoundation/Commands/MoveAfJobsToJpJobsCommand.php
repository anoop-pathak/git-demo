<?php

namespace App\Services\AmericanFoundation\Commands;

use App\Services\AmericanFoundation\Models\AfJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\Trade;

class MoveAfJobsToJpJobsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:move_af_jobs_to_jp_jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move American Foundation Jobs to JobProgress Jobs Table.';

    private $inc = 0;

    // private $systemUserId = 722;

    private $otherTradeId = null;

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

        $this->otherTradeId = Trade::getOtherTradeId();

        $this->info("Script Starts.");
        AfJob::with(['customer'])
            ->chunk(500, function($jobs){
            foreach ($jobs as $job) {
                if($job->job_id) {
                    continue;
                }

                try {
                    setAuthAndScope(config('jp.american_foundation_system_user_id'));
                    setScopeId($job->company_id);

                    $jobArr = $this->jobFieldsMapping($job);
                    $service = App::make('App\Services\AmericanFoundation\Services\AfJobService');
                    $savedjob = $service->createJpCustomer($jobArr);
                    $job->job_id = $savedjob->id;
                    $job->save();

                    $this->inc++;
                    if($this->inc %100 == 0) {
                        $this->info("Total Processing jobs:- " . $this->inc);
                    }

                } catch (\Exception $e) {
                    Log::error("Error in American Foundation Move AfJobs to Jobs table");
                    Log::error($e);
                }
            }
        });
        $this->info("Total Processed jobs:- " . $this->inc);
        $this->info("Script Ends.");
    }

    private function jobFieldsMapping(AfJob $job)
    {
        $customer = $job->customer;
        $jpCustomer = $customer->jpCustomer;

        $payload = [
            'name'   => $job->name,
            'customer_id' => $customer->customer_id,
            'description' => $this->setDescription($job),
            'address_id'  => $jpCustomer->address_id,
            'same_as_customer_address' => 1,
            'trades' => [$this->otherTradeId],
            'other_trade_type_description' => 'i360 Job Transfer',
        ];

        if(ine($job, 'job_number')) {
            $payload['alt_id'] = $job['job_number'];
        }

        return $payload;
    }

    public function setDescription($data)
    {
        $description = null;
        if(ine($data, 'project_number')) {
            $description .= "project_number: " . $data['project_number'] . "\n";
        }
        if(ine($data, 'status')) {
            $description .= "status: " . $data['status'] . "\n";
        }
        if(ine($data, 'job_type')) {
            $description .= "Job Type: " . $data['job_type'] . "\n";
        }

        if(ine($data, 'status')) {
            $description .= "i360 Job Transfer";
        }

        return $description;
    }
}