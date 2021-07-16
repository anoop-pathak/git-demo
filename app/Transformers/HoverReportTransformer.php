<?php 

namespace App\Transformers;
use League\Fractal\TransformerAbstract;

class HoverReportTransformer extends TransformerAbstract {
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
    public function transform ($report){
     	return [
    		'id'          	 => $report->id,
            'hover_job_id'   => $report->hover_job_id,
    		'file_path'      => \FlySystem::publicUrl(config('jp.BASE_PATH').$report->file_path),
    		'file_name'  	 => $report->file_name,
            'file_mime_type' => $report->file_mime_type,
            'file_size'      => $report->file_size,  
    	];
    }
} 
