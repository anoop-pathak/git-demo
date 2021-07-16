<?php

namespace App\Transformers;

use FlySystem;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Models\MaterialList;
use App\Models\Folder;

class WorkOrderTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['linked_estimate', 'linked_proposal'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['created_by', 'job', 'schedules', 'linked_measurement', 'my_favourite_entity'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($workOrder)
    {
        if($workOrder instanceof Folder) {
			return [
                'id'                =>  $workOrder->id,
                'parent_id'         => $workOrder->parent_id,
				'company_id' 	    =>  $workOrder->company_id,
				'job_id' 	        =>  $workOrder->job_id,
				'title'             =>  $workOrder->name,
				'is_dir'            =>  $workOrder->is_dir,
				'created_by'        =>  $workOrder->created_by,
				'updated_by'        =>  $workOrder->updated_by,
				'created_at'        =>  $workOrder->created_at,
                'updated_at'        =>  $workOrder->updated_at,
                'no_of_child'       =>  $workOrder->children->count(),
            ];
        }

        return [
            'id' => $workOrder->id,
            'parent_id' =>  $workOrder->parent_id,
            'title' => $workOrder->title,
            'job_id' => $workOrder->job_id,
            'thumb' => $workOrder->getThumb(),
            'is_file' => $workOrder->is_file,
            'type' => $workOrder->type,
            'is_dir'          => false,
            'file_name' => str_replace(' ', '_', $workOrder->file_name),
            'file_path' => $workOrder->getFilePath(),
            'file_mime_type' => $workOrder->file_mime_type,
            'file_size' => $workOrder->file_size,
            'created_at' => $workOrder->created_at,
            'updated_at' => $workOrder->updated_at,
            'serial_number' => $workOrder->serial_number,
            'worksheet_id' => $workOrder->worksheet_id,
            'link_type' => $workOrder->link_type,
            // 'linked_estimate' => $workOrder->getLinkedEstimate(),
            // 'linked_proposal' => $workOrder->getLinkedProposal(),
            'measurement_id'  => $workOrder->measurement_id,
        ];
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($workOrder)
    {
        $user = $workOrder->createdBy;

        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($workOrder)
    {
        if($workOrder instanceof MaterialList) {
            $job = $workOrder->job;
            if ($job) {
                return $this->item($job, new JobsTransformerOptimized);
            }
        }
    }

    /**
     * Includes schedules
     * @param  Instance $workOrder Work order
     * @return Response
     */
    public function includeSchedules($workOrder)
    {
        if($workOrder instanceof MaterialList) {
            $schedules = $workOrder->schedules;
            if ($schedules) {
                $scheduleTrans = new JobScheduleTransformer;
                $scheduleTrans->setDefaultIncludes([]);

                return $this->collection($schedules, $scheduleTrans);
            }
        }
    }


    /**
     * Include Linked Estimate
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedEstimate($workOrder)
    {
        if($workOrder instanceof MaterialList) {
            $estimate = $workOrder->getLinkedEstimate();

            if ($estimate) {
                return $this->item($estimate, function ($estimate) {
                    return [
                        'id' => $estimate->id,
                        'type' => $estimate->type,
                        'worksheet_id' => $estimate->worksheet_id,
                        'file_path' => $estimate->getFilePath(),
                        'file_name' => $estimate->file_name,
                        'file_mime_type' => $estimate->file_mime_type,
                        'file_size' => $estimate->file_size,
                        'updated_at' => $estimate->updated_at,
                    ];
                });
            }
        }
    }

    /**
     * Include Linked Proposal
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedProposal($workOrder)
    {
        if($workOrder instanceof MaterialList) {
            $proposal = $workOrder->getLinkedProposal();

            if ($proposal) {
                return $this->item($proposal, function ($proposal) {
                    return [
                        'id' => $proposal->id,
                        'type' => $proposal->type,
                        'worksheet_id' => $proposal->worksheet_id,
                        'file_path' => $proposal->getFilePath(),
                        'file_name' => $proposal->file_name,
                        'file_mime_type' => $proposal->file_mime_type,
                        'file_size' => $proposal->file_size,
                        'updated_at' => $proposal->updated_at,
                    ];
                });
            }
        }
    }

    /**
     * Include Linked Measurement
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedMeasurement($workOrder)
    {
        $measurement = $workOrder->measurement;
         if($measurement) {
            return $this->item($measurement, function($measurement){
                return [
                    'id'        => $measurement->id,
                    'file_path' => $measurement->getFilePath(),
                ];
            });
        }
    }

    /**
     * Include User Favourite Entity
     *
     * @return League\Fractal\ItemResource
     */
    public function includeMyFavouriteEntity($workOrder)
    {
        $entity = $workOrder->myFavouriteEntity;
        if($entity) {
            return $this->item($entity, new UserFavouriteEntitiesTransformer);
        }
    }
}
