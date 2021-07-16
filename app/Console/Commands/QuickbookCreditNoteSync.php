<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use App\Models\JobCredit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Settings;

class QuickbookCreditNoteSync extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:quickbook_credit_note_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickbook Credit Note Sync';

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
                $context->set(Company::find($company->id));
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

                $this->creditNoteSync($token, $customerIds, $company);
            }
        } catch (\Exception $e) {
            $msg = 'Company Id ' . $companyId . ' ';
            $msg .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            echo 'Quickbook credit note Sync Command: ' . $msg;
        }
    }

    /**
     * Credit Note Syncs
     * @param  Object $token Token
     * @param  Array $customerIds Array of customer Ids
     * @param  Instance $company Instanse of Company
     * @return Boolean
     */
    private function creditNoteSync($token, $customerIds, $company)
    {
        $jobIds = Job::whereIn('customer_id', $customerIds)
            ->pluck('id')->toArray();

        if (empty($jobIds)) {
            return true;
        }
        $meta = $company->quickbook->meta->pluck('meta_value', 'meta_key')->toArray();
        $service = App::make(\App\Services\QuickBooks\QuickBookService::class);
        goto batch;

        batch:{

        $query = JobCredit::whereIn('job_id', (array)$jobIds)
            ->whereQuickbookSync(false)
            ->with('job')
            ->whereNull('canceled');

        $query->chunk(30, function ($jobCredits) use ($service, $token, $meta) {
            $jobsData = [];
            foreach ($jobCredits as $key => $jobCredit) {
                $data = $service->mapCreditNoteData(
                    $token,
                    $jobCredit,
                    $meta['Services'],
                    'Services'
                );
                $jobsData[$key]['CreditMemo'] = $data;
                $jobsData[$key]['bId'] = $jobCredit->id;
                $jobsData[$key]['operation'] = 'create';
            }

            $batchData['BatchItemRequest'] = $jobsData;

            try {
                $response = $service->batchRequest($token, $batchData);
                if (($response) && !empty($response['BatchItemResponse'])) {
                    foreach ($response['BatchItemResponse'] as $key => $value) {
                        if (!isset($value['CreditMemo']['Id'])) {
                            $jobCredit = JobCredit::find($value['bId']);

                            $jobCredit->update([
                                'quickbook_id' => null,
                                'quickbook_sync' => true
                            ]);
                            Log::info('credit Note');
                            Log::info($value);
                            continue;
                        }

                        $jobCredit = JobCredit::find($value['bId']);

                        $jobCredit->update([
                            'quickbook_id' => $value['CreditMemo']['Id'],
                            'quickbook_sync' => true
                        ]);
                    }
                }
            } catch (\Exception $e) {
                if ($e->getCode() != 429) {
                    throw $e;
                }
            }
        });
        }

        $jobIds = JobCredit::whereIn('job_id', (array)$jobIds)
            ->whereQuickbookSync(false)
            ->whereNull('canceled')
            ->pluck('job_id')->toArray();

        if (!empty($jobIds)) {
            goto batch;
        }

        return true;
    }
}
