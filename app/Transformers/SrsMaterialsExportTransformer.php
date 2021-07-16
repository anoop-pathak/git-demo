<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SrsMaterialsExportTransformer extends TransformerAbstract
{
	public function transform($product)
    {
    	$data = [
    		'Item Name'			    => $product->name,
    		'Code' 					=> $product->code,
            'Description'           => $product->description,
    		'Unit'					=> $product->unit,
    		'Unit Cost'				=> $product->unit_cost,
    		'Selling Price'			=> $product->selling_price,
            'Branch Name'           => $product->branch,
            'Branch Code'           => $product->branch_code,
            'Color(s)'              => implode(',', (array)$product->colors),
            'Alternate Unit(s)'     => implode(',', (array)$product->alternate_units),
    	];

    	return $data;
    }
}