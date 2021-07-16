<?php

namespace App\Transformers;

use App\Models\Measurement;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use App\Models\Folder;
use App\Models\Measurement;

class MeasurementTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['measurement_details', 'ev_order', 'sm_order','created_by', 'hover_job'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($measurement)
    {
        if($measurement instanceof Folder) {
			return [
				'id'                =>  $measurement->id,
				'parent_id'         =>  $measurement->parent_id,
				'company_id' 	    =>  $measurement->company_id,
				'job_id' 	        =>  $measurement->job_id,
				'title'             =>  $measurement->name,
				'is_dir'            =>  $measurement->is_dir,
				'created_by'        =>  $measurement->created_by,
				'updated_by'        =>  $measurement->updated_by,
				'created_at'        =>  $measurement->created_at,
                'updated_at'        =>  $measurement->updated_at,
                'no_of_child'       =>  $measurement->children->count(),
            ];
        }

        return [
            'id'	            => $measurement->id,
            'parent_id'         => $measurement->parent_id,
            'title'             => $measurement->title,
            'type'              => $measurement->type,
            'is_file'           => $measurement->is_file,
            'job_id'            => $measurement->job_id,
            'file_name'         => str_replace(' ', '_', $measurement->file_name),
            'file_path'         =>  $measurement->getFilePath(),
            'file_mime_type'    => $measurement->file_mime_type,
            'file_size'         => $measurement->file_size,
            'ev_report_id'      => $measurement->ev_report_id,
            'sm_order_id'       => $measurement->sm_order_id,
            'thumb'             => $measurement->getThumb(),
            'total_values'      => $measurement->total_values,
            'created_at'        => $measurement->created_at,
            'updated_at'        => $measurement->updated_at,
            'is_dir'            => false,
    	];
    }

    /**
     * Include Measurement Details
     * @param  Instance $measurement Measuremnt
     * @return Measurement Details
     */
    public function includeMeasurementDetails($measurement)
    {
        if($measurement instanceof Measurement) {
            $trades = $measurement->trades;

            //trades use only for single measurement
            if ($trades) {
                $tradeTrans = new TradesTransformer;
                $tradeTrans->setDefaultIncludes([
                    'values',
                    'measurement_values_summary',
                ]);

                return $this->collection($trades, $tradeTrans);
            }
        }
    }

    /**
     * Include Skymeasure Order
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSmOrder($measurement)
    {
        if($measurement instanceof Measurement) {
            $order = $measurement->smOrder;
            if ($order) {
                $skyMeasureTrans = new SkyMeasureOrderTransformer;
                $skyMeasureTrans->setDefaultIncludes([]);

                return $this->item($order, $skyMeasureTrans);
            }
        }
    }

    /**
     * Include Eagleview Order
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEvOrder($measurement)
    {
        if($measurement instanceof Measurement) {
            $order = $measurement->evOrder;
            if ($order) {
                return $this->item($order, new EagleViewOrdersTransformer);
            }
        }
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($measurement) {
        $user = $measurement->createdBy;
        if($user){
            return $this->item($user, new UsersTransformerOptimized);
        }
    }
     /**
     * Include hover jobs
     *
     * @return League\Fractal\ItemResource
     */
    public function includeHoverJob($measurement)
    {
        if($measurement instanceof Measurement) {
            $hoverJob = $measurement->hoverJob;
            if($hoverJob){
                return $this->item($hoverJob, new HoverTransformer);
            }
        }
    }
}
