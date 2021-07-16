<?php

namespace App\Transformers;

use FlySystem;
use App\Transformers\Optimized\WorksheetTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Models\Folder;
use App\Models\Estimation;


class EstimationsTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'linked_material_list',
        'linked_proposal',
        'linked_work_order',
        'worksheet',
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'linked_measurement',
        'createdBy',
        'ev_order',
        'pages',
        'sm_order',
        'my_favourite_entity',
        'job',
        'deleted_entity',
        'deleted_by',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($estimation)
    {
        if($estimation instanceof Folder) {
            $totalCount = 0;
			if($estimation->doc_children) {
				$totalCount = $estimation->doc_children->count();
            }
            if($estimation->dir_children) {
				$totalCount += $estimation->dir_children->count();
			}
			return [
                'id'                =>  $estimation->id,
                'parent_id'       => $estimation->parent_id,
				'parent_id'         =>  $estimation->parent_id,
                'company_id' 	    =>  $estimation->company_id,
                'job_id' 	        =>  $estimation->job_id,
				'title'             =>  $estimation->name,
				'is_dir'            =>  $estimation->is_dir,
				'created_by'        =>  $estimation->created_by,
				'updated_by'        =>  $estimation->updated_by,
				'created_at'        =>  $estimation->created_at,
                'updated_at'        =>  $estimation->updated_at,
                'no_of_child'       =>  $totalCount,
            ];
        }

        $data = [
            'id' => $estimation->id,
            'title' => $estimation->title,
            'job_id' => $estimation->job_id,
            'template' => isset($estimation->firstPage->template) ? $estimation->firstPage->template : null,
            'template_cover' => isset($estimation->firstPage->template_cover) ? $estimation->firstPage->template_cover : null,
            'image' => isset($estimation->firstPage->image) ? FlySystem::publicUrl(config('jp.BASE_PATH').$estimation->firstPage->image) : Null,
            'thumb' => $estimation->getThumb(),
            'is_file' => $estimation->is_file,
            'file_name' => str_replace(' ', '_', $estimation->file_name),
            'file_path'       => $estimation->getFilePath(),
            'file_mime_type' => $estimation->file_mime_type,
            'file_size' => $estimation->file_size,
            'is_mobile' => $estimation->is_mobile,
            'ev_report_id' => $estimation->ev_report_id,
            'created_at' => $estimation->created_at,
            'updated_at' => $estimation->updated_at,
            'total_pages' => $estimation->pages->count(),
            'is_expired' => (int)$estimation->is_expired,
            'expiration_id' => $estimation->expiration_id,
            'expiration_date' => $estimation->expiration_date,
            'expiration_description' => ($documentExpire = $estimation->documentExpire) ? $documentExpire->description : null,
            'page_type' => $estimation->page_type,
            'type' => $estimation->type,
            'worksheet_id' => $estimation->worksheet_id,
            'serial_number' => $estimation->serial_number,
            'sm_order_id' => $estimation->sm_order_id,
            'share_on_hop' => $estimation->share_on_hop,
            'job_insurance' => $estimation->job_insurance,
            'clickthru_id'    => $estimation->clickthru_estimate_id,
            'measurement_id'  => $estimation->measurement_id,
        ];

        if ($estimation->google_sheet_id) {
            $data['google_sheet_id'] = $estimation->google_sheet_id;
            $data['google_sheet_url'] = getGoogleSheetUrl($estimation->google_sheet_id);
        }

        if ($estimation->xactimate_file_path) {
            $data['xactimate_file_path'] = FlySystem::publicUrl(config('jp.BASE_PATH').$estimation->xactimate_file_path);
        }

        return $data;
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($estimation)
    {
        $user = $estimation->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include Eagleview Order
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEvOrder($estimation)
    {
        if($estimation instanceof Estimation) {
            $order = $estimation->evOrder;
            if ($order) {
                return $this->item($order, new EagleViewOrdersTransformer);
            }
        }
    }

    /**
     * Include pages
     *
     * @return League\Fractal\ItemResource
     */
    public function includePages($estimation)
    {
        if($estimation instanceof Estimation) {
            $pages = $estimation->pages;
            return $this->collection($pages, function ($page) {
                return [
                    'id' => $page->id,
                    'content' => $page->template,
                    'image' => !empty($page->image) ? FlySystem::publicUrl(config('jp.BASE_PATH').$page->image) : Null,
                    'thumb' => !empty($page->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH').$page->thumb) : Null,
                    'editable_content' => $page->template_cover,
                ];
            });
        }
    }

    /**
     * Include Linked Proposal
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedProposal($estimation)
    {
        if($estimation instanceof Estimation) {
            $linkedProposal = $estimation->linkedProposal;

            if ($linkedProposal) {
                return $this->item($linkedProposal, function ($linkedProposal) {

                    return [
                        'id' => $linkedProposal->id,
                        'worksheet_id' => $linkedProposal->worksheet_id,
                        'file_path'     => $linkedProposal->getFilePath(),                ];
                });
                // return $this->item($linkedProposal, new ProposalsTransformer);
            }
        }
    }

    /**
     * Include Linked Material List
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedMaterialList($estimation)
    {
        if($estimation instanceof Estimation) {
            $linkedMaterialLists = $estimation->getMaterialLists();

            if (sizeof($linkedMaterialLists)) {
                return $this->item($linkedMaterialLists[0], function ($linkedMaterialSheet) {

                    return [
                        'id' => $linkedMaterialSheet->id,
                        'worksheet_id' => $linkedMaterialSheet->worksheet_id,
                    ];
                });
            }
        }
    }

    /**
     * Include Linked Material Lists
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedMeasurement($estimation)
    {
        if($estimation instanceof Estimation) {
            $measurement = $estimation->measurement;
            if($measurement) {
                return $this->item($measurement, function($measurement){
                    return [
                        'id' => $measurement->id,
                        'file_path'     =>  $measurement->getFilePath(),
                    ];
                });
            }
        }
    }

    /**
     * Include worksheet
     * @return Response
     */
    public function includeWorksheet($estimation)
    {
        if($estimation instanceof Estimation) {
            $worksheet = $estimation->worksheet;

            if ($worksheet) {
                return $this->item($worksheet, new WorksheetTransformerOptimized);
            } elseif (!$worksheet && config('is_mobile')) {
                return $this->item($worksheet, function ($sheet) {

                    return [
                        'test' => null
                    ];
                });
            }
        }
    }

    /**
     * Include Linked Work Order
     * @param  Instance $estimation Estimation
     * @return Response
     */
    public function includeLinkedWorkOrder($estimation)
    {
        if($estimation instanceof Estimation) {
            $linkedWorkOrder = $estimation->getWorkOrder();
            if ($linkedWorkOrder) {
                return $this->item($linkedWorkOrder, function ($linkedWorkOrderSheet) {

                    return [
                        'id' => $linkedWorkOrderSheet->id,
                        'worksheet_id' => $linkedWorkOrderSheet->worksheet_id,
                        'file_path' => $linkedWorkOrderSheet->getFilePath(),
                    ];
                });
            }
        }
    }

    /**
     * Include Skymeasure Order
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSmOrder($estimation)
    {
        if($estimation instanceof Estimation) {
            $order = $estimation->smOrder;
            if ($order) {
                $skyMeasureTrans = new SkyMeasureOrderTransformer;
                $skyMeasureTrans->setDefaultIncludes([]);

                return $this->item($order, $skyMeasureTrans);
            }
        }
    }

    public function includeJob($estimation)
    {
        if($estimation instanceof Estimation) {
            $job = $estimation->job;

            if($job) {
                $jobTrans = new JobsTransformerOptimized;
                $jobTrans->setDefaultIncludes(['customer']);

                return $this->item($job, $jobTrans);
            }
        }
    }

    public function includeDeletedEntity($estimation)
    {
        $deletedDate = $estimation->deleted_at;

        return $this->item($deletedDate, function($deletedDate){

            return [
                'deleted_at' => $deletedDate->toDateTimeString()
            ];
        });
    }

    public function includeDeletedBy($estimation)
    {
        $user = $estimation->deletedBy;

        if($user) {

            return $this->item($user, function($user){
                return [
                    'id'                => (int)$user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'full_name_mobile'  => $user->full_name_mobile,
                    'company_name'      => $user->company_name,
                ];
            });
        }
    }

    /**
     * Include User Favourite Entity
     *
     * @return League\Fractal\ItemResource
     */
    public function includeMyFavouriteEntity($estimation)
    {
        if($estimation instanceof Estimation) {
            $entity = $estimation->myFavouriteEntity;

            if($entity) {
                return $this->item($entity, new UserFavouriteEntitiesTransformer);
            }
        }
    }
}
