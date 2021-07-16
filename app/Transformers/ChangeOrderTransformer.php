<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class ChangeOrderTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['job', 'invoice', 'custom_tax', 'division', 'branch', 'srs_ship_to_address'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($changeOrder)
    {
        return [
            'id' => $changeOrder->id,
            'job_id' => $changeOrder->job_id,
            'total_amount' => $changeOrder->total_amount,
            'taxable' => $changeOrder->taxable,
            'tax_rate' => $changeOrder->tax_rate,
            'quickbook_invoice_id' => isset($changeOrder->invoice->quickbook_invoice_id) ? $changeOrder->invoice->quickbook_invoice_id : null,
            'invoice_id' => $changeOrder->invoice_id,
            'invoice_created' => (bool)$changeOrder->invoice_id,
            'canceled' => $changeOrder->canceled,
            'order' => $changeOrder->order,
            'entities' => $changeOrder->entities,
            'invoice_updated' => $changeOrder->invoice_updated,
            // 'is_old'               =>   $changeOrder->isOld(),
            'created_at' => $changeOrder->created_at,
            'updated_at' => $changeOrder->updated_at,
            'invoice_note' => $changeOrder->invoice_note,
            'name'                 =>   $changeOrder->name,
            'unit_number'          =>   $changeOrder->unit_number,
            'division_id'          =>   $changeOrder->division_id,
        ];
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($changeOrder)
    {
        $job = $changeOrder->job;

        if ($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes(['trades', 'work_types']);

            return $this->item($job, $transformer);
        }
    }

    /**
     * Include Invoice
     * @param  Change Order $changeOrder change Order
     * @return Response
     */
    public function includeInvoice($changeOrder)
    {
        $invoice = $changeOrder->invoice;
        if ($invoice) {
            return $this->item($invoice, new JobInvoiceTransformer);
        }
    }

    /**
     * Include Custom Tax
     * @param  change Order Instance Change Order
     * @return Custom Tax
     */
    public function includeCustomTax($changeOrder)
    {
        $customTax = $changeOrder->customTax;
        if ($customTax) {
            return $this->item($customTax, new CustomTaxesTransformer);
        }
    }

    /**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivision($changeOrder)
    {
        $division = $changeOrder->division;
        if($division){
            return $this->item($division, new DivisionTransformer);
        }
    }
    /**
     * Include Ship to Address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSRSShipToAddress($worksheet) 
    {
        $shipToAddresses = $worksheet->srsShipToAddresses;
        if($shipToAddresses) {
            return $this->item($shipToAddresses, new SRSShipToAddressesTransformer);;
        }
    }
    /**
     * Include Branch
     *
     * @return League\Fractal\ItemResource
     */
    public function includeBranch($worksheet) 
    {
        $branch = $worksheet->branch;
        if($branch) {
            return $this->item($branch, new SupplierBranchesTransformer);;
        }
    }
}
