<?php

namespace App\Transformers;

use Carbon\Carbon;
use League\Fractal\TransformerAbstract;
use Settings;

class JobsExportTransformer extends TransformerAbstract
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
    public function transform($job)
    {
        $customer = $job->customer;
        $address = $customer->address;
        $division = $job->division;
        $financialCalculation = $job->financialCalculation;
        $jobAddr  = $job->address;

        $jobNumber = $job->number;
        $jobContactFullName =  $this->jobContactName($job);
        $jobContactEmail = ($job->primaryContact()) ? $job->primaryContact()->primaryEmail() : null;
        $jobContactFullAddress = $this->jobContactFullAddress($job);
        $jobContactPhoneNumber = $this->jobContactPhoneNumber($job);
        $referral = ($customer->referredByReferral) ? $customer->referredByReferral->name : null;

        if ($job->isMultiJob()) {
            $jobNumber .= ' (Multi Project)';
        }

        if($job->contact_same_as_customer) {
            $jobContactFullName = $customer->first_name.' '.$customer->last_name;
            $jobContactEmail = $customer->email;
            $jobContactPhoneNumber = $customer->phones[0]['number'];
            $jobContactFullAddress = ($address) ? $address->address : null;
         }

        $referredBy = $this->getCustomerRefferedBy($customer);

         $data =  [ 
            'Job Id'           => $jobNumber,
            'Job #'            => excelExportConvertValueToString($job->full_alt_id),
            'Job Name'         => excelExportConvertValueToString($job->name),
            'Referral Type'    => excelExportConvertValueToString($customer->referred_by_type),
            'Referred By'      => $referredBy,
            'Last Name'        => excelExportConvertValueToString($customer->last_name),
            'First Name'       => excelExportConvertValueToString($customer->first_name),
            'Email Address'    => excelExportConvertValueToString($customer->email),
            'Job description'  => excelExportConvertValueToString($job->description),
            'Customer Address' => ($address) ? $address->address : null,
            'City'             => ($address) ? $address->city : null,
            'State'            =>  isset($address->state->code) ? $address->state->code : null,
            'Zip'              =>  ($address) ? $address->zip : null,
             'Customer Note'   => excelExportConvertValueToString($customer->note),
            'Job Contact Full Name' => excelExportConvertValueToString($jobContactFullName),
            'Job Contact Email'     => excelExportConvertValueToString($jobContactEmail),
            'Job Contact Phone Number'  => $jobContactPhoneNumber,
            'Job Contact Full Address'  =>  $jobContactFullAddress,
            'Job Address'      => ($jobAddr) ? $jobAddr->address : null,
            'Job City'         => ($jobAddr) ? $jobAddr->city : null,
            'Job State'        =>  isset($jobAddr->state->code) ? $jobAddr->state->code : null,
            'Job Zip'          =>  ($jobAddr) ? $jobAddr->zip : null,
            'Salesman / Customer Rep' => excelExportConvertValueToString($customer->present()->salesman),
            'Current Stage'    =>  $this->getCurrentStageName($job),
            'Job Division'     => ($division) ? $division->name : null,
            'Trade Type'       => implode(', ', $job->trades->lists('name')),
            'Work Type'        =>  implode(', ', $job->workTypes->lists('name')),
            'Category'         => implode(', ', $job->jobTypes->lists('name')),
            'Job Record Since' => Carbon::parse($job->created_date, \Settings::get('TIME_ZONE'))->toDateString(),
            'Last Modified'    => Carbon::parse($job->updated_at, \Settings::get('TIME_ZONE'))->toDateString(),
            'Job Awarded Date' => $job->getSoldOutDate(),
            'Contract Signed Date' => $job->getContractSignedDate(),
            'Completed Date'   => $job->getCompletedDate(),
            'Job Estimator'    => excelExportConvertValueToString($job->present()->jobEstimators),
            'Work Crew'        => excelExportConvertValueToString($job->present()->jobRepLaborSubAll),
            'Total Job Price'  => showAmount($financialCalculation->total_job_amount),
            'Change Orders'     => showAmount($financialCalculation->total_change_order_amount),
            'Total Amount'     => showAmount($financialCalculation->total_amount),
            'Credits'   => showAmount($financialCalculation->total_credits),
            'Total Refunds'   => showAmount($financialCalculation->total_refunds),
            'Payment Received' => showAmount($financialCalculation->total_received_payemnt),
            'Amount Owed'      => showAmount($financialCalculation->pending_payment),
        ];

        //Creating phone label heading and assign numbers
        $data = $this->transformPhoneNumbers($customer, $data);

        return $data;
    }

    /**
     * Get Current Stage Name
     * @param  Instance $job Job
     * @return Response
     */
    private function getCurrentStageName($job)
    {
        $stage = $job->getCurrentStage();

        return $stage['name'];
    }

    /**
     * Transform Phone Numbers
     * @param  Instance $customer Customer
     * @param  Array $data Data
     * @return Response
     */
    private function transformPhoneNumbers($customer, $data)
    {

        $phones = $customer->present()->phoneNumbers;

        $offset = array_search('First Name', array_keys($data));
        // set phone no into customer_detail
        $customer = array_merge(
            array_slice($data, 0, $offset + 1),
            $phones,
            array_slice($data, $offset, null)
        );

        return $customer;
    }

      /**
     * Transform Job Contact Full Name
     * @param  Instance $job job
     * @return Response
     */
    private function jobContactName($job)
    {
        $jobContact = $job->primaryContact();
        if(!$jobContact) {

            return false;
        }
        $jobContactFullName = $jobContact->fullname;

        return $jobContactFullName;
    }

     /**
     * Transform Job Contact Full Name
     * @param  Instance $job job
     * @return Response
     */
    private function jobContactFullAddress($job)
    {
        $jobContact = $job->primaryContact();

        if(!$jobContact) {

            return false;
        }

        $contactFullAddress = $jobContact->present()->jobContactFullAddress;

        return $contactFullAddress;
    }

     /**
     * Transform Job Conact Phone Numbers
     * @param  Instance $job
     * @return Response
     */
    private function jobContactPhoneNumber($job)
    {
        $jobContact = $job->primaryContact();
        if(!$jobContact) {
            return false;
        }

        $phones = $jobContact->phones->pluck('number')->toArray();
        $phoneNumbers = implode(' , ', $phones);

        return $phoneNumbers;
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
