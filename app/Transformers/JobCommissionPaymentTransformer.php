<?php 
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\JobCommissionsTransformer;

class JobCommissionPaymentTransformer extends TransformerAbstract
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
     protected $availableIncludes = ['commission'];
      /**
     * Turn this item object into a generic array
     *
     * @return array
     */
      public function transform($payment)
      {
      	return [
      		'id'            => $payment->id,
      		'job_id'        => $payment->job_id,
      		'commission_id' => $payment->commission_id,
      		'amount'	      => $payment->amount,
      		'paid_by'       => $payment->paid_by,
      		'paid_on'       => $payment->paid_on,
      		'canceled_at'   => $payment->canceled_at,
      	];
      }
 	/**
   * Include Commission
   *
   * @return League\Fractal\ItemResource
   */
 	public function includeCommission($payment)
 	{
 		$commission = $payment->jobCommission;
 		if($commission) {
 			$transformer = (new JobCommissionsTransformer)->setDefaultIncludes([]);
 			return $this->item($commission, $transformer);
 		}
 	}
 } 