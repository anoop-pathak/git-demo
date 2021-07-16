<?php

namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;

class ReferralsTransformer extends TransformerAbstract
{
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
    public function transform($referrals)
    {

        return [
            'id' => $referrals->id,
            'name' => $referrals->name,
            'created_at' => (string)$referrals->created_at,
            'updated_at' => (string)$referrals->updated_at,
        ];
    }
}
