<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobTransformerOptimized;
use League\Fractal\TransformerAbstract;
use FlySystem;

class SkyMeasureOrderTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['job', 'pdf_report', 'report_files'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($order)
    {

        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'status' => $order->status,
            'details' => $order->details,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
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
            $jobTrans = new JobTransformerOptimized;
            $jobTrans->setDefaultIncludes(['customer']);

            return $this->item($job, $jobTrans);
        }
    }

    /**
     * Include Report Files
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReportFiles($order)
    {
        $files = $order->reportsFiles;

        if (sizeof($files)) {
            return $this->collection($files, function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'file_url'  => FlySystem::publicUrl(config('jp.BASE_PATH').$file->path),
                ];
            });
        }
    }
}
