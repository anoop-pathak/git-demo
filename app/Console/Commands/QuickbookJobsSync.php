<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Settings;

class QuickbookJobsSync extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:quickbook_jobs_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickbook jobs sync';

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

        $companies = Company::has('quickbook')->with([
            'customers' => function ($query) {
                $query->whereNotNull('quickbook_id');
            }
        ])->get();

        if (!$companies->count()) {
            return false;
        }

        $this->quickbookService = App::make(\App\Services\QuickBooks\QuickBookService::class);
        $companyId = null;
        try {
            foreach ($companies as $company) {
                $companyId = $company->id;
                $quickbook = $company->quickbook;
                $customers = $company->customers;

                $context = App::make(\App\Services\Contexts\Context::class);
                Config::set('company_scope_id', $company->id);
                $context->set(\Company::find($company->id));
                $timezone = Settings::get('TIME_ZONE');
                $quickbook = $company->quickbook;
                $token = $this->quickbookService->getToken();

                if (!$this->quickbookService->isValidToken($token)) {
                    continue;
                }

                $customerIds = $company->customers->pluck('id')->toArray();
                $jobIds = Job::whereIn('customer_id', $customerIds)
                    ->whereQuickbookSync(false)
                    ->whereNotNull('quickbook_id')
                    ->has('customer')
                    ->pluck('id')->toArray();
                $this->multiJobsSync($token, $jobIds, $timezone);

                $this->jobsSync($token, $jobIds, $timezone);
            }
        } catch (\Exception $e) {
            $msg = 'Company Id ' . $companyId . ' ';
            $msg .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            echo 'Quickbook Jobs Sync Command: ' . $msg;
        }
    }

    /**
     * Multi Jobs Sync
     * @param  Object $token Token
     * @param  Array $jobIds Array of job ids
     * @return Booolean
     */
    private function multiJobsSync($token, $jobIds, $timezone)
    {
        $jobIds = Job::whereIn('id', (array)$jobIds)
            ->whereQuickbookSync(false)
            ->whereMultiJob(true)
            ->whereNotNull('quickbook_id')
            ->pluck('id')->toArray();

        if (empty($jobIds)) {
            return true;
        }

        $service = App::make(\App\Services\QuickBooks\QuickBookService::class);
        goto batch;

        batch:{

        $query = Job::whereIn('id', (array)$jobIds)
            ->whereQuickbookSync(false)
            ->whereMultiJob(true)
            ->whereNotNull('quickbook_id');

        $query->chunk(20, function ($jobs) use ($token, $service, $timezone) {
            $jobsData = [];
            foreach ($jobs as $key => $job) {
                $customer = $job->customer;
                $referenceId = $customer->quickbook_id;
                $displayName = $job->getQuickbookDisplayName();
                $dateTime = convertTimezone($job->created_at, $timezone);
                $createdDate = $dateTime->format('Y-m-d');
                $quickbookJob = $service->getQuickbookCustomer($token, $job->quickbook_id, $displayName);
                $billingAddress = $customer->billing;

                // map payment data for batch request
                $data = [
                    "Job" => true,
                    "DisplayName" => $displayName,
                    "BillWithParent" => true,
                    "ParentRef" => [
                        "value" => $referenceId
                    ],
                    "MetaData" => [
                        "CreateTime" => $createdDate,
                    ],
                    "GivenName" => substr($customer->getFirstName(), 0, 25), // maximum of 25 char
                    "FamilyName" => substr($customer->last_name, 0, 25),
                    "CompanyName" => substr($customer->getCompanyName(), 0, 25),
                    "BillAddr" => [
                        "Line1" => $billingAddress->address,
                        "Line2" => $billingAddress->address_line_1,
                        "City" => $billingAddress->city ? $billingAddress->city : '',
                        "Country" => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
                        "CountrySubDivisionCode" => isset($billingAddress->country->code) ? $billingAddress->country->code : '',
                        "PostalCode" => $billingAddress->zip
                    ]
                ];

                $data = array_filter($data);

                if (ine($quickbookJob, 'Id')) {
                    $data['Id'] = $quickbookJob['Id'];
                    $data['SyncToken'] = $quickbookJob['SyncToken'];
                }

                $jobsData[$key]['Customer'] = $data;
                $jobsData[$key]['bId'] = $job->id;
                $jobsData[$key]['operation'] = 'create';
            }
            $batchData['BatchItemRequest'] = $jobsData;
            try {
                $response = $service->batchRequest($token, $batchData);
                if (($response) && !empty($response['BatchItemResponse'])) {
                    foreach ($response['BatchItemResponse'] as $key => $value) {
                        if (!isset($value['Customer']['Id'])) {
                            Log::info('Mulit job');
                            Log::info($value);
                            continue;
                        }

                        $job = Job::find($value['bId']);
                        $job->update([
                            'quickbook_id' => $value['Customer']['Id'],
                            'quickbook_sync' => true
                        ]);
                        Log::info('Mulit Job Id ' . $job->id . ' synced successfully');
                    }
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 429) {
                    Log::info('time limit exceed');
                    // continue;
                }
                throw $e;
            }
        });
        }

        $jobIds = Job::whereIn('id', (array)$jobIds)
            ->whereNotNull('quickbook_id')
            ->whereQuickbookSync(false)
            ->whereMultiJob(true)
            ->pluck('id')->toArray();

        if (!empty($jobIds)) {
            goto batch;
        }
    }

    /**
     * Jobs Sync
     * @param  Object $token Token
     * @param  Array $jobIds Array of jobids
     * @return Response
     */
    private function jobsSync($token, $jobIds, $timezone)
    {
        $jobIds = Job::whereIn('id', (array)$jobIds)
            ->whereQuickbookSync(false)
            ->whereMultiJob(false)
            ->whereNotNull('quickbook_id')
            ->pluck('id')->toArray();

        if (empty($jobIds)) {
            return true;
        }
        $service = App::make(\App\Services\QuickBooks\QuickBookService::class);
        goto batch;

        batch:{

        $query = Job::whereIn('id', (array)$jobIds)
            ->whereQuickbookSync(false)
            ->whereMultiJob(false)
            ->whereNotNull('quickbook_id');

        $query->chunk(20, function ($jobs) use ($token, $service, $timezone) {
            $jobsData = [];
            foreach ($jobs as $key => $job) {
                $customer = $job->customer;
                $billingAddress = $customer->billing;
                $customer = $job->customer;
                $referenceId = $customer->quickbook_id;

                if ($job->isProject()) {
                    $parent = $job->parentJob;
                    $referenceId = $parent->quickbook_id;
                }

                $reference = $service->getQuickbookCustomer($token, $referenceId);

                if (!ine($reference, 'Id') && !$job->isProject()) {
                    $customer = $service->createOrUpdateCustomer($token, $customer);
                    $referenceId = $customer->quickbook_id;
                }

                if (!ine($reference, 'Id') && $job->isProject()) {
                    $referenceId = $service->getJobQuickbookId($token, $parent);
                }
                $dateTime = convertTimezone($job->created_at, $timezone);
                $createdDate = $dateTime->format('Y-m-d');
                $displayName = $job->getQuickbookDisplayName();
                $quickbookJob = $service->getQuickbookCustomer($token, $job->quickbook_id);

                // map payment data for batch request
                $data = [
                    "Job" => true,
                    "DisplayName" => $displayName,
                    "BillWithParent" => true,
                    "ParentRef" => [
                        "value" => $referenceId
                    ],
                    "MetaData" => [
                        "CreateTime" => $createdDate,
                    ],
                    "BillAddr" => [
                        "Line1" => $billingAddress->address,
                        "Line2" => $billingAddress->address_line_1,
                        "City" => $billingAddress->city ? $billingAddress->city : '',
                        "Country" => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
                        "CountrySubDivisionCode" => isset($billingAddress->country->code) ? $billingAddress->country->code : '',
                        "PostalCode" => $billingAddress->zip
                    ],
                    "GivenName" => substr($customer->getFirstName(), 0, 25), // maximum of 25 char
                    "FamilyName" => substr($customer->last_name, 0, 25),
                    "CompanyName" => substr($customer->getCompanyName(), 0, 25)
                ];

                $data = array_filter($data);

                if (ine($quickbookJob, 'Id')) {
                    $data['Id'] = $quickbookJob['Id'];
                    $data['SyncToken'] = $quickbookJob['SyncToken'];
                }

                $jobsData[$key]['Customer'] = $data;
                $jobsData[$key]['bId'] = $job->id;
                $jobsData[$key]['operation'] = 'create';
                Log::info('job id on push on batch request. ' . $job->id);
            }

            $batchData['BatchItemRequest'] = $jobsData;
            try {
                $response = $service->batchRequest($token, $batchData);
                if (($response) && !empty($response['BatchItemResponse'])) {
                    foreach ($response['BatchItemResponse'] as $key => $value) {
                        if (!isset($value['Customer']['Id'])) {
                            Log::info('Simple Job');
                            Log::info($value);
                            continue;
                        }

                        $job = Job::find($value['bId']);
                        $job->update([
                            'quickbook_id' => $value['Customer']['Id'],
                            'quickbook_sync' => true
                        ]);

                        Log::info('Simple Job Id ' . $job->id . ' Synced');
                    }
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 429) {
                    Log::info('time limit exceed');
                    // continue;
                }
            }
        });
        }

        $jobIds = Job::whereIn('id', (array)$jobIds)
            ->whereNotNull('quickbook_id')
            ->whereQuickbookSync(false)
            ->whereMultiJob(false)
            ->pluck('id')->toArray();

        if (!empty($jobIds)) {
            goto batch;
        }
    }
}
