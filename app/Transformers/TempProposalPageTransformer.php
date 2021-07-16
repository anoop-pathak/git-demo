<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\PageTableCalculationTransformer;

class TempProposalPageTransformer extends TransformerAbstract
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
            'company_id' => $page->company_id,
            'title' => $page->title,
            'content' => $page->content,
            'auto_fill_required' => $page->auto_fill_required,
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
            'page_type'  => $page->page_type,
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
