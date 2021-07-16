<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SalesTaxReportTransformer extends TransformerAbstract {

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
    public function transform($job) {
      $data = [
            'job_id'                            =>   $job->id,
            'job_number'                        =>   $job->number,
            'job_name'                          =>   $job->name,
            'job_alt_id'                        =>   $job->alt_id,
            'multi_job'                         =>   $job->multi_job,
            'parent_id'                         =>   $job->parent_id,
            'division_id'                       =>   $job->division_id,
            'current_stage'                     =>   $job->getCurrentStage(),
            'customer_id'                       =>   $job->customer->id,
            'customer_name'                     =>   $job->customer->full_name,
            'job_price'                         =>   $job->amount,
            'tax_rate'                          =>   $job->tax_rate,
            'tax_amount'                        =>   $this->jobTaxAmount($job),
            'job_price_including_tax'           =>   $this->jobPriceIncludingTax($job),
            'total_job_invoice_amount'          =>   $this->jobInvoiceAmount($job),
            'total_change_order_invoice_amount' =>   $this->changeOrderInvoiceAmount($job)
        ];

        return $data;
    }

    public function jobTaxAmount($job)
    {
        //Calculate Tax Amount
        $taxAmount = $job->amount * $job->tax_rate /100;

        return $taxAmount;
    }

    public function jobPriceIncludingTax($job)
    {
        //Calculate JobPrice with Tax
        $financialCalculations = $job->financialCalculation;
        $totalJobPrice = $financialCalculations->total_job_amount;

        return $totalJobPrice;
    }

    public function jobInvoiceAmount($job)
    {
        $financialCalculations = $job->financialCalculation;
        $invoiceAmount = $financialCalculations->job_invoice_amount;
        $invoiceTaxAmount = $financialCalculations->job_invoice_tax_amount;
        $totalInvoiceAmount = $invoiceAmount + $invoiceTaxAmount;

        return $totalInvoiceAmount;
    }

    public function changeOrderInvoiceAmount($job)
    {
        $financialCalculations = $job->financialCalculation;
        $changeOrderAmount = $financialCalculations->total_change_order_invoice_amount;

        return $changeOrderAmount;
    }

}