<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class EagleViewOrdersTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'job',
        'reports',
        'status',
        'subStatus',
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
     protected $availableIncludes = [
        'report_files',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($order)
    {
        return [
            'id' => $order->id,
            'address' => $order->address,
            'product_type' => $order->product_type,
            'delivery' => $order->delivery,
            'reportId' => $order->report_id,
            'claim_number' => $order->claim_number,
            'created_at' => $order->created_at,
        ];
    }

    public function includeStatus($order)
    {
        $status = $order->status;
        if ($status) {
            return $this->item($status, function ($status) {
                return [
                    'id' => $status->id,
                    'name' => $status->name
                ];
            });
        }
    }

    public function includeSubStatus($order)
    {
        $subStatus = $order->subStatus;
        if ($subStatus) {
            return $this->item($subStatus, function ($subStatus) {
                return [
                    'id' => $subStatus->id,
                    'name' => $subStatus->name
                ];
            });
        }
    }

    public function includeReports($order)
    {
        $report = $order->pdfReport;
        if ($report) {
            return $this->item($report, new EagleViewReportsTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($order)
    {

        $job = $order->job;
        if ($job) {
            return $this->item($job, new JobsTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReportFiles($order)
    {
        $reports = $order->allReports;
        
        return $this->collection($reports, new EagleViewReportsTransformer);
    }
}
