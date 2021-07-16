<?php

namespace App\Repositories;

use App\Models\Measurement;
use App\Models\MeasurementValue;
use App\Services\Contexts\Context;
use Carbon\Carbon;

class MeasurementRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(Measurement $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }


    public function listing($jobId, $filters = [])
    {
        $includes = $this->getIncludeData($filters);
        $measurements =  $this->make($includes)->sortable()->where('job_id', $jobId);
        $this->applyFilters($measurements, $filters);
        // if(\Auth::user()->isSubContractorPrime()) {
        //  $measurements->whereCreatedBy(\Auth::id());
        // }
        return $measurements;
    }

    public function save($jobId, $title, $values, $createdBy, $meta = [])
    {
        $measurement = new Measurement;
        $measurement->job_id = $jobId;
        $measurement->title = $title;
        $measurement->company_id = getScopeId();
        $measurement->created_by = $createdBy;
        $measurement->sm_order_id = issetRetrun($meta, 'sm_order_id') ?: null;
        $measurement->ev_report_id = issetRetrun($meta, 'ev_report_id') ?: null;
        $measurement->total_values = count($values);
        $measurement->hover_job_id = ine($meta, 'hover_job_id') ? $meta['hover_job_id'] : null;
        $measurement->is_file = ine($meta, 'is_file');
        $measurement->file_name =  issetRetrun($meta, 'file_name') ?: null;
        $measurement->file_path = issetRetrun($meta, 'file_path') ?: null;
        $measurement->file_mime_type = issetRetrun($meta, 'file_mime_type') ?: null;
        $measurement->file_size = issetRetrun($meta, 'file_size') ?: null;
        $measurement->thumb     = issetRetrun($meta, 'thumb') ?: null;
        $measurement->save();

        $this->saveValues($measurement, $values);

        if(!strlen($title)) {
            $measurement->generateName();
        }

        return $measurement;
    }

    /**
    * update measurement
    *
    * @param $measurement, $title, $values
    * @return true
    */
    public function update($measurement, $title, $values)
    {
        $measurement->title = $title;
        $measurement->total_values = count($values);
        $measurement->is_file =  !(int)count($values);
        $measurement->save();
        $measurement->values()->delete();

        $this->saveValues($measurement, $values);

        return $measurement;
    }

    /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = array())
    {
        $query = $this->make($with);
        return $query->findOrFail($id);
    }

    public function saveValues($measurement, $values)
	{
		$msValues = [];
		foreach ($values as $index => $value) {
			$valuesData = $this->getValuesData($measurement, $value['trade_id'], $index, $value['attributes']);
			$msValues = array_merge($msValues, $valuesData);
		}

		if(!empty($msValues)) {
			MeasurementValue::insert($msValues);
		}

		return $measurement;
	}

    /********** Private Functions **********/

    private function getIncludeData($filters)
    {
        $with = [];
        if(!ine($filters, 'includes')) return $with;
        $includes = $filters['includes'];

        if(in_array('created_by', $includes))    {
            $with[] = 'createdBy.profile';
        }

        if(in_array('linked_measurement', $includes)) {
            $with[] = 'linkedEstimate.linkedMeasurement';
        }

        if(in_array('hover_job', $includes)) {
            $with[] = 'hoverJob';
        }

        if(in_array('hover_job.report_files', $includes)) {
            $with[] = 'hoverJob.hoverReport';
        }

        if(in_array('sm_order', $includes)) {
            $with[] = 'smOrder';
        }

        if(in_array('sm_order.report_files', $includes)) {
            $with[] = 'smOrder.reportsFiles';
        }

        if(in_array('ev_order', $includes)) {
            $with[] = 'evOrder';
        }

        if(in_array('ev_order.report_files', $includes)) {
            $with[] = 'evOrder.allReports';
        }

        if(in_array('hover_job.hover_images', $includes)) {
            $with[] = 'hoverJob.hoverImage';
        }

        return $with;
    }

    private function applyFilters($query, $filters = array())
	{
        if(ine($filters, 'measurement_ids')) {
			$query->whereIn('measurements.id', arry_fu((array) $filters['measurement_ids']));
        }

		if(ine($filters, 'ids')) {
			$query->whereIn('measurements.id', arry_fu((array) $filters['ids']));
        }

        if(ine($filters, 'exclude_files')) {
			$query->whereIsFile(false);
		}
    }

    private function getValuesData($measurement, $tradeId, $index, $attributes, $parentId = null)
	{
		$msValues = [];
		$now = Carbon::now()->toDateTimeString();
		foreach ($attributes as $key => $attribute) {
			$data = [
				'index'					=> $index + 1,
				'trade_id'				=> $tradeId,
				'attribute_id'			=> $attribute['attribute_id'],
				'value'					=> isset($attribute['value']) ? $attribute['value'] : null,
				'parent_attribute_id'	=> $parentId,
				'measurement_id'		=> $measurement->id,
				'created_at'			=> $now,
				'updated_at'			=> $now,
			];
			$msValues[] = $data;

			if(ine($attribute, 'sub_attributes')) {
				$subValues = $this->getValuesData($measurement, $tradeId, $index, $attribute['sub_attributes'], $attribute['attribute_id']);

				$msValues = array_merge($msValues, $subValues);
			}
		}

		return $msValues;
	}
}
