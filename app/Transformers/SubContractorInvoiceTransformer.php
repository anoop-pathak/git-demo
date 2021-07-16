<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SubContractorInvoiceTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($invoice)
    {

        return [
            'id' => $invoice->id,
            'file_name' => $invoice->file_name,
            'file_url' => $invoice->getFilePath(),
            'thumb_url' => $invoice->getThumb(),
            'size' => $invoice->size,
            'mime_type' => $invoice->mime_type,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ];
    }
}
