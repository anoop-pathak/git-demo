<?php

namespace App\Http\CustomerWebPage\Transformers;

use App\Transformers\HoverTransformer as HoverTransformer;

class HoverJobTransformer extends HoverTransformer
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
    public function transform($hoverJob)
    {
        $data = [
            'id'         => $hoverJob->id,
            'company_id' => $hoverJob->company_id,
            'job_id'     => $hoverJob->job_id,
            'hover_job_id' => $hoverJob->hover_job_id,
            'state'        => $hoverJob->state,
            'owner_id'     => $hoverJob->owner_id,
            'owner_id'     => $hoverJob->owner_id,
            'is_capture_request'  => (int)(bool)$hoverJob->capture_request_id,
            'capture_request_url' => $hoverJob->getCaptureRequetUrl()
        ];
        
        return $data;
    }

}
