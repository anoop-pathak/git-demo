<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;

class JobPriceRequestTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['rejected_by', 'approved_by', 'requested_by', 'custom_tax'];
 	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($jobPriceRequest)
    {
		return [
            'id'     		=>  $jobPriceRequest->id,
            'job_id'   		=>  $jobPriceRequest->job_id,
			'amount' 		=>  $jobPriceRequest->amount,
            'taxable'       =>  $jobPriceRequest->taxable,
            'tax_rate'      =>  $jobPriceRequest->tax_rate,
        ];
	}
     /**
     * Include Appointment Count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeApprovedBy($jobPriceRequest) {
        $user = $jobPriceRequest->approvedBy;
        if($user){
           return $this->item($user, new UsersTransformerOptimized);
        }
    }

    public function includeRejectedBy($jobPriceRequest) {
        $user = $jobPriceRequest->rejectedBy;
        if($user){
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    public function includeRequestedBy($jobPriceRequest) {
        $user = $jobPriceRequest->requestedBy;
        if($user){
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    public function includeCustomTax($invoice)
    {
        $customTax = $invoice->customTax;
        if($customTax) {

            return $this->item($customTax, new CustomTaxesTransformer);
        }
    }

}