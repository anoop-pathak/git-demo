<?php

namespace App\Transformers;

use App\Models\Customer;
use App\Models\Job;
use League\Fractal\TransformerAbstract;

class CustomerJobSearchTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */

    public function transform($customer)
    {
        $lastName = issetRetrun($customer, 'last_name') ?: '';
        $jobAddress = $this->getJobAddress($customer);
        $customerAddress = $this->getCustomerAddress($customer);

        $data = [
            'id' => $customer['customer_id'],
            'company_id' => $customer['company_id'],
            'first_name' => $customer['first_name'],
            'last_name' => $lastName,
            'is_commercial' => (int)$customer['is_commercial'],
            'company_name' => issetRetrun($customer, 'company_name') ?: '',
            'job_id' => issetRetrun($customer, 'job_id') ?: '',
            'job_name' => issetRetrun($customer, 'job_name') ?: '',
            'number' => issetRetrun($customer, 'number') ?: '',
            'alt_id' => issetRetrun($customer, 'alt_id') ?: '',
            'division_code' => issetRetrun($customer, 'division_code') ?: '',
            'phones' => $this->getPhones($customer),
            'parent_id' => issetRetrun($customer, 'parent_id') ?: '',
            'multi_job' => (int)issetRetrun($customer, 'multi_job'),
            'customer_contact' => $this->getCustomerContact($customer),
            'trades' => issetRetrun($customer, 'trades') ?: [],
            'job_resource_id' => issetRetrun($customer, 'job_resource_id') ?: '',
            'current_stage' => [],
            'full_name' => $customer['first_name'] . ' ' . $lastName,
            'full_name_mobile' => $customer['first_name'] . ' ' . $lastName,
            'job_address' => $jobAddress,
            'customer_address' => $customerAddress,
            'full_customer_address' => implode(', ', array_filter($customerAddress)),
            'full_job_address' => implode(', ', array_filter($jobAddress)),
            'other_trade_type_description' => issetRetrun($customer, 'other_trade_type_description') ?: '',
            'score' => $customer['score'],
            'division'                     => issetRetrun($customer, 'division') ?: '',
            'division_id'                  => (int)issetRetrun($customer, 'division_id') ?: 0,
            'lost_job'                 => ine($customer, 'lost_date'),
            'archived'                 => ine($customer, 'archived'),
        ];

        if (ine($customer, 'job_id')) {
            $data['current_stage'] = $this->getCurrentStage($customer);
        }

        return $data;
    }

    /*************************** Private Function *****************************/

    /**
     * Get Phones
     * @param  [type] $customer [description]
     * @return [type]           [description]
     */
    private function getPhones($customer)
    {
        $phones = [];
        $array = ['label', 'number', 'ext'];

        // if phones not set
        if (!ine($customer, 'phones')) {
            return array_fill_keys($array, '');
        }

        foreach ($customer['phones'] as $phone) {
            $phoneValues = explode('_SEP_', $phone);
            $phones[] = array_combine($array, $phoneValues);
        }

        return $phones;
    }

    /**
     * Get customer contact name
     * @param  Array $customer customer data
     * @return Customer Contact
     */
    private function getCustomerContact($customer)
    {
        $customerContact = [];
        if (ine($customer, 'customer_contact_first_name') && ine($customer, 'customer_contact_last_name')) {
            for ($i = 0; $i < count($customer['customer_contact_first_name']); $i++) {
                $customerContact[$i]['customer_contact_first_name'] = $customer['customer_contact_first_name'] [$i];
                $customerContact[$i]['customer_contact_last_name'] = $customer['customer_contact_last_name'] [$i];
            }
        }

        return $customerContact;
    }

    /**
     * Get Current Stage
     * @param  Array $customer customer data
     * @return job data
     */

    private function getCurrentStage($customer)
    {

        if(!isset($customer['current_stage_code'])) {
            $job = Job::find($customer['job_id']);
            return $job->getCurrentStage();
        }

        return [
            'name' => $customer['current_stage_name'],
            'color' => $customer['current_stage_color'],
            'code' => $customer['current_stage_code'],
            'resource_id' => $customer['current_stage_resource_id']
        ];
    }

    /**
     * Get Customer Address
     * @param  ARRAY $customer customer data
     * @return customer address
     */
    private function getCustomerAddress($customer)
    {
        return [
            'address' => issetRetrun($customer, 'customer_address') ?: '',
            'address_line_1' => issetRetrun($customer, 'customer_address_line_1') ?: '',
            'city' => issetRetrun($customer, 'customer_city') ?: '',
            'state' => issetRetrun($customer, 'customer_state') ?: '',
            'zip' => issetRetrun($customer, 'customer_zip') ?: '',
        ];
    }

    /**
     * Get Job Address
     * @param  ARRAY $customer customer data
     * @return job address
     */
    private function getJobAddress($customer)
    {
        return [
            'address' => issetRetrun($customer, 'job_address') ?: '',
            'address_line_1' => issetRetrun($customer, 'job_address_line_1') ?: '',
            'city' => issetRetrun($customer, 'job_city') ?: '',
            'state' => issetRetrun($customer, 'job_state') ?: '',
            'zip' => issetRetrun($customer, 'job_zip') ?: '',
        ];
    }
}
