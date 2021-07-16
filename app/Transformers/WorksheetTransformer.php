<?php

namespace App\Transformers;

use App\Models\Worksheet;
use FlySystem;
use League\Fractal\TransformerAbstract;

class WorksheetTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['material_list', 'job_proposal', 'job_estimate', 'work_order'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'custom_tax',
        'suppliers',
        'material_custom_tax',
        'labor_custom_tax',
        'attachments',
        'division',
        'srs_ship_to_address',
        'branch',
        'qbd_queue_status',
        'template_pages_ids'
    ];

    public function transform($worksheet)
    {
        $columnSettings = $worksheet->column_settings;

        return [
            'id' => $worksheet->id,
            'job_id' => $worksheet->job_id,
            'name' => $worksheet->name,
            'title' => $worksheet->title,
            'order' => $worksheet->order,
            'overhead' => $worksheet->overhead,
            'profit' => $worksheet->profit,
            'type' => $worksheet->type,
            'taxable' => $worksheet->taxable,
            'tax_rate' => $worksheet->tax_rate,
            'total' => $worksheet->total,
            'enable_actual_cost' => $worksheet->enable_actual_cost,
            'selling_price_total' => $worksheet->selling_price_total,
            'file_path' => $worksheet->file_path,
            'file_size' => $worksheet->file_size,
            'note' => $worksheet->note,
            'thumb' => ($thumb = $worksheet->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $thumb) : null,
            'custom_tax_id' => $worksheet->custom_tax_id,
            'hide_pricing' => $worksheet->hide_pricing,
            'show_tier_total'     => $worksheet->show_tier_total,
            'enable_selling_price' => (bool)$worksheet->enable_selling_price,
            'material_tax_rate' => $worksheet->material_tax_rate,
            'labor_tax_rate' => $worksheet->labor_tax_rate,
            'commission' => $worksheet->commission,
            're_calculate' => (bool)$worksheet->re_calculate,
            'multi_tier' => (bool)$worksheet->multi_tier,
            'margin' => $worksheet->margin,
            'created_at' => $worksheet->created_at,
            'updated_at' => $worksheet->updated_at,
            'material_custom_tax_id' => $worksheet->material_custom_tax_id,
            'labor_custom_tax_id' => $worksheet->labor_custom_tax_id,
            'description_only' => $worksheet->description_only,
            'hide_customer_info' => $worksheet->hide_customer_info,
            'show_quantity' => $worksheet->show_quantity,
            'insurance_meta' => $worksheet->insurance_meta,
            'show_unit' => $worksheet->show_unit,
            'line_tax' => $worksheet->line_tax,
            'line_margin_markup' => $worksheet->line_margin_markup,
            'branch_code'            => $worksheet->branch_code,
            'ship_to_sequence_number' => $worksheet->ship_to_sequence_number,
            'update_tax_order'        => $worksheet->update_tax_order,
            'sync_on_qbd'             => (bool)$worksheet->sync_on_qbd_by,
            'is_qbd_worksheet'        => $worksheet->is_qbd_worksheet,
            'pages_exist'             => $worksheet->pages_exist,
            'pages_required'          => $worksheet->pages_required,
            'show_calculation_summary' => $worksheet->show_calculation_summary,
            'show_line_total'         => (bool)$worksheet->show_line_total,
            'srs_old_worksheet'       => (bool)$worksheet->srs_old_worksheet,
            'collapse_all_line_items' => (bool)$worksheet->collapse_all_line_items,
            'fixed_price'             => $worksheet->fixed_price,
            'enable_job_commission'   => $worksheet->enable_job_commission,
            'show_style'              => ine($columnSettings, 'show_style'),
            'show_size'               => ine($columnSettings, 'show_size'),
            'show_color'              => ine($columnSettings, 'show_color'),
            'show_supplier'           => ine($columnSettings, 'show_supplier'),
            'show_trade_type'         => ine($columnSettings, 'show_trade_type'),
            'show_work_type'          => ine($columnSettings, 'show_work_type'),
            'show_tier_color'         => ine($columnSettings, 'show_tier_color'),
        ];
    }

    /**
     * Include Material List
     * @param response
     */
    public function includeMaterialList($worksheet)
    {
        $materialList = $worksheet->materialList;
        if ($materialList) {
            return $this->item($materialList, new MaterialListTransformer);
        }
    }

    /**
     * Include Job Proposal
     * @param  Instance $worksheet Worksheet
     * @return Response
     */
    public function includeJobProposal($worksheet)
    {
        $proposal = $worksheet->jobProposal;
        if ($proposal) {
            return $this->item($proposal, new ProposalsTransformer);
        }
    }

    /**
     * Inclue Job Estimate
     * @param  Instance $worksheet worksheet
     * @return Response
     */
    public function includeJobEstimate($worksheet)
    {
        $estimate = $worksheet->jobEstimate;
        if ($estimate) {
            return $this->item($estimate, new EstimationsTransformer);
        }
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
     * Include Material Custom Tax
     * @param  Worksheet Instance $worksheet Worksheet
     * @return Custom Tax
     */
    public function includeMaterialCustomTax($worksheet)
    {
        $customTax = $worksheet->materialCustomTax;
        if ($customTax) {
            return $this->item($customTax, new CustomTaxesTransformer);
        }
    }

    /**
     * Include Labor Custom Tax
     * @param  Worksheet Instance $worksheet Worksheet
     * @return Custom Tax
     */
    public function includeLaborCustomTax($worksheet)
    {
        $customTax = $worksheet->laborCustomTax;
        if ($customTax) {
            return $this->item($customTax, new CustomTaxesTransformer);
        }
    }

    /**
     * Include Work Order
     * @param  Instance $worksheet WorkSheet
     * @return Response
     */
    public function includeWorkOrder($worksheet)
    {
        $workOrder = $worksheet->workOrder;
        if ($workOrder) {
            return $this->item($workOrder, new MaterialListTransformer);
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

    public function includeAttachments($worksheet)
    {
        $attachments = $worksheet->attachments;

        if ($attachments) {
            return $this->collection($attachments, function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'url' => FlySystem::publicUrl(config('jp.BASE_PATH') . $attachment->path),
                    'thumb_url' => Flysystem::publicUrl(config('jp.BASE_PATH') . $attachment->thumb),
                ];
            });
        }
    }

    /**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivision($worksheet)
    {
        $division = $worksheet->division;
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

    public function includeQbdQueueStatus($worksheet)
    {
        $qbdQueue = $worksheet->qbDesktopQueue;
        if($qbdQueue) {
            return $this->item($qbdQueue, new QBDesktopQueueTransformer);
        }
    }

    /**
     * Include TemplatePage Ids
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
}
