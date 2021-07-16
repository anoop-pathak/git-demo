<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class PageTableCalculationTransformer extends TransformerAbstract {

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
	public function transform ($pageCalculation){

		return [
            'name'      => $pageCalculation->name,
            'ref_id'    => $pageCalculation->ref_id,
            'head'      => $pageCalculation->head,
            'body'      => $pageCalculation->body,
            'foot'      => $pageCalculation->foot,
            'options'   => $pageCalculation->options
        ];
	}
}