<?php

namespace  App\Http\CustomerWebPage\Transformers;

use League\Fractal\TransformerAbstract;


class HoverJobModelTransformer extends TransformerAbstract
{
	
	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [''];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($hoverJobModel)
	{
	    return [
	    	'id'           => $hoverJobModel->id,
	    	'company_id'   => $hoverJobModel->company_id,
	    	'job_id'       => $hoverJobModel->job_id,
	    	'hover_job_id' => $hoverJobModel->hover_job_id,
	    	'image_url'    => $hoverJobModel->image_url,
	    	'url'          => $hoverJobModel->url,
	    ];
	}
}