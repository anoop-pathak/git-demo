<?php

namespace App\Services\AmericanFoundation\Commands;

use App\Services\AmericanFoundation\Models\AfCustomer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Models\State;

class MoveAfCustomerToJpCustomerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:move_af_customers_to_jp_customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move American Foundation Customers to JobProgress Customers Table.';

    private $inc = 0;

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
        $this->info('Script Start.');
        AfCustomer::with(['company', 'afCustomerRep'])
            ->whereNotNull('first_name')->whereNotNull('last_name')
            ->chunk(100, function($customers){

            foreach ($customers as $customer) {
                if($customer->customer_id) {
                    continue;
                }

                try {
                    setAuthAndScope(config('jp.american_foundation_system_user_id'));
                    setScopeId($customer->company_id);

                    $customerArr = $this->customerFieldsMapping($customer);
                    $service = App::make('App\Services\AmericanFoundation\Services\AfCustomerService');
                    $savedCustomer = $service->createJpCustomer($customerArr);
                    $customer->customer_id = $savedCustomer->id;
                    $customer->save();
                    $this->inc++;
                    if($this->inc %10 == 0) {
                        $this->info("Total Processing customer:- " . $this->inc);
                    }

                } catch (\Exception $e) {
                    Log::error("Error in American Foundation Move AfCustomers to customers table");
                    Log::error($e);
                }
            }
        });
    }

    private function customerFieldsMapping(AfCustomer $customer)
    {
        // Get State
        $customerStateId = State::nameOrCode($customer->customer_state)->first();
        $billingStateId  = State::nameOrCode($customer->customer_state)->first();
        $repId = null;
        if($customer->afCustomerRep) {
            $repId = $customer->afCustomerRep->user_id;
        }

        $payload = [
            'first_name'   => $customer->first_name,
            'last_name'    => $customer->last_name,
            'email'        => $customer->email,
            'company_id'   => $customer->company_id,
            'company_name' => $customer->company_name,
            'note'         => $customer->note,
            'rep_id'       => $repId,
            'address' => [
                'address'    => $customer->customer_address,
                'city'       => $customer->customer_city,
                'state_id'   => $customerStateId ? $customerStateId->id : null,
                'country_id' => 0,
                'zip'        => $customer->customer_zip,
            ],
            'billing' => $this->getBillingAddress($customer, $billingStateId),
            'customer_contacts' => [
                [
                    'first_name' => $customer->secondary_first_name,
                    'last_name'  => $customer->secondary_last_name
                ]
            ],
            'phones'  => $this->getPhoneNumber($customer),
        ];
        return $payload;
    }

    private function getBillingAddress($customer, $billingStateId)
    {
        return [
            'address'    => $customer->billing_address,
            'city'       => $customer->billing_city,
            'state_id'   => $billingStateId ? $billingStateId->id : null,
            'country_id' => 0,
            'zip'        => $customer->customer_zip,
            'same_as_customer_address' => 0
        ];
    }

    private function getPhoneNumber($customer)
    {
        $options = json_decode($customer->options, true);

        $customerPhone = $options['i360_phone_1_c'] ?: '0000000000';

        $customerPhone = str_replace('(', '', $customerPhone);
        $customerPhone = str_replace(')', '', $customerPhone);
        $customerPhone = str_replace(' ', '', $customerPhone);
        $customerPhone = str_replace('-', '', $customerPhone);
        $phone[] = [
            'label'  => 'home',
            'number' => $customerPhone,
        ];

        return $phone;
    }
}