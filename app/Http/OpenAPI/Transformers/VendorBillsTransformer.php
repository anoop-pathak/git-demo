<?php
namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;
use App\Http\OpenAPI\Transformers\AddressesTransformer;
use App\Http\OpenAPI\Transformers\VendorsTransformer;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
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
        'id'            =>  $vendorBills->id,
        'bill_date'     =>  $vendorBills->bill_date,
        'due_date'      =>  $vendorBills->due_date,
        'address'       =>  $vendorBills->address,
        'file_path'     =>  $vendorBills->signed_url,
        'bill_number'   =>  $vendorBills->bill_number,
        'note'          =>  $vendorBills->note,
        'total_amount'  =>  $vendorBills->total_amount,
        'created_at'    =>  $vendorBills->created_at,
        'updated_at'    =>  $vendorBills->updated_at,
        'tax_amount'    =>  $vendorBills->tax_amount,
        'origin'        =>  $vendorBills->originName()
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
    public function includeAttachments($vendorBills)
    {
        $attachments = $vendorBills->attachments;

        return $this->collection($attachments, function($attachment){
            return [
                'id'                     => $attachment->id,
                'parent_id'              => $attachment->parent_id,
                'name'                   => $attachment->name,
                'size'                   => $attachment->size,
                'mime_type'              => $attachment->mime_type,
                'meta'                   => $attachment->meta,
                'url'                    => $attachment->signed_url,
            ];
        });
    }
}