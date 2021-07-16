<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class LaborsExportTransformer extends TransformerAbstract
{
    public function transform($product)
    {
    	$data = [
    		'Item Name'				=> $product->name,
            'Code'                  => $product->code,
            'Description'           => $product->description,
    		'Unit'					=> $product->unit,
    		'Unit Cost'				=> $product->unit_cost,
    		'Selling Price'			=> $product->selling_price,
    	];

    	return $data;
    }
}