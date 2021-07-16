<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
class SubContractorJobSearchTransformer extends TransformerAbstrac
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
     public function transform($job) {
        $customer = $job->customer;
        $customerAddress = $customer->address;
        $jobAddress = $job->address;
        $contacts = $customer->contacts;
        $customerContact = [];

        foreach ($contacts as $contact) {
            $customerContact = [
                'customer_contact_first_name' => $contact->first_name,
                'customer_contact_last_name' => $contact->last_name,
            ];
        }

        $data = [
            'id'            => (string)$customer->id,
            'customer_id'   => $job->customer_id,
            'first_name'    => $customer->first_name,
            'last_name'     => $customer->last_name,
            'is_commercial' => $customer->is_commercial,
            'company_name'  => $customer->company_name,
            'job_id'        => $job->id,
            'number'        => $job->number,
            'alt_id'        => $job->alt_id,
            'job_name'      => $job->name,
            'phones'        => $customer->phones,
            'parent_id'     => ($job->parent_id) ?: "",
            'multi_job'     => $job->multi_job,
            'customer_contact' => $customerContact,
            'trades' => $job->trades->pluck('name')->toArray(),
            'job_resource_id'  => (int)$job->getResourceId(),
            'current_stage'    => $job->getCurrentStage(),
            'full_name'        => $customer->full_name,
            'full_name_mobile' => $customer->full_name_mobile,
            'job_address' => [
                'address' => (string)$jobAddress->address,
                'address_line_1' => (string)$jobAddress->address_line_1,
                'city'  => (string)$jobAddress->city,
                'state' => ($state = $jobAddress->state) ? (string)$state->name : "",
                'zip'   => (string)$jobAddress->zip_code
            ],
            'customer_address' => [
                'address' => (string)$customerAddress->address,
                'address_line_1' => (string)$customerAddress->address_line_1,
                'city'  => (string)$customerAddress->city,
                'state' => ($state = $customerAddress->state) ? (string)$state->name : "" ,
                'zip'   => (string)$customerAddress->zip_code
            ],
            'full_customer_address' => (string)$customerAddress->present()->address_one_line,
            'full_job_address'      => (string)$jobAddress->present()->address_one_line,
            'other_trade_type_description' => (string)$job->other_trade_type_description,
            'division_code'     => (string)$job->division_code,
            'score' => 15.4500,
            'lost_job' => $this->lostJob($job),
            'archived' => $this->archivedJob($job)
        ];
         return $data;
    }

    /**
     * Include lost job followup
     *
     * @return bool
     */
    public function lostJob($job) {
        $followUps = $job->currentFollowUpStatus()->first();
        if($followUps && $followUps->mark == 'lost_job') {
            return true;
        }
        return false;
    }

    /**
     * Include archive job
     *
     * @return bool
     */
    public function archivedJob($job) {
        $archivedJob = $job->archived;
        if($archivedJob) {
            return true;
        }
        return false;
    }
}