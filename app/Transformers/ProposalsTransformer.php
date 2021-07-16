<?php

namespace App\Transformers;

use App\Models\Proposal;
use FlySystem;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\WorksheetTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Models\Folder;

class ProposalsTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'linked_estimate',
        'linked_material_list',
        'linked_material_lists',
        'worksheet',
        'linked_work_order',
        'linked_measurement'
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'createdBy',
        'pages',
        'attachments',
        'job',
        'customer',
        'linked_invoices',
        'customer_signature',
        'job_invoice_count',
        'linked_measurement',
        'template_pages',
        'my_favourite_entity',
        'deleted_entity',
        'deleted_by',
        'tables',
        'digital_sign_queue_status',
        'comment',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($proposal)
    {

        if($proposal instanceof Folder) {
			return [
				'id'                =>  $proposal->id,
				'parent_id'         =>  $proposal->parent_id,
				'company_id' 	    =>  $proposal->company_id,
				'job_id' 	        =>  $proposal->job_id,
				'title'             =>  $proposal->name,
				'is_dir'            =>  $proposal->is_dir,
				'created_by'        =>  $proposal->created_by,
				'updated_by'        =>  $proposal->updated_by,
				'created_at'        =>  $proposal->created_at,
                'updated_at'        =>  $proposal->updated_at,
                'no_of_child'       =>  $proposal->children->count(),
            ];
        }
		$data = [
            'id'              => $proposal->id,
            'parent_id'       => $proposal->parent_id,
            'title'           => $proposal->title,
            'job_id'          => $proposal->job_id,
            'template'        => isset($proposal->firstPage->template) ? $proposal->firstPage->template : null,
            'template_cover'  => isset($proposal->firstPage->template_cover) ? $proposal->firstPage->template_cover : null,
            'image'           => !empty($proposal->firstPage->image) ? FlySystem::publicUrl(config('jp.BASE_PATH').$proposal->firstPage->image) : null,
            'thumb'           => $proposal->getThumb(),
            'is_file'         => $proposal->is_file,
            'file_name'       => str_replace(' ', '_', $proposal->file_name),
            'file_path'       => $proposal->getFilePath(),
            'file_mime_type'  => $proposal->file_mime_type,
            'file_size'       => $proposal->file_size,
            'is_mobile'       => $proposal->is_mobile,
            'created_at'      => $proposal->created_at,
            'updated_at'      => $proposal->updated_at,
            'total_pages'     => $proposal->pages->count(),
            'is_expired'      => (int)$proposal->is_expired,
            'expiration_id'   => $proposal->expiration_id,
            'expiration_date' => $proposal->expiration_date,
            'note'            => $proposal->note,
            'status'          => $proposal->status,
            'serial_number'   => $proposal->serial_number,
            'expiration_description' => ($documentExpire = $proposal->documentExpire) ? $documentExpire->description : null,
            'page_type'       => $proposal->page_type,
            'attachments_per_page' => $proposal->attachments_per_page,
            'insurance_estimate' => $proposal->insurance_estimate,
            'share_on_hop'       => $proposal->share_on_hop,
            'type'               => $proposal->type,
            'worksheet_id'       => $proposal->worksheet_id,
            'linked_gsheet_url'  => $proposal->linked_gsheet_url,
            'comment'            => $proposal->comment,
            'initial_signature'  => $proposal->initial_signature,
            'measurement_id'     => $proposal->measurement_id,
            'is_digital_signed'  => (int)$proposal->digital_signed,
        ];

        if($proposal->google_sheet_id) {
            $data['google_sheet_id']  = $proposal->google_sheet_id;
            $data['google_sheet_url'] = getGoogleSheetUrl($proposal->google_sheet_id);
        }

        return $data;
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($proposal)
    {
        $user = $proposal->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include pages
     *
     * @return League\Fractal\ItemResource
     */
    public function includePages($proposal)
    {
        if($proposal instanceof Proposal) {
            $pages = $proposal->pages;

            return $this->collection($pages, new ProposalPageTransformer);
        }
    }

    /**
     * Include attachments
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAttachments($proposal)
    {
        if($proposal instanceof Proposal) {
            $attachments = $proposal->attachments;
            return $this->collection($attachments, function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'url' => FlySystem::publicUrl(config('jp.BASE_PATH') . $attachment->path),
                ];
            });
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($proposal)
    {
        if($proposal instanceof Proposal) {
            $job = $proposal->job;
            if ($job) {
                return $this->item($job, new JobsTransformerOptimized);
            }
        }
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($proposal)
    {
        if($proposal instanceof Proposal) {
            $customer = $proposal->job->customer;
            if ($customer) {
                return $this->item($customer, new CustomersTransformerOptimized);
            }
        }
    }

    /**
     * Include
     * @param  Instance $proposal Proposal
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedInvoices($proposal)
    {
        if($proposal instanceof Proposal) {
            $invoices = $proposal->invoices;

            $invoiceTrans = new JobInvoiceTransformer;
            $invoiceTrans->setDefaultIncludes(null);

            return $this->collection($invoices, $invoiceTrans);
        }
    }

    /**
     * Worksheet Include..
     * @param  Instance $proposal Proposal
     * @return League\Fractal\ItemResource
     */
    public function includeWorksheet($proposal)
    {
        if($proposal instanceof Proposal) {
            $worksheet = $proposal->worksheet;
            if ($worksheet) {
                return $this->item($worksheet, new WorksheetTransformerOptimized);
            }
        }
    }

    public function includeLinkedEstimate($proposal)
    {
        if($proposal instanceof Proposal) {
            $estimate = $proposal->linkedEstimate;
            if ($estimate) {
                return $this->item($estimate, function ($estimate) {

                    return [
                        'id' => $estimate->id,
                        'worksheet_id' => $estimate->worksheet_id,
                        'file_path'     => $estimate->getFilePath(),
                    ];
                });
                // return $this->item($estimate, new EstimationsTransformer);
            }
        }
    }

    /**
     * Include Linked Material List
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLinkedMaterialList($proposal)
    {
        if($proposal instanceof Proposal) {
            $linkedMaterialLists = $proposal->getMaterialLists();

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
    public function includeLinkedMaterialLists($proposal)
    {
        if($proposal instanceof Proposal) {
            $linkedMaterialLists = $proposal->getMaterialLists();

            if ($linkedMaterialLists) {
                return $this->collection($linkedMaterialLists, function ($linkedMaterialSheet) {

                    return [
                        'id' => $linkedMaterialSheet->id,
                        'worksheet_id' => $linkedMaterialSheet->worksheet_id,
                        'for_supplier_id' => $linkedMaterialSheet->for_supplier_id,
                        'file_path' => $linkedMaterialSheet->getFilePath(),
                        'file_mime_type' => $linkedMaterialSheet->file_mime_type,
                    ];
                });
            }
        }
    }

    public function includeLinkedMeasurement($proposal)
    {
        if($proposal instanceof Proposal) {
            $measurement = $proposal->measurement;
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
     * Include Signature
     * @param  Proposal $proposal Proposal Instance
     * @return Signature
     */
    public function includeCustomerSignature($proposal)
    {
        if (($proposal instanceof Proposal) && $proposal->signature) {
            return $this->item($proposal, function ($proposal) {
                return [
                    'signature' => $proposal->signature,
                    'multiple_signatures' => $proposal->multiple_signatures,
                ];
            });
        }
    }

    /**
     * Include Linked Work Order
     * @param  Instance $proposal Proposal
     * @return Response
     */
    public function includeLinkedWorkOrder($proposal)
    {
        if($proposal instanceof Proposal) {
            $linkedWorkOrder = $proposal->getWorkOrder();
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
     * Include job invoice count
     * @param  Instance $proposal
     * @return count
     */
    public function includeJobInvoiceCount($proposal)
    {
        if($proposal instanceof Proposal) {
            $count = $proposal->invoices->count();

            return $this->item($count, function ($count) {

                return [
                    'count' => $count
                ];
            });
        }
    }

    /**
     * Worksheet Include..
     * @param  Instance $proposal Proposal
     * @return League\Fractal\ItemResource
     */
    public function includeTemplatePages($proposal)
    {
        if($proposal instanceof Proposal) {
            $worksheet = $proposal->worksheet;
            if($worksheet && (!$worksheet->templatePages->isEmpty())) {
                return $this->collection($worksheet->templatePages, new WorksheetTemplatePagesTransformer);
            }
        }
    }

    /**
     * Include User Favourite Entity
     *
     * @return League\Fractal\ItemResource
     */
    public function includeMyFavouriteEntity($proposal)
    {
        if($proposal instanceof Proposal) {
            $entity = $proposal->myFavouriteEntity;

            if($entity) {
                return $this->item($entity, new UserFavouriteEntitiesTransformer);
            }
        }
    }

    public function includeDeletedEntity($proposal)
    {
        $deletedDate = $proposal->deleted_at;

        if($deletedDate){

            return $this->item($deletedDate, function($deletedDate){
                return [
                    'deleted_at' => $deletedDate->toDateTimeString(),
                ];
            });
        }
    }

    public function includeDeletedBy($proposal)
    {
        $user = $proposal->deletedBy;

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

    public function includeTables($proposal)
    {
        $pageTableCalculations = $proposal->pageTableCalculations;

        if($pageTableCalculations) {
            return $this->collection($pageTableCalculations, new PageTableCalculationTransformer);
        }
    }

    public function includeDigitalSignQueueStatus($proposal)
    {
        $queueStatus = $proposal->digitalSignQueueStatus;

        if($queueStatus) {
            return $this->item($queueStatus, function($queueStatus) {
                return [
                    'id'            => $queueStatus->id,
                    'status'        => $queueStatus->status,
                    'error_reason'  => $queueStatus->error_reason,
                    'created_at'    => $queueStatus->created_at,
                    'updated_at'    => $queueStatus->updated_at,
                ];
            });
        }
    }

    public function includeComment($proposal)
    {

        return $this->item($proposal, function($proposal){
                return [
                    'comment' => $proposal->comment,
                ];
        });
    }

}
