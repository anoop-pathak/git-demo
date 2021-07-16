<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class FlagsTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
        'customers_count',
        'jobs_count',
        'color'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($flag)
    {
        return [
            'id' => $flag->id,
            'title' => $flag->title,
            'for' => $flag->for,
            'company_id' => $flag->company_id,
            'reserved' => $flag->isReservedFlag(),
        ];
    }

    /**
     * Include customers count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomersCount($flag)
    {
        $data['count'] = $flag->customers()->own()->count();
        return $this->item($data, function ($data) {
            return $data;
        });
    }

    /**
     * Include jobs count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobsCount($flag)
    {
        $data['count'] = $flag->jobs()
            ->own()
            ->division()
            ->excludeLostJobs()
            ->count();
        return $this->item($data, function ($data) {
            return $data;
        });
    }

    /**
     * Include Color
     *
     * @return League\Fractal\ItemResource
     */
    public function includeColor($flag)
    {
        $color = $flag->color;

        if($color) {
            return $this->item($color, function($color){
                return [
                    'color' => $color->color,
                ];
            });
        }

    }
}
