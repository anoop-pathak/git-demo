<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Phone;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class CustomerImportCSV extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:customer_import_csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Customer import csv.';

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
        $this->companyId = 284; //Christian Roofing

        $this->attachPhones();
        $this->attachAddresses();
        $this->info('Customer solr sync' . PHP_EOL);
        $this->call('command:solr_customer_sync');
    }

    public function attachPhones()
    {
        $customerIds = [];
        $filename = storage_path() . '/data/customer_contact_phone_csv.csv';
        $excel = App::make('excel');
        $import = $excel->load($filename);
        $records = $import->get();

        $totalPhones = count($records);
        $this->info('Total Customer Phones: ' . $totalPhones);

        foreach ($records as $record) {
            $this->info('Pending Customer Phones: ' . $totalPhones--);

            if (strlen($record->number) != 10) {
                continue;
            }

            $customer = Customer::where('first_name', (string)$record->first_name)
                ->where('last_name', (string)$record->last_name)
                ->where('company_id', $this->companyId)
                ->where('email', (string)$record->email)
                ->first();

            if (!$customer) {
                continue;
            }


            $customerIds[] = $customer->id;
            $phone = Phone::firstOrNew(['customer_id' => $customer->id, 'number' => $record->number]);
            $phone->label = 'home';
            $phone->ext = $record->ext;
            $phone->save();
        }

        Phone::whereIn('customer_id', array_unique($customerIds))
            ->where('number', '0000000000')
            ->delete();
    }

    public function attachAddresses()
    {
        $states = State::where('country_id', 1)->pluck('id', 'code')->toArray();
        $filename = storage_path() . '/data/customer_contact_address_csv.csv';

        $excel = App::make('excel');
        $import = $excel->load($filename);
        $records = $import->get();
        $totalMatchRecords = 0;
        $totalRecords = count($records);

        $this->info('Total Customers Address: ' . $totalRecords);
        foreach ($records as $record) {
            $this->info('Pending Customers Address: ' . $totalRecords--);

            $customer = Customer::where('first_name', (string)$record->first_name)
                ->where('last_name', (string)$record->last_name)
                ->where('company_id', $this->companyId)
                ->where('email', (string)$record->email)
                ->first();

            if (!$customer) {
                continue;
            }

            if ($customerAddress = $customer->address) {
                $customerAddress->address_line_1 = trim($record->address_line_1);
                $customerAddress->city = trim($record->city);
                $customerAddress->lat = ($record->lat) ?: null;
                $customerAddress->long = ($record->long) ?: null;
                $customerAddress->zip = trim($record->zip);
                $customerAddress->country_id = 1;

                if (array_key_exists($record->state_code, $states)) {
                    $customerAddress->state_id = $states[trim($record->state_code)];
                }

                $address = $record->address;
                if (!$customerAddress->state_id && $record->state_code) {
                    $address .= ' ' . trim($record->state_code);
                }

                $customerAddress->address = trim($address);

                $customerAddress->save();
            }
        }
    }
}
