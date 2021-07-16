<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;

class JobsSelectedListTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['current_stage', 'job_meta', 'address', 'trades', 'resource_ids', 'division'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'alt_id' => $job->alt_id,
            'archived' => $job->archived,
            'customer_id' => $job->customer_id,
            'multi_job' => $job->multi_job,
            'parent_id' => $job->parent_id,
            'number' => $job->number,
            'division_code' => $job->division_code
        ];

        return $data;
    }

    /**
     * Include Current Stage
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCurrentStage($job)
    {
        $currentStage = $job->getCurrentStage();

        if(!empty($currentStage)) {
            return $this->item($currentStage, function($currentStage) {
                return [
                    'name'          => $currentStage['name'],
                    'color'         => $currentStage['color'],
                    'code'          => $currentStage['code'],
                    'resource_id'   => $currentStage['resource_id'],
                    'last_stage_completed_date'   => $currentStage['last_stage_completed_date'],
                ];
            });
        }
    }

    /**
     * Include Job Meta
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobMeta($job)
    {
        $meta = $job->jobMeta;

        if ($meta) {
            $metaData = [];
            foreach ($meta as $key => $value) {
                $metaData[$value['meta_key']] = $value['meta_value'];
            }

            return $this->item($metaData, function ($data) {

                return [
                    'resource_id' => $data['resource_id'],
                    'default_photo_dir' => $data['default_photo_dir'],
                ];
            });
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
            $transformer = new AddressesTransformer;
            $transformer->setDefaultIncludes([]);

            return $this->item($address, $transformer);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($job)
    {
        $trades = $job->trades;

        if (!$trades->isEmpty()) {
            $transformer = new TradesTransformer;
            $transformer->setDefaultIncludes([]);
            return $this->collection($trades, $transformer);
        }
    }

    /**
     * include for resource ids
     * @param  [instance] $job [descriptio]
     * @return [array]      [resource ids]
     */
    public function includeResourceIds($job)
    {
        $ids = [];

        if (isset($job->workflow->resource_id)) {
            $ids['workflow_resource_id'] = $job->workflow->resource_id;
        }

        if (isset($job->company->subscriberResource->value)) {
            $ids['company_resource_id'] = $job->company->subscriberResource->value;
        }

        return $this->item($ids, function ($ids) {

            return [
                'workflow_resource_id' => array_key_exists('workflow_resource_id', $ids) ? $ids['workflow_resource_id'] : null,
                'company_resource_id' => array_key_exists('company_resource_id', $ids) ? $ids['company_resource_id'] : null
            ];
        });
    }

    /**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivision($job) {
        $division = $job->division;

         if($division){
            return $this->item($division, new DivisionsTransformerOptimized);
        }
    }
}
