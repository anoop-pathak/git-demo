<?php 

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOtimized;
use HoverReport;

class HoverTransformer extends TransformerAbstract {
 	/**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['hover_reports', 'hover_images'];
 	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['measurement', 'report_files', 'hover_user'];
     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform ($hover){

    	return [

            'id'         => $hover->id,
            'job_id'     => $hover->job_id,
    		'hover_job_id' => $hover->hover_job_id,
            'user_email' => $hover->user_email,
            'user_id'    => $hover->hover_user_id,
            'customer_id'    => $hover->customer_id,
            'owner_id'       => $hover->owner_id,
            'deliverable_id' => $hover->deliverable_id,
            'customer_name'  => $hover->customer_name,
            'customer_email' => $hover->customer_email,
            'customer_phone'  => $hover->customer_phone,
            'name'   => $hover->name,
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
     public function includeReportFiles($hover)
    {
        $reports = $hover->hoverReport;
        
        return $this->collection($reports, new HoverReportTransformer);
    }
     public function includeHoverImages($hover)
    {
        $images = $hover->hoverImage;
        if($images) {
            return $this->collection($images, new HoverImageTransformer);
        }
    }
     public function includeMeasurement($hover)
    {
        $measurement = $hover->measurement;
         if($measurement) {
             return $this->item($measurement, new MeasurementTransformer);   
        }
    }

    public function includeHoverUser($hover)
    {
        $hoverUser = $hover->hoverUser;
        if($hoverUser) {
            return $this->item($hoverUser, function($user){
                return [
                    'first_name'    => $user->first_name,
                    'last_name'     => $user->last_name,
                    'hover_user_id' => $user->hover_user_id,
                    'email'         => $user->email,  
                    'aasm_state'    => $user->aasm_state,
                    'acl_template'  => $user->acl_template,
                ];
            });   
        }
    }
 } 