<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QBDesktopAccountTransformer extends TransformerAbstract {

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
    public function transform ($account){

    	return [
            'qb_desktop_id' => $account->list_id,
            'account_type'  => $account->account_type,
    		'name'          => $account->name,
            'desciption'    => $account->desciption,
        ];

    }
}