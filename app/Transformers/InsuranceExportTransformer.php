<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class InsuranceExportTransformer extends TransformerAbstract
{
    public function transform($product)
    {
    	$data = [
    		'Description'	=> $product->description,
    		'Code' 			=> $product->code,
    		'Trade Type'	=> $product->trade->name,
    		'Unit'			=> $product->unit,
    		'Unit Cost'		=> $product->unit_cost,
    	];

        return $data;
    }
}
