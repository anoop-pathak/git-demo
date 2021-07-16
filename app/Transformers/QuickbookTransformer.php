<?php
namespace App\Transformers;
use League\Fractal\TransformerAbstract;
class QuickbookTransformer extends TransformerAbstract {

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
	public function transform ($quickbook){
 		return [
            'quickbook_company_id' => $quickbook->quickbook_id,
            'only_one_way_sync' => (bool)$quickbook->only_one_way_sync
        ];
	}
}