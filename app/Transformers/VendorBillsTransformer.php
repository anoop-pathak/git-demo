<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\VendorsTransformer;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use FlySystem;

class VendorBillsTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['vendor', 'lines', 'job', 'attachments'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($vendorBills) {
      return [
        'id'          =>  $vendorBills->id,
        'bill_date'   =>  $vendorBills->bill_date,
        'due_date'    =>  $vendorBills->due_date,
        'address'     =>  $vendorBills->address,
        'file_path'   =>  FlySystem::publicUrl($vendorBills->file_path),
        'bill_number' =>  $vendorBills->bill_number,
        'note'        =>  $vendorBills->note,
        'total_amount'=>  $vendorBills->total_amount,
        'created_at'  =>  $vendorBills->created_at,
        'updated_at'  =>  $vendorBills->updated_at,
        'tax_amount'  =>  $vendorBills->tax_amount,
        'quickbook_sync_status' => $vendorBills->getQuickbookStatus(),
        'origin'      =>  $vendorBills->originName(),
        'quickbook_id'=>  $vendorBills->quickbook_id,
        'qb_desktop_id' => $vendorBills->qb_desktop_txn_id
    ];
}

    /**
     * Include vendors
     *
     * @return League\Fractal\ItemResource
     */
    public function includeVendor($vendorBills)
    {
        $vendor = $vendorBills->vendor;
        if($vendor) {

            return $this->item($vendor, new VendorsTransformer);
        }
    }
    /**
     * Include lines
     *
     * @return League\Fractal\ItemResource
     */

    public function includeLines($vendorBills)
    {
        $lines = $vendorBills->lines;

        return $this->collection($lines, new VendorBillLinesTransformer);
    }
     /**
     * Include job
     *
     * @return League\Fractal\ItemResource
     */

     public function includeJob($vendorBills)
     {
        $job = $vendorBills->job;
        if($job) {

            return $this->item($job, new JobsTransformerOptimized);
        }
    }

    /**
     * Include attachments
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAttachments($vendorBills) {
        $attachments = $vendorBills->attachments;
        return $this->collection($attachments, function($attachment){
            return [
                'id'                     => $attachment->id,
                'parent_id'              => $attachment->parent_id,
                'name'                   => $attachment->name,
                'size'                   => $attachment->size,
                'path'                   => $attachment->path,
                'mime_type'              => $attachment->mime_type,
                'meta'                   => $attachment->meta,
                'created_at'             => $attachment->created_at,
                'updated_at'             => $attachment->updated_at,
                'url'                    => $attachment->url,
                'thumb_url'              => $attachment->thumb_url,
            ];
        });
    }
}