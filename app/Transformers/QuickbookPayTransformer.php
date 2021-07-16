<?php 
namespace App\Transformers;
use League\Fractal\TransformerAbstract;
class QuickbookPayTransformer extends TransformerAbstract {
	
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
			'quickbooks_payments_connected' => $quickbook->is_payments_connected
        ];
	}
}