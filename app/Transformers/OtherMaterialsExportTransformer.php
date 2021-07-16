<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class OtherMaterialsExportTransformer extends TransformerAbstract
{
	public function transform($product)
    {
    	$data = [
    		'Entity Name'			=> $product->name,
            'Code'                  => $product->code,
            'Description'           => $product->description,
    		'Category'				=> $product->category->name,
    		'Unit'					=> $product->unit,
    		'Unit Cost'				=> $product->unit_cost,
    		'Selling Price'			=> $product->selling_price,
    	];

    	return $data;
    }
}