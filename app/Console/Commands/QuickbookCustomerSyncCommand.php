<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\QuickBooks\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QuickbookCustomerSyncCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:quickbook-customer-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickbook Customer Sync';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->client = new Client();
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

        $companyId = null;

        try {
            foreach ($companies as $company) {
                $companyId = $company->id;
                $quickbook = $company->quickbook;
                $customers = $company->customers;
                $this->client->setToken($quickbook);
                $this->client->setQuickbookCompanyId($quickbook->quickbook_id);
                $this->customersSync($customers);
            }
        } catch (\Exception $e) {
            $msg = 'Company Id ' . $companyId . ' ';
            $msg .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info('Quickbook Customer Sync Command: ' . $msg);
        }
    }


    public function customersSync($customers)
    {
        if (!$customers->count()) {
            return false;
        }

        foreach ($customers as $customer) {
            $this->syncData($customer);
        }
    }

    private function syncData($customer)
    {
        try {
            if (!$customer->quickbook_id) {
                return false;
            }

            $data = $this->mapCustomer($customer);
            $this->client->request("/customer", 'POST', $data);
        } catch (\Exception $e) {
            $msg = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info('Quickbook Customer Id: ' . $customer->id . ' ' . $msg);
        }
    }

    public function mapCustomer($customer)
    {
        $customerQuickbookId = $customer->quickbook_id;

        $syncToken = $this->getCustomerSyncToken($customerQuickbookId);

        $displayName = $customer->first_name . ' ' . $customer->last_name . ' ' . '(' . $customer->id . ')';

        $firstName = $customer->first_name;
        $lastName = $customer->last_name;
        $companyName = $customer->company_name;

        if ($customer->is_commercial) {
            $firstName = '';
            $lastName = '';
            $companyName = $customer->first_name;
            $displayName = $companyName . ' ' . '(' . $customer->id . ')';
        }

        $billingAddress = $customer->billing;
        $customerEntity = [
            "BillAddr" => [
                "Line1" => $billingAddress->address,
                "Line2" => $billingAddress->address_line_1,
                "City" => $billingAddress->city ? $billingAddress->city : '',
                "Country" => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
                "CountrySubDivisionCode" => isset($billingAddress->country->code) ? $billingAddress->country->code : '',
                "PostalCode" => $billingAddress->zip
            ],
            "Notes" => $customer->note,
            "GivenName" => substr($firstName, 0, 25), // maximum of 25 char
            "FamilyName" => substr($lastName, 0, 25),
            "CompanyName" => substr($companyName, 0, 50),
            "DisplayName" => substr($displayName, 0, 100),
            "PrimaryPhone" => [
                "FreeFormNumber" => preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '($1) $2-$3', $customer->phones[0]->number)
            ],
            "PrimaryEmailAddr" => [
                "Address" => $customer->email
            ]
        ];

        $customerEntity = array_filter($customerEntity);

        $customerEntity['Id'] = $customerQuickbookId;
        $customerEntity['SyncToken'] = $syncToken;

        return $customerEntity;
    }

    private function getCustomerSyncToken($quickbookId)
    {
        $param = [
            'query' => "SELECT *  FROM  Customer WHERE Id = '" . $quickbookId . "'"
        ];

        $item = $this->client->request("/query", 'GET', $param);
        if (empty($item['QueryResponse'])) {
            return 0;
        }

        return $item['QueryResponse']['Customer'][0]['SyncToken'];
    }
}
