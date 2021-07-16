<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class OnboardChecklistTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['section'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($checklist)
    {

        return [
            'id' => $checklist->id,
            'section_id' => $checklist->section_id,
            'title' => $checklist->title,
            'action' => $checklist->action,
            'is_required' => $checklist->is_required,
            'video_url' => $checklist->video_url,
            'created_at' => $checklist->created_at,
            'updated_at' => $checklist->updated_at,
        ];
    }

    public function includeSection($checklist)
    {
        $section = $checklist->section;
        if ($section) {
            return $this->item($section, new OnboardChecklistSectionTransformer);
        }
    }
}
