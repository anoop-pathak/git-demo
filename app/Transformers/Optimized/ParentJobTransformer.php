<?php
namespace App\Transformers\Optimized;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;

class ParentJobTransformer extends TransformerAbstract
{

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
    protected $availableIncludes = ['division'];

    public function transform($job) {
        return [
            'id'               => $job->id,
            'number'           => $job->number,
            'alt_id'           => $job->alt_id,
            'lead_number'      => $job->lead_number,
            'division_code'    => $job->division_code,
        ];
    }

    /**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivision($job) {
        $division = $job->division;

        if($division){
            return $this->item($division, new DivisionsTransformerOptimized);
        }
    }
}