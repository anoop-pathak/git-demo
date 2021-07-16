<?php

namespace App\Services\Folders\Criteria;

use App\Models\Folder;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Services\Contexts\Context;
use App\Repositories\FolderRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TemplateFolderSearchCriteria
{
    protected $templateQueryBuilder;
    protected $model;
    protected $tableName;
    protected $folders;

    protected $type;
    protected $isShowDeleted = false;
    protected $parentId;
    protected $keyword; // `q`
    protected $filters = [];
    protected $isDeletedTemplates = false;
    protected $limit;

    // inject classes
    protected $scope;
    protected $repository;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->tableName = (New Folder)->getTable();
    }

    /**
     * set templates query builder.
     *
     * @param Eloquent $builder
     * @return self
     */
    public function setTemplateBuilder($builder)
    {
        $this->templateQueryBuilder = $builder;
        return $this;
    }

    /**
     * set requested parent id.
     *
     * @param Integer $parentId
     * @return self
     */
    public function setParentId($parentId = null)
    {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * add title condition if requested.
     *
     * @param String $title
     * @return self
     */
    public function whereTitle($title = null)
    {
        if(!$title) {
            return $this;
        }
        $this->model->where(function($q) use($title) {
                $q->where('name', 'LIKE', "%$title%")
                    ->orWhere('t.title', 'LIKE', "%$title%")
                    ->orWhere('t.group_name', 'LIKE', "%$title%");
            });

        return $this;
    }

    /**
     * set Template type in query builder
     *
     * @param String $type
     * @return self
     */
    public function whereType($type)
    {
        if(!$type) {
            return $this;
        }
        $this->type = $type;
        $type = Folder::TEMPLATE_TYPE_PREFIX . $type;

        $this->model->where(function($q) use($type) {
            $q->where($this->tableName.'.type', $type);
            if($this->type == Template::ESTIMATE) {
                $q->orWhere($this->tableName.'.type', Folder::TEMPLATE_TYPE_PREFIX.Template::BLANK);
            }
        });

        return $this;
    }

    /**
     * add condition to get deleted items.
     *
     * @param Boolean $val: true/false
     * @return self
     */
    public function whereDeleted($val = false)
    {
        $this->isDeletedTemplates = false;
        if(!$val) {
            return $this;
        }

        $this->isDeletedTemplates = true;
        $this->model->onlyTrashed();

        if (!$this->parentId) {
            $this->model->where('is_auto_deleted', '<>', 1);
        }

        return $this;
    }

    public function applySorting($sorting = true)
    {
        # TODO - Lakhwinder sir will check
        if($sorting) {
            $this->model->sortable();
        }
        return $this;
    }

    /**
     * Set limit
     *
     * @param Integer $limit (optional)
     * @return self
     */
    public function setLimit($limit = null)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Create Folders table query builder.
     *
     * @return self
     */
    public function make()
    {
        $builder = new Folder;

        $searchable = [
            'columns' => [
                't.group_name'	=> 10,
                't.title' => 10,
                $this->tableName.'.name'	=> 10,
            ],
        ];
        $builder = $builder->setSearchableColumns($searchable);
        $builder = $builder->leftJoin("templates as t", function($join) {
                        $join->on('t.id', '=', 'folders.reference_id');
                    })
                    // ->orderBy('is_dir', 'desc')
                    ->with(['children'])
                    ->selectRaw($this->tableName.'.*, t.group_id');
        $this->model = $builder;
        return $this;
    }

    /**
     * append keyword search in query builder if requested.
     *
     * @param String $keyword: (optional) string of keyword.
     * @return self
     */
    public function whereKeywordSearch($keyword = null)
    {
        if(!$keyword) {
            return $this;
        }

        $this->keyword = $keyword;

        $this->model->search(implode(' ', array_slice(explode(' ', $keyword), 0, 10)), null, true);
        return $this;
    }

    /**
     * Get data from folders table on the basis of limit.
     *  if limit is set then get data in pagination otherwise get all the data.
     *
     * @return self
     */
    public function get()
    {
        $builder = $this->model;

        if(!$this->excludeDirectories()) {
            $builder = $this->whereParentId($builder);
        }

        $folders = [];
        if($this->limit) {
            $folders = $builder->paginate($this->limit);
        } else {
            $folders = $builder->get();
        }

        $this->folders = $folders;
        return $this->folders;
    }

    public function getReferenceIds()
    {
        if(!$this->folders) {
            return [];
        }

        $ids = $this->folders->pluck('reference_id')->toArray();
        $groupIds = $this->folders->pluck('group_id')->toArray();
        $groupIds = array_filter(array_unique($groupIds));
        if($groupIds) {
            $refIds = Template::whereIn('group_id', $groupIds)->pluck('id')->toArray();
            $ids = array_merge($ids, $refIds);
        }
        return array_filter(array_unique($ids));
    }

    public function applyFilters($filters = [])
    {
        $this->filters = $filters;

        $this->model->where(function($q){
                            $q->whereIn('reference_id', (array)$this->getTemplateIds());

                            if(!$this->excludeDirectories()) {
                                $q->orWhere('is_dir', true);
                            }
                        });

        $this->appendDirFilter();
        $this->appendTemplateTypes();
        // dd($this->model->toSql(), $this->model->getBindings(), $this->scope->has());
        return $this;
    }

    /**
     * add condition where parent id in builder according to requested params.
     *
     * @param Eloquent $builder
     * @return Eloquent
     */
    private function whereParentId($builder)
    {
        if($this->parentId && $this->keyword) {

            $item = Folder::where('id', $this->parentId)->first();
            $builder = $builder->where('path', 'LIKE', $item->path.'/%');
        } else if (!$this->keyword && !$this->isDeletedTemplates && !$this->parentId) {

            $parentIds = $this->getDefaultParentId();
            $builder = $builder->whereIn('parent_id', (array)$parentIds);
        } else if($this->parentId) {

            $builder = $builder->where('parent_id', $this->parentId);
        }

        return $builder;
    }

    /**
     * Check fields from the filter parameters which needs to ignore folder structure.
     *
     * @return Boolean (true/false)
     */
    private function excludeDirectories()
    {
        $templateIds = isSetNotEmpty($this->filters, 'template_ids');
        $groupId = isSetNotEmpty($this->filters, 'group_id');

        if($templateIds || $groupId) {
            return true;
        }
        return false;
    }

    /**
     * get templates on the basis of requested parameter.
     *  below are the possible values
     *  templates: 'system'
     *  templates: 'custom'
     *  if logged in user is superadmin then fetch only system templates.
     *  But if logged in user belongs to any company then get templates on the basis of requested parameter
     *  if parameter is not exists in request then get both `system` and `custom` templates.
     *
     * @return void
     */
    private function appendTemplateTypes()
    {
        if ($this->scope->has()) {
            if ($this->isSystemTemplates()) {

                // get system templates/folders.
                $this->model->system();
            } elseif ($this->isCustomTemplates()) {

                // get custom templates.
                $this->model->custom($this->scope->id());
            } else {
                $this->model->withCustom($this->scope->id());
            }
        }else {

            $this->model->system();
        }

        return $this;
    }

    /**
     * Load relational data with add filtering query in relational data.
     *
     * @return self.
     */
    public function loadRelationships()
    {
        $this->model->with(["doc_children" => function($q) {
                                    $templatesTable = (new Template)->getTable();
                                    $q->join("$templatesTable as templates", function($j) {
                                        $j->on('templates.id', '=', 'folders.reference_id');
                                    });
                                    $q = $this->withQueryBuilder($q);
                                },
                                'dir_children' => function($q) {
                                    if($this->isDeletedTemplates) {
                                        $q->withTrashed();
                                    }
                                }
                            ]);
        return $this;
    }

    /**
     * Append is directory filter to get dir/files on the basis of filtering param.
     *
     * @return Folder
     */
    protected function appendDirFilter()
    {
        $filters = $this->filters;
        if(!isset($filters['is_dir']) || $filters['is_dir'] == '') {
            return $this;
        }
        $isDir = false;
        if($filters['is_dir'] == 1) {
            $isDir = true;
        }

        $this->model->whereIsDirectory($isDir);
        return $this;
    }

    /**
     * append query in get docs as children.
     *
     * @param Builder $query
     * @return Builder
     */
    private function withQueryBuilder($query)
    {
        $filters = $this->filters;
        if(ine($filters,'deleted_templates')) {
            $query->whereNotNull('templates.deleted_at');
		}

		if(ine($filters, 'ids')) {
			$query->whereIn('templates.id', (array)$filters['ids']);
		}

		if(ine($filters, 'group_id')) {
            $query->where('templates.group_id', $filters['group_id']);
		}

		if(config('is_mobile') && !ine($filters, 'insurance_estimate')) {
			$query->where('templates.insurance_estimate', false);
        }

		//for specific template type..
		if(ine($filters,'type')) {
			$query->where(function($query) use($filters){
				$query->where('templates.type','=',$filters['type']);
				if($filters['type'] == 'estimate') {
					// blank template..
					$query->orWhere('templates.type','=','blank');
				}
			});
		}

		if(!ine($filters,'multi_page')) {
            $pagesTable = (new TemplatePage)->getTable();
            $query->join("$pagesTable as pages", function($j) {
                $j->on("pages.template_id", '=', 'templates.id');
            });
		}

		if(ine($filters, 'insurance_estimate')) {
            $query->where('templates.insurance_estimate', true);
		}

		if(ine($filters, 'page_type')){
			$query->where('templates.page_type', $filters['page_type']);
		}

		if(ine($filters, 'only_google_sheets')){
			$query->whereNotNull('templates.google_sheet_id');
		}

		if(ine($filters, 'without_google_sheets')){
			$query->whereNull('templates.google_sheet_id');
		}

		if(ine($filters, 'without_insurance_estimate')) {
            $query->where('templates.insurance_estimate', false);
		}

        if(ine($filters, 'most_frequently_used')) {
        	$query->join('template_uses', function($join){
        		$startDate = Carbon::now()->subDays(30)->toDateTimeString();
        		$join->on('templates.id', '=', 'template_uses.template_id')
        		->where('template_uses.created_at', '>=', $startDate)
        		->where('template_uses.company_id', '=', $this->scope->id());
            })->addSelect(DB::raw('count(template_uses.template_id) as template_order'))
            ->orderBy('template_order', 'desc');
        }

		if(ine($filters, 'only_archived')) {
            $query->whereNotNull('templates.archived');
		} else {
			if(!ine($filters, 'with_archived'))	{
                $query->whereNull('templates.archived');
			}
        }

        return $query;
    }

    /**
     * Get derfault parent id.
     *  If parent id is set in request then get requested parent id.
     *  and if parent id is not set in request then find parent id
     *  on the basis of requested type and company id.
     */
    public function getDefaultParentId()
    {
        if(!$this->scope->has() || $this->isSystemTemplates()) { // system templates.
            return $this->repository->findSystemRootId($this->type);
        }

        $parentIds = [];
        if($this->isCustomTemplates()) { // custom templates.
            $parentIds[] = $this->getLoggedInCompanyTemplateRootId();
            $parentIds[] = $this->getBlankTemplateRootId();

        } else { // with templates.
            $parentIds[] = $this->repository->findSystemRootId($this->type);
            $parentIds[] = $this->getLoggedInCompanyTemplateRootId();
            $parentIds[] = $this->getBlankTemplateRootId();
        }

        return $parentIds;
    }

    /**
     * Check is system templates key exists in filter or not.
     *
     * @return boolean
     */
    private function isSystemTemplates()
    {
        return ine($this->filters,'templates') && ($this->filters['templates'] == 'system');
    }

    /**
     * Check is custom templates key exists in filter or not.
     *
     * @return boolean
     */
    private function isCustomTemplates()
    {
        return ine($this->filters,'templates') && ($this->filters['templates'] == 'custom');
    }

    /**
     * Get root item parent id for the folders/templates of logged in user.
     *
     * @return integer parent id.
     */
    private function getLoggedInCompanyTemplateRootId()
    {
        $parentId = null;
        $path = [];
		if($this->scope->has()) {
            $parentId = $this->repository->findOrCreateByName($this->scope->id(), $parentId);
		}
		$path[] = Folder::DEFUALT_TEMPLATES_DIR_LABEL;
        $path[] = $this->type;

        $parentId = $this->repository->findOrCreateByName(Folder::DEFUALT_TEMPLATES_DIR_LABEL, $parentId);
        $parentId = $this->repository->findOrCreateByName($this->type, $parentId);

        return $parentId;
    }

    /**
     * Get root id of blank template.
     *  Blank template comes under super admin so exclude company id when getting blank templates.
     *
     * @return integer root id.
     */
    private function getBlankTemplateRootId()
    {
        $builder = Folder::where('name', Folder::DEFUALT_TEMPLATES_DIR_LABEL)
                        ->whereNull('parent_id')
                        ->whereNull('company_id');
        $templateRootItem = $builder->first();
        if(!$templateRootItem) {
            return null;
        }
        $builder = Folder::where('name', 'blank')
                        ->where('parent_id', $templateRootItem->id)
                        ->whereNull('company_id');
        $blankRootItem = $builder->first();
        if(!$blankRootItem) {
            return null;
        }
        return $blankRootItem->id;
    }

    private function getTemplateIds()
    {
        return $this->templateQueryBuilder->pluck('templates.id')->toArray();
    }
}