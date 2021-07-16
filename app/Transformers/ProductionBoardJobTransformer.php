<?php

namespace App\Transformers;

use App\Models\Job;
use App\Transformers\Optimized\CustomersTransformer;
use League\Fractal\TransformerAbstract;

class ProductionBoardJobTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['customer', 'pb_entries', 'address'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['current_stage', 'job_meta'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {

        $data = [
            'id' => $job->id,
            'number' => $job->number,
            'alt_id'        =>  $job->alt_id,
            'name'          =>  $job->name,
            'moved_to_pb' => $job->moved_to_pb_date,
            'trades' => $job->trades,
            'work_types' => $job->workTypes,
            'archived' => $job->pb_archived_date,
            'order'         =>  $job->order,
            'job_archived'  =>  $job->archived,
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['status'] = $job->projectStatus;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        return $data;
    }

    public function includeCustomer($job)
    {
        $customer = $job->customer;
        if ($customer) {
            $customerTransObj = new CustomersTransformer;
            $customerTransObj->setDefaultIncludes(['address', 'rep']);

            return $this->item($customer, $customerTransObj);
        }
    }

    public function includePBEntries($job)
    {
        $entries = $job->productionBoardEntries;
        if ($entries) {
            return $this->collection($entries, new ProductionBoardEntryTransformer);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($job)
    {
        $address = $job->address;
        if ($address) {
            return $this->item($address, new AddressesTransformer);
        }
    }

    /**
     * Include Current Stage
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCurrentStage($job)
    {
        $currentStage = $this->getCurrentStage($job);
        return $this->item($job, function ($job) use ($currentStage) {
            return $currentStage;
        });
    }

    /**
     * Include Job Meta
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobMeta($job)
    {
        $meta = $this->getJobMeta($job);
        return $this->item($job, function ($job) use ($meta) {
            return $meta;
        });
    }

    /**
     * @param object | Job Model instance
     *
     * @return array | current stage data
     */
    private function getCurrentStage($job)
    {
        $ret = ['name' => 'Unknown', 'color' => 'black', 'code' => null, 'resource_id' => null];
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
            $currentStage['resource_id'] = $stage->resource_id;
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    /**
     * @param object | Job class instance
     *
     * @return array
     */
    private function getJobMeta($job)
    {
        $meta = $job->jobMeta;

        if ($meta) {
            $metaData = [];
            foreach ($meta as $key => $value) {
                $metaData[$value['meta_key']] = $value['meta_value'];
            }
        }
        return $metaData;
    }
}
