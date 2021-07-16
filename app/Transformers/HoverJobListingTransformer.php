<?php 
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use HoverReport;

class HoverJobListingTransformer extends TransformerAbstract {
 	/**
     * List of resources to automatically include
     *
     * @var array
     */
    // protected $defaultIncludes = [];
 	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['hover_reports', 'hover_images', 'report_files'];
     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform ($hover){

        $customerName = $hover->first_name .' ' . $hover->last_name;
        if($hover->capture_request_id) {
            $customerName = $hover->customer_name;
        }

    	return [
    		'id'         => $hover->id,
    		'job_id'     => $hover->job_id,
            'parent_id'  => $hover->parent_id,
            'user_email' => $hover->user_email,
            'user_id'    => $hover->hover_user_id,
            'user_email'  => $hover->user_email,
            'customer_id'    => $hover->customer_id,
            'owner_id'       => $hover->owner_id,
            'deliverable_id' => $hover->deliverable_id,
            'customer_name'  => $customerName,
            'customer_email' => $hover->email,
            'customer_phone'  => $hover->customer_phone,
            'name'   => $hover->name,
            'number' => $hover->number,
            'alt_id'      => $hover->alt_id,
            'state'  => $hover->state,
            'job_address'  => $hover->location_line_1,
            'job_address_line_2'  => $hover->location_line_2,
            'job_city'    => $hover->location_city,
            'job_country' => $hover->location_country,
            'job_state' => $hover->location_region,
            'job_zip_code' => $hover->location_postal_code,
            'is_capture_request' => $hover->is_capture_request,
            'state_id'  => $hover->state_id,
            'country_id' => $hover->country_id,
            'created_at' => $hover->created_at,
            'updated_at' => $hover->updated_at,
    	];
    }
     public function includeHoverReports($hover)
    {
        $reports = $hover->pdfReport;
        if($reports) {
             return $this->item($reports, new HoverReportTransformer);
        }
    }
     public function includeHoverImages($hover)
    {
        $images = $hover->hoverImage;
        if($images) {
             return $this->collection($images, new HoverImageTransformer);
        }
    }
     public function includeReportFiles($hover)
    {
        $reports = $hover->hoverReport;
        
        return $this->collection($reports, new HoverReportTransformer);
    }
 } 