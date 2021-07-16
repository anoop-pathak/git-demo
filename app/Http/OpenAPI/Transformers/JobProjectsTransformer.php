<?php

namespace App\Http\OpenAPI\Transformers;

use App\Http\OpenAPI\Transformers\AddressesTransformer;
use App\Http\OpenAPI\Transformers\JobContactTransformer;
use App\Http\OpenAPI\Transformers\JobTypesTransformer;
use App\Http\OpenAPI\Transformers\LabourTransformer;
use App\Http\OpenAPI\Transformers\UsersTransformer;
use League\Fractal\TransformerAbstract;

class JobProjectsTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
        'trades', 
        'current_stage',
        'work_types',
        'reps',
        'sub_contractors',
        'estimators',
    ];

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
            'alt_id' => $job->alt_id,
            'duration' => $job->duration,
            'created_at' => $job->created_at,
            'created_date' => $job->created_date,
            'updated_at' => $job->updated_at,
            'description' => $job->description,
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['status'] = $job->projectStatus;
            $data['awarded'] = $job->awarded;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        return $data;
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($job)
    {
        $trades = $job->trades;
        if($trades) {
			return $this->collection($trades, function($trade) {
                return [
                	'id'   => $trade->id,
                	'name' => $trade->name,
                ];
            }); 
        }
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProjects($job)
    {
        if ($job->isMultiJob()) {
            $projects = $job->projects;
            return $this->collection($projects, new JobProjectsTransformer);
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
     * Include sub_contractors
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSubContractors($job)
    {
        $subContractors = $job->subContractors;
        if ($subContractors) {
            return $this->collection($subContractors, new LabourTransformer);
        }
    }

    /**
     * Job work types
     * @param type $job
     * @return type
     */
    public function includeWorkTypes($job)
    {
        $workTypes = $job->workTypes;
        if ($workTypes) {
            return $this->collection($workTypes, new JobTypesTransformer);
        }
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($job)
    {
        $customer = $job->customer;
        if ($customer) {
            return $this->item($customer, new CustomersTransformer);
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
     * Include estimator
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEstimators($job)
    {
        $estimators = $job->estimators;
        if ($estimators) {
            return $this->collection($estimators, new UsersTransformer);
        }
    }
 
    /**
     * Include contact
     *
     * @return League\Fractal\ItemResource
     */
    public function includeContact($job)
    {
        $contact = $job->jobContact;
        if ($contact) {
            return $this->item($contact, new JobContactTransformer);
        }
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
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    /**
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReps($job)
    {
        $reps = $job->reps;
        if ($reps) {
            return $this->collection($reps, new UsersTransformer);
        }
    }
}
