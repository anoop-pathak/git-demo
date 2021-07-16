<?php
namespace App\Transformers;
use League\Fractal\TransformerAbstract;

class FinancialDetailsTransformer extends TransformerAbstract
{
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
	public function transform($financialDetails)
    {
		return [
            'id'     	=>  $financialDetails->id,
            'type'   	=>  $financialDetails->type,
			'name' 		=>  $financialDetails->name,
            'created_at'=>  $financialDetails->created_at,
            'updated_at'=>  $financialDetails->updated_at,
        ];
	}

}