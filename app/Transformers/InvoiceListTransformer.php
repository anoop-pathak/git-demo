<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class InvoiceListTransformer extends TransformerAbstract {

	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
    protected $defaultIncludes = ['job_division'];

	public function transform($invoice)
	{
		$job = $invoice->job;

		return [
			'job_id'		=>	$invoice->job_id,
			'title'         =>  $invoice->title,
			'name'         	=>  $invoice->name,
			'customer_id'   =>  $invoice->customer_id,
			'invoice_id'	=>	$invoice->id,
			'customer_name'	=>	$invoice->customer->full_name,
			'status'        =>  $invoice->status,
			'invoice_amount'=>	$invoice->amount,
			'tax_rate'		=>	$invoice->tax_rate,
			'total_amount'	=>	$invoice->total_amount,
			'open_balance'	=>	$invoice->getOpenBalanceAttribute(),
			'job_number'	=>	$job->number,
			'job_alt_id'	=>	$job->alt_id,
			'parent_id'		=>	$job->parent_id,
			'job_name'		=>	$job->name,
			'job_trades'	=>	$job->trades,
			'created_at'    =>  $invoice->created_at, 
			'updated_at'    =>  $invoice->updated_at,
		];
	}

	/**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobDivision($invoice)
    {
        $division = $invoice->job->division;
        if($division){

            return $this->item($division, new DivisionTransformer);
        }
    }
}