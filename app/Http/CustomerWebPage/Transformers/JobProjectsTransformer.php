<?php

namespace App\Http\CustomerWebPage\Transformers;

use App\Http\CustomerWebPage\Transformers\AddressesTransformer;
use App\Http\CustomerWebPage\Transformers\JobContactTransformer;
use App\Http\CustomerWebPage\Transformers\JobTypesTransformer;
use App\Http\CustomerWebPage\Transformers\LabourTransformer;
use App\Http\CustomerWebPage\Transformers\UsersTransformer;
use League\Fractal\TransformerAbstract;

class JobProjectsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['work_types', 'trades'];

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
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'number' => $job->number,
        ];

        return $data;
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($job)
    {
        $trades = $job->trades;
        if($trades) {
            return $this->collection($trades, function($trade) {
                return [
                    'id'   => $trade->id,
                    'name' => $trade->name,
                ];
            });
        }
    }

    /**
     * Include workTypes
     *
     * @return League\Fractal\ItemResource
     */

    public function includeWorkTypes($job)
    {
        $workTypes = $job->workTypes;
        if ($workTypes) {
            return $this->collection($workTypes, new JobTypesTransformer);
        }
    }
}
