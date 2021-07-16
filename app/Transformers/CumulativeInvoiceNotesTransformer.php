<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;

class CumulativeInvoiceNotesTransformer extends TransformerAbstract {

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
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($cumulativeInvoiceNote) {
        
		return [
            'job_id'     =>  $cumulativeInvoiceNote->job_id,
			'note'       =>  $cumulativeInvoiceNote->note,
            'created_at' =>  $cumulativeInvoiceNote->created_at,
            'updated_at' =>  $cumulativeInvoiceNote->updated_at,
		];
	}

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($cumulativeInvoiceNote) {
        $job = $cumulativeInvoiceNote->job;
        if($job){

            return $this->item($job, new JobsTransformerOptimized);    
        }
    }
}