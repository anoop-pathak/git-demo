<?php

namespace App\Services\QuickBooks;

use App\Models\Customer;
use App\Models\State;
use App\Models\TempImportCustomer;
use App\Repositories\CustomerRepository;
use App\Repositories\QuickBookRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use App\Services\QuickBooks\Facades\QuickBooks;
use Exception;

class CustomerImport
{
    public function __construct(QuickBookRepository $repo, Client $client, CustomerRepository $customerRepo)
    {
        $this->repo = $repo;
        $this->client = $client;
        $this->customerRepo = $customerRepo;
    }

    /**
     * Customer Import
     * @return int
     */
    public function import()
    {
        $token = $this->repo->getToken();
		if(!$token) {
			return false;
		}
		$totalImportCustomer = 0;

		try {

			$customerQuickbookIds = $this->customerRepo->getQuickbookCustomerList();

			$startPosition = 1;
			do {

				$query = "SELECT *  FROM Customer WHERE Job = FALSE STARTPOSITION {$startPosition} MAXRESULTS 1000";
				$response = QuickBooks::getDataByQuery($query);

				// $response = $this->client->request("/query", 'GET', [
				// 	'query' => "SELECT *  FROM Customer WHERE Job = FALSE STARTPOSITION {$startPosition} MAXRESULTS 1000"
				// ]);

				$startPosition += 1000;
				$data = [];

				if(!empty($response) ) {

					foreach ($response as $customer) {
						$customer = QuickBooks::toArray($customer);

						if(in_array($customer['Id'], (array)$customerQuickbookIds)) continue;

						$data[] = $this->extractCustomerData($customer);

						$totalImportCustomer++;
					}
				}

				if(!empty($data)) {
					TempImportCustomer::insert($data);
				}

			} while (!empty($response));

			return $totalImportCustomer;
		} catch(Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
    }

    /****************** PRIVATE METHOD **********************/

    private function extractCustomerData($data)
    {

        $tempCustomerData = [];

        $tempCustomerData['duplicate'] = false;
        $tempCustomerData['is_valid'] = true;
        $tempCustomerData['errors'] = null;

        $customerData = $this->mapCustomerInput($data);
        $customerData['address'] = $this->mapAddressInput($data);
        $customerData['billing'] = $this->mapBillingAddressInput($data);
        $customerData['phones'] = $this->mapPhonesInput($data);
        $validate = Validator::make($customerData, Customer::validationRules());

        if ($validate->fails()) {
            $tempCustomerData['errors'] = json_encode($validate->messages()->toArray());
            $tempCustomerData['is_valid'] = false;
        }

        if ($tempCustomerData['is_valid'] && $this->isDuplicate($customerData)) {
            $tempCustomerData['duplicate'] = true;
        }

        $timestamp = \Carbon\Carbon::now();

        $tempCustomerData['data'] = json_encode($customerData);
        $tempCustomerData['company_id'] = getScopeId();
        $tempCustomerData['quickbook'] = true;
        $tempCustomerData['created_at'] = $timestamp;
        $tempCustomerData['updated_at'] = $timestamp;

        return $tempCustomerData;
    }

    private function mapCustomerInput($input = [])
    {
        $map = [
            'quickbook_id' => 'Id',
            'first_name' => 'GivenName',
            'last_name' => 'FamilyName',
            'company_name' => 'CompanyName',
            'quickbook_id' => 'Id',
            'note' => 'Notes'
        ];

        $customerInput = $this->mapInputs($map, $input);
        $customerInput['email'] = null;
        if (isset($input['PrimaryEmailAddr']['Address'])) {
            $customerInput['email'] = $input['PrimaryEmailAddr']['Address'];
        }
        return $customerInput;
    }


    /**
     *  map customer locations input data.
     */
    private function mapAddressInput($input = [])
    {
        if (!isset($input['ShipAddr']) && !isset($input['BillAddr'])) {
            return false;
        }

        if (!isset($input['ShipAddr'])) {
            $shipingAddress = $input['BillAddr'];
        } else {
            $shipingAddress = $input['ShipAddr'];
        }
        $addressFields = [
            'address' => 'Line1',
            'city' => 'City',
            'state' => 'CountrySubDivisionCode',
            'country' => 'Country',
            'zip' => 'PostalCode'
        ];

        $billing = $this->mapInputs($addressFields, $shipingAddress);
        $billing = $this->mapStateAndCountry($billing);
        return $billing;
    }

    private function mapBillingAddressInput($input = [])
    {
        if (!isset($input['BillAddr'])) {
            return false;
        }
        $billingAddress = $input['BillAddr'];
        $addressFields = [
            'address' => 'Line1',
            'city' => 'City',
            'state' => 'CountrySubDivisionCode',
            'country' => 'Country',
            'zip' => 'PostalCode'
        ];

        $billing = $this->mapInputs($addressFields, $billingAddress);
        $billing = $this->mapStateAndCountry($billing);
        $billing['same_as_customer_address'] = 0;
        return $billing;
    }

    private function mapPhonesInput($input = [])
    {
        $phones = [];
        $key = 0;

        if (isset($input['PrimaryPhone']['FreeFormNumber'])) {
            $number = preg_replace('/\D+/', '', $input['PrimaryPhone']['FreeFormNumber']);
            if (strlen($number) == 10) {
                $phones[$key]['label'] = 'phone';
                $phones[$key]['number'] = $number;
                $key++;
            }
        }

        if (isset($input['Mobile']['FreeFormNumber'])) {
            $number = preg_replace('/\D+/', '', $input['Mobile']['FreeFormNumber']);
            if (strlen($number) == 10) {
                $phones[$key]['label'] = 'phone';
                $phones[$key]['number'] = $number;
                $key++;
            }
        }

        if (isset($input['Fax']['FreeFormNumber'])) {
            $number = preg_replace('/\D+/', '', $input['Fax']['FreeFormNumber']);
            if (strlen($number) == 10) {
                $phones[$key]['label'] = 'fax';
                $phones[$key]['number'] = $number;
                $key++;
            }
        }

        return $phones;
    }

    private function mapInputs($map, $input = [])
    {
        $ret = [];

        // empty the set default.
        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? trim($input[$value]) : "";
            } else {
                $ret[$key] = isset($input[$value]) ? trim($input[$value]) : "";
            }
        }

        return $ret;
    }

    private function mapStateAndCountry($data = [])
    {
        if (!ine($data, 'state')) {
            $data;
        }

        try {
            $state = State::nameOrCode($data['state'])->first();
            $data['state_id'] = $state->id;
            $data['country_id'] = $state->country_id;
            $data['country'] = $state->country->name;
            return $data;
        } catch (\Exception $e) {
            return $data;
        }
    }

    private function isDuplicate($customer)
    {
        $customerRepo = App::make(\App\Repositories\CustomerRepository::class);

        $duplicate = $customerRepo->isDuplicateRecord(
            $customer,
            $customer['phones']
        );

        return (bool)$duplicate;
    }
}
