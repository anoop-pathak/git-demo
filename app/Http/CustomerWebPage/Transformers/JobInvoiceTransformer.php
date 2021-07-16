<?php

namespace App\Http\CustomerWebPage\Transformers;

use FlySystem;
use App\Http\CustomerWebPage\Transformers\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class JobInvoiceTransformer extends TransformerAbstract
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
    public function transform($invoice)
    {

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'title' => $invoice->title,
            'file_path' => FlySystem::publicUrl(config('jp.BASE_PATH') . $invoice->file_path),
            'open_balance' => $invoice->open_balance,
            'status' => $invoice->status,
            'amount' => $invoice->amount,
            'tax_rate' => $invoice->tax_rate,
            'total_amount' => $invoice->total_amount,
            'name' => $invoice->name
        ];
    }
}
