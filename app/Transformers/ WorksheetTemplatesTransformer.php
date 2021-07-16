<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class WorksheetTemplatesTransformer extends TransformerAbstract
{

	protected $defaultIncludes = [];

    protected $availableIncludes = ['pages_detail'];

    public function transform($template)
	{
		return [
			'id' => (int)$template->id,
			'title' => $template->title,
			'page_type' => $template->page_type,
			'thumb' 	=>	isset($template->firstPage->thumb) ? \FlySystem::publicUrl(config('jp.BASE_PATH').$template->firstPage->thumb) : null,
			'insurance_estimate'=>	$template->insurance_estimate,
		];
	}

    public function includePagesDetail($template)
	{
		$pages = $template->pages;
		if(!$pages->isEmpty()) {
			return $this->collection($pages, function($page) {
				return [
					'page_id' => $page->id,
					'auto_fill_required' => $page->auto_fill_required
				];
			});
		}
	}
}