<?php

namespace App\Http\OpenAPI\Transformers;

use FlySystem;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
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
    protected $availableIncludes = ['proposal', 'lines', 'custom_tax', 'division', 'branch', 'srs_ship_to_address', 'job'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($invoice)
    {
        return [
            'id'                    => $invoice->id,
            'customer_id'           => $invoice->customer_id,
            'job_id'                => $invoice->job_id,
            'invoice_number'        => $invoice->invoice_number,
            'title'                 => $invoice->title,
            'description'           => $invoice->description,
            'file_path'             => $invoice->signed_url,
            'file_size'             => $invoice->file_size,
            'created_at'            => $invoice->created_at,
            'updated_at'            => $invoice->updated_at,
            'open_balance'          => $invoice->open_balance,
            'status'                => $invoice->status,
            'amount'                => $invoice->amount,
            'tax_rate'              => $invoice->tax_rate,
            'total_amount'          => $invoice->total_amount,
            'tax_rate'              => $invoice->tax_rate,
            'due_date'              => $invoice->due_date,
            'date'                  => $invoice->date,
            'type'                  => $invoice->type,
            'note'                  => $invoice->note,
            'name'                  => $invoice->name,
            'unit_number'           => $invoice->unit_number,
            'origin'                => $invoice->originName(),
            'last_updated_origin'   => $this->getOrigin($invoice->last_updated_origin),
            'taxable_amount'        => $invoice->taxable_amount,
        ];
    }

    public function getOrigin($origin)
    {
        if($origin == 0) {
            return  "JobProgress";
        }

        if($origin == 1) {
            return  "QuickBooks";
        }

        return null;
    }

    /**
     * Include Proposals
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProposal($invoice)
    {
        $proposal = $invoice->proposal;

        if ($proposal) {
            $proposalTrans = new ProposalsTransformer;
            $proposalTrans->setDefaultIncludes([]);

            return $this->item($proposal, $proposalTrans);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($invoice)
    {
        $job = $invoice->job;

        if ($job) {
            $trans = new JobsTransformerOptimized;
            $trans->setDefaultIncludes(['customer', 'address']);

            return $this->item($job, $trans);
        }
    }

    public function includeLines($invoice)
    {
        $lines = $invoice->lines;

        if ($lines) {
            $linesTrans = new JobInvoiceLinesTransformer;
            $linesTrans->setDefaultIncludes(['work_type', 'trade']);

            return $this->collection($lines, $linesTrans);
        }
    }

    public function includeCustomTax($invoice)
    {
        $customTax = $invoice->customTax;
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
