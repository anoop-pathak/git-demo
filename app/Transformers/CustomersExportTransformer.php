<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class CustomersExportTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($customer)
    {
        $firstName = $customer->first_name;
        $lastName = $customer->last_name;
        $companyName = $customer->company_name;

        if(ine($customer, 'is_commercial')) {
            $secondaryContact = $customer->secondaryNameContact;
            $firstName = ($secondaryContact) ? $secondaryContact->first_name : null;
            $lastName  = ($secondaryContact) ? $secondaryContact->last_name : null;
            $companyName = $customer->first_name;
        }
        $referredBy = $this->getCustomerRefferedBy($customer);

        $data = [
            'First Name'            =>  excelExportConvertValueToString($firstName),
            'Last Name'             =>  excelExportConvertValueToString($lastName),
            'Company Name'          =>  excelExportConvertValueToString($companyName),
            'E-mail'                =>  excelExportConvertValueToString($customer->email),
            'Mailing address street'=>  isset($customer->address->address) ? excelExportConvertValueToString($customer->address->address) : '',
            'Mailing address City'  =>  isset($customer->address->city) ? excelExportConvertValueToString($customer->address->city) : '',
            'Mailing address State' =>  isset($customer->address->state->name) ? $customer->address->state->name : '',
            'Mailing address Zip'   =>  isset($customer->address->zip) ? excelExportConvertValueToString($customer->address->zip) : '',
            'Billing address street'=>  isset($customer->billing->address) ? excelExportConvertValueToString($customer->billing->address) : '',
            'Billing address City'  =>  isset($customer->billing->city) ? excelExportConvertValueToString($customer->billing->city) : '',
            'Billing address State' =>  isset($customer->billing->state->name) ? $customer->billing->state->name : '',
            'Billing address Zip'   =>  isset($customer->billing->zip) ? excelExportConvertValueToString($customer->billing->zip) : '',
            'Canvasser'             =>  $customer->present()->showCanvessor,
            'Call Center Rep'       =>  $customer->present()->showCallCenterRep,
            'Referred By'           =>  $referredBy,
        ];
        $customer = $this->transformPhoneNumbers($customer, $data);
        return $customer;
    }

    private function transformPhoneNumbers($customer, $data)
    {
        $phone = [];
        $phone = $customer->present()->phoneNumbers;

        $offset = array_search('E-mail', array_keys($data));
        // set phone no into customer_detail
        $customer = array_merge(
            array_slice($data, 0, $offset + 1),
            $phone,
            array_slice($data, $offset, null)
        );
        return $customer;
    }

    private function getCustomerRefferedBy($customer)
    {
        $referredBy = null;

        if($customer->referred_by_type == 'customer' && $customer->referredByCustomer) {
            return $customer->referredByCustomer->full_name;
        }

        if ($customer->referred_by_type == 'referral' && $customer->referredByReferral) {
            return $customer->referredByReferral->name;
        }

        if($customer->referred_by_type == 'other'){
            return $customer->referred_by_note;
        }

        return $referredBy;
    }
}
