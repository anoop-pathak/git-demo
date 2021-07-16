<?php

namespace App\Http\OpenAPI\Transformers;

use App\Http\OpenAPI\Transformers\JobProjectsTransformer;
use League\Fractal\TransformerAbstract;
use App\Http\OpenAPI\Transformers\AddressesTransformer;
use App\Http\OpenAPI\Transformers\UsersTransformer;
use App\Http\OpenAPI\Transformers\CustomersTransformer;
use App\Http\OpenAPI\Transformers\LabourTransformer;
use App\Http\OpenAPI\Transformers\JobTypesTransformer;
use App\Http\OpenAPI\Transformers\JobNotesTransformer;


class JobsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [

    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'address',
        'reps',
        'estimators',
        'customer',
        'sub_contractors',
        'parent',
        'projects',
        'work_types',
        'trades',
        'job_notes'
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
            'name' => $job->name,
            'number' => $job->number,
            'customer_id' => $job->customer_id,
            'description' => $job->description,
            'same_as_customer_address' => $job->same_as_customer_address,
            'other_trade_type_description' => $job->other_trade_type_description,
            'created_by' => $job->created_by, // create a new include
            'created_at' => $job->created_at,
            'created_date' => $job->created_date,
            'updated_at' => $job->updated_at,
            'deleted_at' => $job->deleted_at,
            'call_required' => (bool)$job->call_required,
            'appointment_required' => (bool)$job->appointment_required,
            'tax_rate' => $job->tax_rate,
            'alt_id' => $job->alt_id,
            'lead_number' => $job->lead_number,
            'duration' => $job->duration,
            'completion_date' => $job->completion_date,
            'contract_signed_date' => $job->cs_date,
            'current_stage' => $this->getCurrentStage($job),
            'contact_same_as_customer' => (int)$job->contact_same_as_customer,
            'insurance' => $job->insurance,
            'archived' => $job->archived,
            'hover_job_id' => null,
            'awarded_date' => $job->awarded_date,
        ];

        if(!empty($job->stage_last_modified)) {
            $data['stage_last_modified'] = $job->stage_last_modified;
        }

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['status'] = $job->projectStatus;
            $data['awarded'] = $job->awarded;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        if($job->hoverJob) {
            $data['hover_job_id'] = $job->hoverJob->hover_job_id;
        }

        return $data;
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
     * Include Parent for projects
     *
     * @return League\Fractal\ItemResource
     */
    public function includeParent($job)
    {
        if ($job->isProject() && ($parentJob = $job->parentJob)) {
            return $this->item($parentJob, new JobProjectsTransformer);
        }
    }

    /**
     * Include Notes
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobNotes($job, $params)
    {
        list($for_current_stage) = $params['current_stage'];
        if ((bool)$for_current_stage) {
            $current_stage = $job->jobWorkflow->current_stage;
            $notes = $job->notes()
                ->where('stage_code', '=', $current_stage)
                ->get();
        } else {
            $notes = $job->notes;
        }

        if ($notes) {
            return $this->collection($notes, new JobNotesTransformer);
        }
    }


    /******************** Private function ********************/


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
            $job->stage_last_modified = $jobWorkflow->stage_last_modified;
            if($job->current_stage_name) {
                $currentStage['name']  = $job->current_stage_name;
                $currentStage['color'] = $job->current_stage_color;
                $currentStage['code']  = $job->current_stage_code;
            }else {
                $stage = $jobWorkflow->stage;
                $currentStage['name'] = $stage->name;
                $currentStage['color'] = $stage->color;
                $currentStage['code'] = $stage->code;
            }
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    /**
     * Include Projects
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProjects($job)
    {
        if ($job->isMultiJob()) {
            $projects = $job->projects;

            $transformer = (new JobProjectsTransformer)->setDefaultIncludes([]);

            return $this->collection($projects, $transformer);
        } 
    }

    /**
     * Include Plan
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
     * Include workTypes
     *
     * @return League\Fractal\ItemResource
     */

    public function includeWorkTypes($schedule) {
        $worktypes = $schedule->workTypes;
        if($worktypes){

            return $this->collection($worktypes, new JobTypesTransformer);
        }
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */

	// public function includeCreatedBy($job) {
    //     $user = $job->created_by;
    //     $user = \App\Models\User::find($user);
	// 	if($user) { 
	// 		return $this->item($user, new UsersTransformer);
	// 	}
	// }
}
