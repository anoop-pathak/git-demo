<?php

namespace App\Transformers;

use App\Models\Trade;
use Illuminate\Support\Facades\App;
use League\Fractal\TransformerAbstract;
use App\Model\MeasurementAttribute;

class TradesTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['work_types'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'templates_count',
        'third_party_tools_count',
        'jobs_count',
        'work_types',
        'values',
        'attributes',
        'measurement_values_summary',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($trade)
    {
        return [
            'id' => $trade->id,
            'name' => $trade->name,
            'color' => isset($trade->color) ? $trade->color : $trade->getDefaultColor(),
        ];
    }

    /**
     * Include TemplatesCount
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTemplatesCount($trade, $params)
    {
        $templates = App::make(\App\Repositories\TemplatesRepository::class);

        list($withoutInsEstimate) = issetRetrun($params, 'without_insurance_estimate');
        list($withoutGoogleSheets) = issetRetrun($params, 'without_google_sheets');
        list($onlyGoogleSheet) = issetRetrun($params, 'only_google_sheets');

        $data['estimate_templates'] = $templates->getFilteredTemplates([
            'trades' => $trade->id,
            'type' => 'estimate',
            'templates' => 'custom',
            'multi_page' => true,
            'without_google_sheets' => (bool)$withoutGoogleSheets,
            'only_google_sheets' => (bool)$onlyGoogleSheet,
        ])->get()->count();

        $data['proposal_templates'] = $templates->getFilteredTemplates([
            'trades' => $trade->id,
            'type' => 'proposal',
            'templates' => 'custom',
            'multi_page' => true,
            'without_google_sheets' => (bool)$withoutGoogleSheets,
            'only_google_sheets' => (bool)$onlyGoogleSheet,
            'without_insurance_estimate' => (bool)$withoutInsEstimate,
        ])->get()->count();

        return $this->item($data, function ($data) {
            return $data;
        });
    }

    /**
     * Include Third Party Tools Count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeThirdPartyToolsCount($trade)
    {
        $thirdPartyTool = App::make(\App\Repositories\ThirdPartyToolsRepository::class);
        $data['third_party_tool'] = $thirdPartyTool->getFilteredTools([
            'trades' => $trade->id
        ])->count();

        return $this->item($data, function ($data) {
            return $data;
        });
    }

    /**
     * Include Jobs Count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobsCount($trade)
    {
        $scope = App::make(\App\Services\Contexts\Context::class);
        $data['jobs'] = $trade->job_count ?: 0;

        return $this->item($data, function ($data) {
            return $data;
        });
    }

    /**
     * Include Work Types
     *
     * @return League\Fractal\ItemResource
     */
    public function includeWorkTypes($trade)
    {
        $data = $trade->workTypes;

        $data = $this->collection($data, function ($data) {
            return $data->toArray();
        });

        return $data;
    }

    /**
     * Include Measurement Values
     * @param  Trade $trade [Trade]
     * @return values
     */
    public function includeValues($trade)
    {
        $measurementValues = $trade->measurementValues;
        return $this->collection($measurementValues, function($measurementValue){
            return $measurementValue;
        });
    }

    /**
     * Include Measurement Atributes
     * @param  trade  $trade trade
     * @return attribute collection
     */
    public function includeAttributes($trade)
    {
        $attributes = $trade->measurementAttributes;
        $transformer = new MeasurementAttributeTransformer;
        $transformer->setDefaultIncludes(['sub_attributes']);

        return $this->collection($attributes, $transformer);
    }

    public function includeMeasurementValuesSummary($trade)
    {
        $measurementValues = $trade->measurementValuesSummary;
        $transformer = new MeasurementValueTransformer;
        $transformer->setDefaultIncludes(['sub_attribute_values_summary', 'unit']);

        return $this->collection($measurementValues, $transformer);
    }
}
