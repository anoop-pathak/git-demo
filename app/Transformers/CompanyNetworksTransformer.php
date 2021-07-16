<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class CompanyNetworksTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['network_meta'];

    public function transform($companyNetwork)
    {
        return [
            'id' => (int)$companyNetwork->id,
            'network' => $companyNetwork->network,
            'is_connected' => true
        ];
    }

    /**
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeNetworkMeta($companyNetwork)
    {
        $networkMeta = $companyNetwork->networkMeta;
        if ($networkMeta) {
            $metaValue = $networkMeta->meta_value;
            return $this->collection((array)$metaValue, new FacebookPageTransformer);
        }
    }
}
