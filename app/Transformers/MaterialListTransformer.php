<?php

namespace App\Transformers;

use FlySystem;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\WorksheetTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Models\Folder;
use App\Models\MaterialList;

class MaterialListTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['created_by', 'job', 'for_supplier', 'worksheet', 'srs_order', 'linked_measurement', 'my_favourite_entity'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($materialList)
    {
        if($materialList instanceof Folder) {
			return [
				'id'                =>  $materialList->id,
				'parent_id'         =>  $materialList->parent_id,
				'company_id' 	    =>  $materialList->company_id,
				'job_id' 	        =>  $materialList->job_id,
				'title'             =>  $materialList->name,
				'is_dir'            =>  $materialList->is_dir,
				'created_by'        =>  $materialList->created_by,
				'updated_by'        =>  $materialList->updated_by,
				'created_at'        =>  $materialList->created_at,
                'updated_at'        =>  $materialList->updated_at,
                'no_of_child'       =>  $materialList->children->count(),
            ];
        }

        return [
            'id' => $materialList->id,
            'parent_id' => $materialList->parent_id,
            'title' => $materialList->title,
            'job_id' => $materialList->job_id,
            'thumb' => $materialList->getThumb(),
            'is_file' => $materialList->is_file,
            'type' => $materialList->type,
            'is_dir'  => false,
            'file_name' => str_replace(' ', '_', $materialList->file_name),
            'file_path' => $materialList->getFilePath(),
            'file_mime_type' => $materialList->file_mime_type,
            'file_size' => $materialList->file_size,
            'created_at' => $materialList->created_at,
            'updated_at' => $materialList->updated_at,
            'serial_number' => $materialList->serial_number,
            'worksheet_id' => $materialList->worksheet_id,
            'link_type' => $materialList->link_type,
            // 'linked_estimate' => $materialList->getLinkedEstimate(),
            // 'linked_proposal' => $materialList->getLinkedProposal(),
            'for_supplier_id' => $materialList->for_supplier_id,
            'template' => $materialList->template,
            'branch_detail' => $materialList->branch_detail,
            'measurement_id'  => $materialList->measurement_id,
        ];
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($materialList)
    {
        $user = $materialList->createdBy;

        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($materialList)
    {
        if($materialList instanceof MaterialList) {
            $job = $materialList->job;
            if ($job) {
                return $this->item($job, new JobsTransformerOptimized);
            }
        }
    }

    /**
     * Include worksheet
     * @return Response
     */
    public function includeWorksheet($materialList)
    {
        if($materialList instanceof MaterialList) {
            $worksheet = $materialList->worksheet;
            if ($worksheet) {
                return $this->item($worksheet, new WorksheetTransformerOptimized);
            }
        }
    }

    /**
     * Include For Supplier
     *
     * @return League\Fractal\ItemResource
     */
    public function includeForSupplier($materialList)
    {
        if($materialList instanceof MaterialList) {
            $supplier = $materialList->forSupplier;

            if ($supplier) {
                return $this->item($supplier, function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ];
                });
            }
        }
    }

    /**
     * Include Linked Estimate
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedEstimate($materialList)
    {
        if($materialList instanceof MaterialList) {
            $estimate = $materialList->getLinkedEstimate();

            if ($estimate) {
                return $this->item($estimate, function ($estimate) {
                    return [
                        'id' => $estimate->id,
                        'type' => $estimate->type,
                        'worksheet_id' => $estimate->worksheet_id,
                        'file_path' => FlySystem::publicUrl(config('jp.BASE_PATH') . $estimate->file_path),
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
    public function includeLinkedProposal($materialList)
    {
        if($materialList instanceof MaterialList) {
            $proposal = $materialList->getLinkedProposal();

            if ($proposal) {
                return $this->item($proposal, function ($proposal) {
                    return [
                        'id' => $proposal->id,
                        'type' => $proposal->type,
                        'worksheet_id' => $proposal->worksheet_id,
                        'file_path' => FlySystem::publicUrl(config('jp.BASE_PATH') . $proposal->file_path),
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
     * Include SRS Order
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSrsOrder($materialList)
    {
        if($materialList instanceof MaterialList) {
            $order = $materialList->srsOrder;

            if ($order) {
                return $this->item($order, function ($order) {
                    return [
                        'order_id' => $order->order_id,
                        'order_status' => $order->order_status,
                        'srs_order_id' => $order->srs_order_id,
                    ];
                });
            }
        }
    }

    public function includeLinkedMeasurement($materialList)
    {
        if($materialList instanceof MaterialList) {
            $measurement = $materialList->measurement;
            if($measurement) {
                return $this->item($measurement, function($measurement){
                    return [
                        'id'        => $measurement->id,
                        'file_path' => $measurement->getFilePath(),
                    ];
                });
            }
        }
    }

    /**
     * Include User Favourite Entity
     *
     * @return League\Fractal\ItemResource
     */
    public function includeMyFavouriteEntity($materialList)
    {
        if($materialList instanceof MaterialList) {
            $entity = $materialList->myFavouriteEntity;

            if($entity) {
                return $this->item($entity, new UserFavouriteEntitiesTransformer);
            }
        }
    }
}
