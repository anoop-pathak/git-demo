<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class OnboardChecklistSectionTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['checklists'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($section)
    {

        return [
            'id' => $section->id,
            'title' => $section->title,
            'position' => $section->position,
        ];
    }


    public function includeChecklists($section)
    {
        $checklists = $section->checklists;

        if ($checklists) {
            return $this->collection($checklists, new OnboardChecklistTransformer);
        }
    }
}
