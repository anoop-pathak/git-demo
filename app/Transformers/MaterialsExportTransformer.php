<?php 

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class MaterialsExportTransformer extends TransformerAbstract
{
    public function transform($products)
    {
    	$data = [
    		'Item Name'				=> $products->name,
            'Code'                  => $products->code,
            'Description'           => $products->description,
    		'Unit'					=> $products->unit,
    		'Unit Cost'				=> $products->unit_cost,
    		'Selling Price'			=> $products->selling_price,
            'Type / Style(s)'       => implode(',', (array)$products->styles),
            'Size(s)'               => implode(',', (array)$products->sizes),
            'Color(s)'              => implode(',', (array)$products->colors),
    	];

    	return $data;
    }
}