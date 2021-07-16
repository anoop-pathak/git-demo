<?php
namespace App\Services\Folders\Helpers;

use App\Models\Folder;
use App\Models\Template;
use Paginator;
use App\Services\Contexts\Context;
use App\Services\Folders\Criteria\TemplateFolderSearchCriteria;

class TemplateQueryBuilder
{
    protected $searchCriteria;
    protected $scope;
    protected $sortable = true;
    protected $builder;
    protected $filters;
    protected $type;
    protected $parentId;
    protected $limit;
    protected $templates;
    protected $deletedTemplates;

    protected $folderReferenceFiles;

    // files and folders
    protected $files;
    protected $folders;

    protected $folderReferenceIds = [];
    protected $folderReferenceGroupIds = [];

    public function __construct(TemplateFolderSearchCriteria $searchCriteria, Context $scope)
    {
        $this->searchCriteria = $searchCriteria;
        $this->scope = $scope;
        $this->templates = [];
    }

    /**
     * Set Builder.
     *
     * @return self.
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * Set option for apply sortable trait.
     *
     * @return self.
     */
    public function setSortable($sortable = true)
    {
        $this->sortable = (bool)$sortable;
        return $this;
    }

    /**
     * get collection of templates.
     *
     * @return Collection of templates.
     */
    public function get()
    {
        return $this->templates;
    }

    /**
     * set filtering params
     *
     * @param Array $filters: array of filtering fields.
     * @return self.
     */
    public function setFilters($filters = [])
    {
        $this->filters = $filters;
        $this->type = $this->ine('type', $filters);
        $this->parentId = $this->ine('parent_id', $filters);
        $this->limit = $this->ine('limit', $filters);
        $this->page = $this->ine('page', $filters);
        $this->deletedTemplates = $this->ine('deleted_templates', $this->filters);

        return $this;
    }


    /**
     * bind query builders and get filtered templates with folders.
     *
     * @return self.
     */
    public function bind()
    {
        $keyword = isSetNotEmpty($this->filters,'q');
        $title = $this->ine('title', $this->filters);

        $builder = $this->searchCriteria->make();

        $this->folders = $builder->setTemplateBuilder($this->builder)
                                ->setParentId($this->parentId)
                                ->setLimit($this->limit)
                                ->whereKeywordSearch($keyword)
                                ->whereDeleted($this->deletedTemplates)
                                ->whereTitle($title)
                                ->whereType($this->type)
                                ->applyFilters($this->filters)
                                ->loadRelationships()
                                ->applySorting()
                                ->get();

        $ids = $this->searchCriteria->getReferenceIds();
        $this->getFiles($ids);
        $this->mergeData();
        return $this;
    }

    /**
     * Append is directory filter to get dir/files on the basis of filtering param.
     *
     * @param Eloquent $builder: Folder
     * @return Folder
     */
    protected function appendDirFilter($builder)
    {
        $filters = $this->filters;
        if(!isset($filters['is_dir']) || $filters['is_dir'] == '') {
            return $builder;
        }
        $isDir = false;
        if($filters['is_dir'] == 1) {
            $isDir = true;
        }

        $builder = $builder->whereIsDirectory($isDir);
        return $builder;
    }

    /**
     * get template files from the templates table.
     *
     * @return boolean (true)
     */
    public function getFiles($ids = [])
    {
        $builder = $this->builder->whereIn('templates.id', (array) $ids);
        $this->files = $builder->get();
        return true;
    }

    /**
     * Check key exists in array or not. If key not exists then return detail value.
     *
     * @param String $key: string of key in array.
     * @param Array $arr: array of field values.
     * @param Mix $default: (optional) set default value if key not exist in array.
     * @return Mix value from the array key.
     */
    private function ine($key, $arr, $default = null)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Merge file and folder data and return collection of items on the basis of limit.
     *  If limit is not set then get all the folder/files.
     *  If limit is set then return pagination object of folder files.
     *
     * @return boolean (true)
     */
    private function mergeData()
    {

        $files = $this->files;
        $folders = $this->folders;
        $this->templates = [];

        if($this->deletedTemplates) {

            if ($folders) {
                foreach ($folders as $item) {
                    if ($item->is_dir) {
                        $this->templates[] = $item;
                    } else {
                        $this->folderReferenceFiles[] = $item;
                        $this->templates[] = $this->fileFindById($item->reference_id);
                    }
                }
            }

        } else {

            if ($folders) {
                foreach ($folders as $item) {
                    if ($item->is_dir) {
                        $this->templates[] = $item;
                    } else {
                        $this->folderReferenceFiles[] = $item;
                    }
                }
            }

            if($files) {
                foreach($files as $file) {
                    $file['parent_id'] = $this->findParentIdByRefId($file->id);
                    $file['ancestors'] = $this->getFileAncestors($file->id);
                    $this->templates[] = $file;
                }
            }
        }

        if(!$this->limit) {
            return $this->templates;
        }
        $this->templates = Paginator::make($this->templates, $this->folders->getTotal(), $this->folders->getPerPage());
        return true;
    }

    /**
     * file findById
     *
     * @param Integer $id
     * @return Template
     */
    private function fileFindById($id)
    {
        $item = null;
        foreach($this->files as $file) {
            if($file->id != $id) {
                continue;
            }
            $item = $file;
            $item['parent_id'] = $this->findParentIdByRefId($item->id);
            $item['ancestors'] = $this->getFileAncestors($item->id);
        }
        return $item;
    }

    private function getFileAncestors($fileId)
    {
        if(!$this->folders || !$this->hasIncludedAncestors()) {
            return [];
        }
        $ancestors = [];
        foreach($this->folders as $folder) {
            if($folder->reference_id == $fileId) {
                $ancestors = $folder->ancestors();
                break;
            }
        }
        return $ancestors;
    }

    private function hasIncludedAncestors()
    {
        if(!ine($this->filters,'includes')) {
            return false;
        }
        $includes = $this->filters['includes'];
        if(in_array('ancestors', $includes)) {
            return true;
        }
        return false;
    }

    private function findParentIdByRefId($refId)
    {
        if(!$this->folderReferenceFiles) {
            return $this->parentId;
        }
        $parentId = null;
        foreach ($this->folderReferenceFiles as $reference) {
            if($reference->reference_id == $refId) {
                $parentId = $reference->parent_id;
                break;
            }
        }
        return $parentId;
    }
}