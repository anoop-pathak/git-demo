<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SubContractorRateSheetTransformer extends TransformerAbstract
{

    public function transform($sheetDetail)
    {
        return [
            'id' => $sheetDetail->id,
            'name' => $sheetDetail->name,
            'unit' => $sheetDetail->unit,
            'unit_cost' => $sheetDetail->unit_cost,
            'description' => $sheetDetail->description,
            'selling_price' => $sheetDetail->selling_price,
            'code' => $sheetDetail->code,
        ];
    }
}
