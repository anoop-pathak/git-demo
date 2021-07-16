<?php

namespace App\Transformers\Zapier;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Resource;
use App\Models\JobPayment;
use App\Models\WorkCrewNote;
use App\Transformers\Optimized\JobProjectsTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\JobType;

class JobsTransformer extends TransformerAbstract
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
        $data = [
            'id'                   => $job->id,
            'name'                 => $job->name,
            'number'               => $job->number,
            'multi_job'            => $job->multi_job,
            'trades'               => $this->getTrades($job),
            'description'          => $job->description,
            'amount'               => $job->amount,
            'other_trade_type_description' => $job->other_trade_type_description,
            'source_type'          => $job->source_type,
            'distance'             => isset($job->distance) ? $job->distance : null,
            'call_required'        => (bool)$job->call_required,
            'taxable'              => $job->taxable,
            'job_types'            => $this->getjobTypes($job),
            'work_types'           => $this->getWorkTypes($job),
            'tax_rate'             => $job->tax_rate,
            'duration'             => $job->duration,
            'completion_date'      => $job->completion_date,
            'contract_signed_date' => $job->cs_date,
            'to_be_scheduled'      => $job->to_be_scheduled,
            'stage_changed_date'   => $job->stage_changed_date,
            'current_stage'        => $this->getCurrentStage($job),
            'origin'               => $job->origin,
            'latitude'             => $job->address->lat,
            'longitude'            => $job->address->long,
            'address'              => $job->address->address,
            'city'                 => $job->address->city,
            'state'                => isset($job->address->state->code) ? $job->address->state->code : null,
            'zip_code'             => $job->address->zip,
            'country'              => isset($job->address->country->code) ?$job->address->country->code                       :null,
            'created_at_utc'       => \Carbon\Carbon::parse($job->created_at)->format('Y-m-d\TH:i:s\Z'),
            'updated_at_utc'       => \Carbon\Carbon::parse($job->updated_at)->format('Y-m-d\TH:i:s\Z'),
            'customer'             => $this->getCustomer($job),
            'parent_job_stage'     => $this->getparentJobStage($job),
            'status'               => $this->getJobStatus($job),
            'is_project'           => $job->isProject() ? 1 : 0,
        ];

        return $data;
    }

     /**
     * Include trades
     *
     * @return League\Fractal\ItemResource
     */
    private function getTrades($job)
    {
        if($job->trades->isEmpty()) {
            return [];
        }
        $trades = [];
        foreach($job->trades as $trade) {
            $trades[] = [
                'id' => $trade->id,
                'name' => $trade->name,
            ];
        }
        return $trades;
    }

    private function getJobTypes($job)
    {
        if($job->jobTypes->isEmpty()) {
            return [];
        }
        $types = [];
        foreach($job->jobTypes as $jobType) {
            $types[] = [
                'id' => $jobType->id,
                'name' => $jobType->name,
            ];
        }

        return $types;
    }

    private function getWorkTypes($job)
    {
        if($job->workTypes->isEmpty()) {
            return [];
        }
        $workTypes = [];
        foreach($job->workTypes as $workType) {
            $workTypes[] = [
                'id' => $workType->id,
                'name' => $workType->name,
            ];
        }

        return $workTypes;
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    private function getCustomer($job)
    {
        $customer = $job->customer;
        $jobCustomer = [
            'first_name' => $customer->first_name,
            'last_name'  => $customer->last_name,
            'full_name'  => $customer->full_name,
            'email'      => $customer->email,
            'company_name' => $customer->company_name,
            'phone'        => isset($customer->phones->first()->number) ? $customer->phones->first()->number : null
        ];
        return $jobCustomer;
    }

    /**
     * @param object | Job Model instance
     *
     * @return array | current stage data
     */
    private function getCurrentStage($job)
    {
        $ret = ['name' => 'Unknown', 'color' => 'black', 'code' => null];
        try {
            $currentStage = [];
            $jobWorkflow = $job->jobWorkflow;
            if (is_null($jobWorkflow)) {
                return $ret;
            }
            $stage = $jobWorkflow->stage;
            $currentStage['name'] = $stage->name;
            $currentStage['color'] = $stage->color;
            $currentStage['code'] = $stage->code;
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    private function getparentJobStage($job)
    {
        $parentJobStage = [];
        $jobWorkflow = $job->parentJobWorkflow;
        if (!$jobWorkflow) {
            return [];
        }
        $stage = $jobWorkflow->stage;
        $parentJobStage['name'] = $stage->name;
        $parentJobStage['color'] = $stage->color;
        $parentJobStage['code'] = $stage->code;
        return $parentJobStage;
    }

    private function getJobStatus($job)
    {
        $status = $job->projectStatus;
        if(!$status) return [];
        $jobStatus = [
            'name' => $status->name,
        ];
        return $jobStatus;
    }
}
