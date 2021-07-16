<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\PageTableCalculationTransformer;

class ProposalPageTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['tables'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($page)
    {

        return [
            'id' => $page->id,
            'title' => $page->title,
            'content' => $page->template,
            'image' => !empty($page->image) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $page->image) : null,
            'thumb' => !empty($page->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $page->thumb) : null,
            'editable_content' => $page->template_cover,
            'order' => $page->order,
            'auto_fill_required' => $page->auto_fill_required
        ];
    }

    public function includeTables($page)
    {
        $pageTableCalculations = $page->pageTableCalculations;

        if($pageTableCalculations) {
            return $this->collection($pageTableCalculations, new PageTableCalculationTransformer);
        }
    }
}
