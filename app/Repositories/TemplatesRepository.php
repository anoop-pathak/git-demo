<?php

namespace App\Repositories;

use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Folders\Helpers\TemplateQueryBuilder;

class TemplatesRepository extends AbstractRepository
{
    protected $model;
    protected $scope;
    protected $announcement;
    protected $templateQueryBuilder;

    public function __construct(Template $model, Context $scope, AnnouncementsRepository $announcement,TemplateQueryBuilder $templateQueryBuilder)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->announcement = $announcement;
        $this->templateQueryBuilder = $templateQueryBuilder;
    }

    /**
     * Create Template
     * @param  string $title | Template Title
     * @param  string $type | Template type e.g., estimate or proposal
     * @param  array $trades | Array of trades ids
     * @param  int $createdBy | Current user id
     * @return template
     */
    public function createTemplate($title, $type, $trades, $createdBy, $meta = [])
    {
        $companyId = null;
        if ($this->scope->has()) {
            $companyId = $this->scope->id();
        }
        $template = $this->model;
        $template->title = $title;
        $template->type = $type;
        $template->created_by = $createdBy;
        $template->company_id = $companyId;
        $template->option = ine($meta, 'option') ? $meta['option'] : null;
        $template->insurance_estimate = ine($meta, 'insurance_estimate') ? $meta['insurance_estimate'] : false;
        $template->all_divisions_access = isset($meta['all_divisions_access']) ? $meta['all_divisions_access'] : true;

        if (ine($meta, 'page_type')) {
            $template->page_type = $meta['page_type'];
        }

        $template->for_all_trades = ine($meta, 'for_all_trades') ? $meta['for_all_trades'] : 0;

        $template->save();

        if (!$template->for_all_trades) {
            $template->trades()->attach($trades);
        }

        if(ine($meta,'division_ids')){
            $this->assignDivisions($template, $meta['division_ids']);
        }

        // create announcement for jobprogress templates..
        if (!$companyId) {
            $this->announcement->createAnnouncement("New $type template created", "", $trades);
        }
        return $template;
    }

    /**
	 * get all the templates along with folders.
	 */
	public function get($filters, $sortable = true)
	{
		$with = $this->includeData($filters);
		$templates = $this->getTemplates($sortable, $with);
		$this->applyFilters($templates, $filters);
        $templates = $this->getTemplatesAlongWithFolders($templates, $filters, $sortable);

		return $templates;
	}

    public function getFilteredTemplates($filters, $sortable = true)
    {
        $with = $this->includeData($filters);
        $templates = $this->getTemplates($sortable, $with);
        $this->applyFilters($templates, $filters);

        return $templates;
    }

    public function getTemplatesAlongWithFolders($templates, $filters = [], $sortable = true)
	{
		$service = $this->templateQueryBuilder->setBuilder($templates)
            ->setFilters($filters)
            ->setSortable($sortable)
            ->bind();
        $templates = $service->get();

        return $templates;
    }

    public function getTemplates($sortable = true, $with = array())
    {
        $templates = $this->make($with);

        if ($sortable) {
            $templates = $templates->Sortable();
        }
        $templates->with('trades');

        $templates->select('templates.*');

        return $templates;
    }

    public function isValidTemplateIdsForGrouping(array $templateIds)
    {
        return !($this->make()->where('company_id', '!=', $this->scope->id())
            ->whereIn('id', $templateIds)->exists());
    }

    public function getByGroupId($groupId)
    {
        return $this->make()->whereCompanyId($this->scope->id())
            ->whereGroupId($groupId)->first();
    }


    public function getTemplateIdsByGroupId($groupId)
    {
        return $this->make()->whereCompanyId($this->scope->id())
            ->whereIn('group_id', (array)$groupId)->pluck('id')->toArray();
    }

    public function getById($id)
    {

        return $this->make()->withCustom(getScopeId())->findOrFail($id);
    }

    public function getTemplateIds($templateIds = [])
    {
        return $this->make()->whereCompanyId($this->scope->id())
            ->whereIn('id', (array)$templateIds)->pluck('id')->toArray();
    }

    public function getPageByPageId($id)
    {
        $companyId = $this->scope->id();
        $page = TemplatePage::company($companyId)->findOrFail($id);

        return $page;
    }

    /**
     * Get Templates by group ids
     * @param  array $groupIds group ids
     * @param  array $column selected columns
     * @return Templates
     */
    public function getTemplatesByGroupIds(array $groupIds = [], $columns = ['*'])
    {
        $builder = $this->make()
			->sortable()
			->whereCompanyId($this->scope->id())
			->whereIn('group_id', $groupIds)
			->withoutArchived()
			->select($columns)
			->with(['pages' => function($query){
				$query->select('id', 'template_id', 'auto_fill_required', 'order');
		    }])
			->orderBy('group_order', 'ASC');
		$templates = $this->getTemplatesAlongWithFolders($builder, ['group_id' => $groupIds], true);

		if(!$templates) {
			return $templates;
		}

		$items = [];
		foreach($templates as $template) {
			$items[$template->group_id][] = $template;
		}
		return $items;
    }

    /**
     * Get template page by id
     * @param  Int $id Template page id
     * @return Response
     */
    public function getPageById($id)
    {
        return TemplatePage::where('template_pages.id', $id)
            ->leftJoin(
                DB::raw('(SELECT id, title, company_id, page_type FROM templates WHERE deleted_at IS NULL) as templates'),
                'template_pages.template_id',
                '=',
                'templates.id'
            )
            ->select('template_pages.*', 'title', 'company_id', 'page_type')
            ->whereCompanyId($this->scope->id())
            ->firstOrFail();
    }

    public function assignDivisions($template, $divisionIds=[], $forAllDivisions=false)
    {
        $template->divisions()->sync(arry_fu($divisionIds));
        $template->all_divisions_access = $forAllDivisions;
        $template->save();
        return $template;
    }

    /**
	 * Find an deleted emetity by id
	 * @param int $id
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getDeletedById($id)
	{
		$query = $this->make();
		if(getScopeId()) {
			$query->where('company_id', getScopeId());
		} else {
			$query->where('company_id', '<=', 0);
		}

		return $query->onlyTrashed()->findOrFail($id);
	}

    /******************** Private Function ************************/

    private function applyFilters($query, $filters)
    {
        $query->division();

        //to show company's and superadmin's templetes..
        if ($this->scope->has()) {
            if (ine($filters, 'templates') && ($filters['templates'] == 'system')) {
                $query->system();
            } elseif (ine($filters, 'templates') && ($filters['templates'] == 'custom')) {
                $query->custom($this->scope->id());
            } else {
                $query->withCustom($this->scope->id());
            }
        } else {
            $query->system();
        }

        if(ine($filters,'deleted_templates')) {
			$query->onlyTrashed();
		}

        if(ine($filters, 'ids')) {
            $query->whereIn('templates.id', (array)$filters['ids']);
        }

        // this parameter is use to get only templates.
		if(ine($filters, 'template_ids')) {
			$query->whereIn('templates.id', (array)$filters['template_ids']);
		}

        if (ine($filters, 'group_id')) {
            $query->whereGroupId($filters['group_id']);
        } else {
            $query->groupBy(DB::raw('case when group_id is null then templates.id else group_id end'));
            $query->addSelect(DB::raw("count('group_id') as count"));
        }

        if (config('is_mobile') && !ine($filters, 'insurance_estimate')) {
            $query->whereInsuranceEstimate(false);
        }

        if (ine($filters, 'trades')) {
            $query->where(function ($query) use ($filters) {
                $query->byTrades((array)$filters['trades'])
                    ->orWhere('for_all_trades', 1);
            });
        }

        //for specific template type..
        if (ine($filters, 'type')) {
            $query->where(function ($query) use ($filters) {
                $query->where('templates.type', '=', $filters['type']);
                if ($filters['type'] == 'estimate') {
                    // blank template..
                    $query->orWhere('templates.type', '=', 'blank');
                }
            });
        }

        if (!ine($filters, 'multi_page')) {
            $query->has('pages', '=', 1);
        }

        // include first page..
        $query->with([
            'firstPage' => function ($query) use ($filters) {
                if (ine($filters, 'without_content')) {
                    $query->select('id', 'thumb', 'template_id', 'image', 'auto_fill_required');
                }
            }
        ]);

        // include pages..
        $query->with([
            'pages' => function ($query) use ($filters) {
                if (ine($filters, 'without_content')) {
                    $query->select('id', 'thumb', 'template_id', 'image', 'auto_fill_required');
                }
            }
        ]);

        if (ine($filters, 'insurance_estimate')) {
            $query->whereInsuranceEstimate(true);
        }

        if (ine($filters, 'page_type')) {
            $query->wherePageType($filters['page_type']);
        }

        if (ine($filters, 'only_google_sheets')) {
            $query->whereNotNull('google_sheet_id');
        }

        if (ine($filters, 'without_google_sheets')) {
            $query->whereNull('google_sheet_id');
        }

        if (ine($filters, 'without_insurance_estimate')) {
            $query->whereInsuranceEstimate(false);
        }

        if (ine($filters, 'most_frequently_used')) {
            $query->join('template_uses', function ($join) {
                $startDate = Carbon::now()->subDays(30)->toDateTimeString();
                $join->on('templates.id', '=', 'template_uses.template_id')
                    ->where('template_uses.created_at', '>=', $startDate)
                    ->where('template_uses.company_id', '=', $this->scope->id());
            })->addSelect(DB::raw('count(template_uses.template_id) as template_order'))
                ->orderBy('template_order', 'desc');
        }

        if (ine($filters, 'only_archived')) {
            $query->onlyArchived();
        } else {
            if (!ine($filters, 'with_archived')) {
                $query->withoutArchived();
            }
        }

        if(ine($filters, 'title')) {
			$query->nameSearch($filters['title'])
			->orderBy('relevance', 'desc');
		}

        if(ine($filters, 'q')) {
			$query->nameSearch($filters['q'])
			->orderBy('relevance', 'desc');
		}
    }

    private function includeData($filter = array())
	{
		$with = ['trades'];

		$includes = isset($filter['includes']) ? $filter['includes'] : [];
        if(!is_array($includes) || empty($includes)) return $with;

		if(in_array('deleted_by', $includes)) {
            $with[] = 'deletedBy';
        }

        if(in_array('pages', $includes)) {
            $with[] = 'pages.pageTableCalculations';
		}

		if(in_array('tables', $includes)) {
            $with[] = 'pageTableCalculations';
        }

        return $with;
	}
}
