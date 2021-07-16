<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class TiersTransformer extends TransformerAbstract
{

    public function transform($tier)
    {
        return [
            'id' => (int)$tier->id,
            'name' => (string)$tier->name,
        ];
    }
}
