<?php namespace App\Transformers\Optimized;

use App\Models\Worksheet;
use App\Transformers\CustomTaxesTransformer;
use App\Transformers\SRSShipToAddressesTransformer;
use App\Transformers\SupplierBranchesTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\QBDesktopQueueTransformer;

class WorksheetTransformerOptimized extends TransformerAbstract
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
    protected $availableIncludes = ['custom_tax', 'suppliers', 'srs_ship_to_address', 'branch', 'qbd_queue_status', 'template_pages_ids'];

    public function transform($worksheet)
    {
        return [
            'id' => $worksheet->id,
            'name' => $worksheet->name,
            'taxable' => $worksheet->taxable,
            'tax_rate' => $worksheet->tax_rate,
            'overhead' => $worksheet->overhead,
            'profit' => $worksheet->profit,
            'total' => $worksheet->total,
            'custom_tax_id' => $worksheet->custom_tax_id,
            'selling_price_total' => $worksheet->selling_price_total,
            'enable_selling_price' => (bool)$worksheet->enable_selling_price,
            'material_tax_rate' => $worksheet->material_tax_rate,
            'labor_tax_rate' => $worksheet->labor_tax_rate,
            'commission' => $worksheet->commission,
            're_calculate' => (bool)$worksheet->re_calculate,
            'meta' => $worksheet->meta,
            'material_custom_tax_id' => $worksheet->material_custom_tax_id,
            'labor_custom_tax_id' => $worksheet->labor_custom_tax_id,
            'insurance_meta' => $worksheet->insurance_meta,
            'line_tax'               => $worksheet->line_tax,
            'line_margin_markup'     => $worksheet->line_margin_markup,
            'margin'                 => $worksheet->margin,
            'update_tax_order'       => $worksheet->update_tax_order,
            'pages_exist'             => $worksheet->pages_exist,
            'sync_on_qbd'            => (bool)$worksheet->sync_on_qbd_by,
            'is_qbd_worksheet'       => $worksheet->is_qbd_worksheet,
            'pages_required'          => $worksheet->pages_required,
            'show_calculation_summary' => $worksheet->show_calculation_summary,
            'show_line_total'         => (bool)$worksheet->show_line_total,
            'srs_old_worksheet'      => (bool)$worksheet->srs_old_worksheet,
            'fixed_price'             => $worksheet->fixed_price,
        ];
    }

    /**
     * Include Custom Tax
     * @param  Worksheet Instance $worksheet Worksheet
     * @return Custom Tax
     */
    public function includeCustomTax($worksheet)
    {
        $customTax = $worksheet->customTax;
        if ($customTax) {
            return $this->item($customTax, new CustomTaxesTransformer);
        }
    }

    /**
     * Include Suppliers
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSuppliers($worksheet)
    {
        $suppliers = $worksheet->suppliers;

        if ($suppliers) {
            return $this->collection($suppliers, function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                ];
            });
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
            return $this->item($shipToAddresses, new SRSShipToAddressesTransformer);
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
            return $this->item($branch, new SupplierBranchesTransformer);
        }
    }

    /**
     * Include Template Page ids
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTemplatePagesIds($worksheet)
    {
        $pagesIds = $worksheet->templatePages->pluck('id')->toArray();
        return $this->item($pagesIds, function($pagesIds) {
            return $pagesIds;
        });
    }

    /**
     * Include QBD Queue Status
     *
     * @return League\Fractal\ItemResource
     */
    public function includeQbdQueueStatus($worksheet)
    {
        $queue = $worksheet->qbDesktopQueue;
        if($queue) {
            return $this->item($queue, new QBDesktopQueueTransformer);;
        }
    }
}
