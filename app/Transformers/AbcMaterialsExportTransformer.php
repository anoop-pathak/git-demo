<?php

namespace App\Transformers;
use League\Fractal\TransformerAbstract;

class AbcMaterialsExportTransformer extends TransformerAbstract
{
	public function transform($product)
    {
    	$data = [
    		'Item Name'			=> 	$product->name,
    		'Code'				=> 	$product->code,
	   		'Description'		=> 	$product->description,
            'Unit'              =>  $product->unit,
    		'Unit/Estimating'	=> 	$product->abc_additional_data ? $product->abc_additional_data->estimatin : '',
    		'Unit/Purchase'		=> 	$product->abc_additional_data->purch ? $product->abc_additional_data->purch : '',
            'Unit Cost'			=> 	$product->unit_cost,
            'Selling Price' 	=> 	$product->selling_price,
    	];

    	return $data;
    }
}
