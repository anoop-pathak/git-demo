<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\QuickbookMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class AddQuickbookCompanyMeta extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_quickbook_company_meta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add quickbook company meta';

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
        $companies = Company::has('quickbook')->get();

        if (!$companies->count()) {
            return false;
        }

        $companyId = null;

        $this->quickbookService = App::make(\App\Services\QuickBooks\QuickBookService::class);

        try {
            foreach ($companies as $company) {
                $context = App::make(\App\Services\Contexts\Context::class);
                $context->set(Company::find($company->id));
                $quickbook = $company->quickbook;
                $token = $this->quickbookService->getToken();
                QuickbookMeta::whereQuickbookId($quickbook->id)->delete();
                $this->quickbookService->findOrCreateItem($token);
                $paymentMethods = ['Other', 'Cash', 'Credit Card', 'Paypal', 'Check', 'Venmo', 'Zelle', 'Digital Cash App', 'ACH/Online Payment'];
                foreach ($paymentMethods as $method) {
                    $this->quickbookService->getPaymentReference($token, $method);
                }
            }
        } catch (\Exception $e) {
            $msg = 'Company Id ' . $companyId . ' ';
            $msg .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info('Quickbook Customer Sync Command: ' . $msg);
        }
    }
}
