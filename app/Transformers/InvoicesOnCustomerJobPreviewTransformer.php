<?php 
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class InvoicesOnCustomerJobPreviewTransformer extends TransformerAbstract
{	
	public function transform($invoice)
	{
		return [
			'id' => $invoice->id,
			'open' => $invoice->isOpen,
			'title' => $invoice->title,
			'total_amount' => currencyFormat($invoice->total_amount),
			'open_balance' => currencyFormat($invoice->open_balance),
			'invoice_link' => route('jobs.invoice', ['id' => $invoice->id])
		];
	}
} 