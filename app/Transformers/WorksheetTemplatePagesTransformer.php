<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class WorksheetTemplatePagesTransformer extends TransformerAbstract
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


	public function transform($page)
	{
		return [
			'id'					=> (int)$page->id,
			'title'					=> $page->title,
			'content'				=> $page->content,
			'auto_fill_required'	=> $page->auto_fill_required,
			'page_type'				=> $page->page_type,
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