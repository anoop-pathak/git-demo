<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QBDesktopProductTransformer extends TransformerAbstract {

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
    public function transform ($qbDesktopProduct){

       	return [
    		'name'			  => $qbDesktopProduct->name,
    		'price'			  => (int)$qbDesktopProduct->price,
    		'uom_name'	      => $qbDesktopProduct->uom_name,
            'account_name'    => $qbDesktopProduct->account_name,
            'qb_desktop_id'   => $qbDesktopProduct->list_id,
        ];

    }
}