<?php

namespace App\Transformers;

use App\Models\Template;
use FlySystem;
use Illuminate\Support\Facades\App;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use Request;
use App\Transformers\PageTableCalculationTransformer;
use App\Models\Folder;

class TemplateTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'pages',
        'ancestors',
        'trades',
        'created_by',
        'with_proposal_serial_number',
        'divisions',
        'deleted_entity',
        'deleted_by',
        'tables'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($template)
    {

        $parentId = ($template->parent_id) ? (int)$template->parent_id : null;
		if($template instanceof Folder) {
			$totalCount = 0;
			if($template->doc_children) {
				$totalCount = $template->doc_children->count();
			}
			if($template->dir_children) {
				$totalCount += $template->dir_children->count();
			}
			return [
				'id'                =>  $template->id,
				'parent_id'         =>  $parentId,
				'company_id' 	    =>  $template->company_id,
				'title'             =>  $template->name,
				'is_dir'            =>  $template->is_dir,
				'created_by'        =>  $template->created_by,
				'updated_by'        =>  $template->updated_by,
				'created_at'        =>  $template->created_at,
				'updated_at'        =>  $template->updated_at,
				'no_of_child'       =>  $totalCount,
			];
		} else if((Request::has('group_id')) || is_null($template->group_id)) {
            $data = [
                'id' => $template->id,
                'parent_id'  =>  $parentId,
                'company_id' => $template->company_id,
                'type' => $template->type,
                'title' => $template->title,
                'content' => isset($template->firstPage->content) ? $template->firstPage->content : null,
                'editable_content' => isset($template->firstPage->editable_content) ? $template->firstPage->editable_content : null,
                'image' => isset($template->firstPage->image) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $template->firstPage->image) : null,
                'thumb' => isset($template->firstPage->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $template->firstPage->thumb) : null,
                'option' => $template->option,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
                'total_pages' => $template->pages->count(),
                'page_type' => $template->page_type,
                'insurance_estimate' => $template->insurance_estimate,
                'for_all_trades' => (bool)$template->for_all_trades,
                'group_id' => $template->group_id,
                'group_name' => $template->group_name,
                'group_order' => $template->group_order,
                'archived' => $template->archived,
                'is_dir'   => false,
                'all_divisions_access'	=>  $template->all_divisions_access,
            ];

            if (ine($template, 'google_sheet_id')) {
                $data['google_sheet_id'] = $template->google_sheet_id;
                $data['google_sheet_url'] = getGoogleSheetUrl($template->google_sheet_id);
                $data['thumb'] = getGoogleSheetThumbUrl($template->google_sheet_id);
            }

            return $data;
        } else {
            return [
                'parent_id'   =>  $parentId,
				'title'  	  =>  $template->title,
                'group_name' => $template->group_name,
                'group_id' => $template->group_id,
                'is_dir'	  => false,
                'count' => $template->count,
                'type' => $template->type,
                'page_type' => $template->page_type,
            ];
        }
    }

    /**
	 * Get Ancestors of the node.
	 *
	 * @param Template $resource
	 * @return void
	 */
	public function includeAncestors($resource)
	{
		$data = [];
        if ($resource instanceof Folder) {
            $data = $resource->ancestors();
        } else {
			$data = $resource->ancestors;
		}

		if($data) {
			return $this->collection($data, function($ancestor) {
				return [
					'id' 		=> $ancestor['id'],
					'name' 		=> $ancestor['name'],
					'parent_id' => $ancestor['parent_id'],
				];
			});
		}
	}

    /**
     * Include trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($template)
    {
        $trades = $template->trades;
        if (sizeof($trades) != 0) {
            return $this->collection($trades, function ($trade) {
                return [
                    'id' => $trade->id,
                    'name' => $trade->name,
                ];
            });
        }
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($template)
    {
        $user = $template->user;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include pages
     *
     * @return League\Fractal\ItemResource
     */
    public function includePages($template)
    {
        $pages = $template->pages;

        if($pages) {
            return $this->collection($pages, new TemplatePageTransformer);
        }
    }


    /**
     * [includeWithPropsalSerialNumber description]
     * @param  [object] $template     [description]
     * @return Response
     */
    public function includeWithProposalSerialNumber($template)
    {
        if ($template->type == Template::PROPOSAL) {
            $proposalRepo = App::make(\App\Repositories\ProposalsRepository::class);
            $data['serial_number'] = $proposalRepo->getSerialNumber();

            return $this->item($data, function ($data) {
                return $data;
            });
        }
    }

    public function includeDeletedEntity($template)
    {
        $deletedDate = $template->deleted_at;

        if($deletedDate){

            return $this->item($deletedDate, function($deletedDate){
                return [
                    'deleted_at' => $deletedDate ? $deletedDate->toDateTimeString() : null
                ];
            });
        }
    }

    public function includeDeletedBy($template)
    {
        $user = $template->deletedBy;

        if($user) {

            return $this->item($user, function($user){
                return [
                    'id'                => (int)$user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'full_name_mobile'  => $user->full_name_mobile,
                    'company_name'      => $user->company_name,
                ];
            });
        }
    }

    /**
     * Include Division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivisions($template)
    {
        $divisions = $template->divisions;
        if($divisions){
            return $this->collection($divisions, new DivisionsTransformerOptimized);
        }
    }

    public function includeTables($template)
    {
    	$pageTableCalculations = $template->pageTableCalculations;

    	if($pageTableCalculations) {
            return $this->collection($pageTableCalculations, new PageTableCalculationTransformer);
        }
    }
}
