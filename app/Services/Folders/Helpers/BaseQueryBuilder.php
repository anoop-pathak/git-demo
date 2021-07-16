<?php
namespace App\Services\Folders\Helpers;

use App\Models\Job;
use Paginator;
use App\Repositories\FolderRepository;
use App\Services\Contexts\Context;
use App\Models\Folder;

class BaseQueryBuilder
{

    protected $repository;
    protected $scope;
    protected $sortable = true;
    protected $builder;
    protected $filters;
    protected $job;
    protected $parentId;
    protected $limit;
    protected $entityType;
    protected $items;

    // files and folders
    protected $files;
    protected $folders;
    protected $otherTableName;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->items = [];
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
     * get collection of items.
     *
     * @return Collection of items.
     */
    public function get()
    {
        return $this->items;
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
        $this->job = $this->getJob();
        $this->parentId = $this->ine('parent_id', $filters);
        $this->limit = $this->ine('limit', $filters, config('jp.pagination_limit'));
        $this->page = $this->ine('page', $filters);
        return $this;
    }

    /**
     * Get job detail using job id from request.
     *
     * @return Job model instance.
     */
    public function getJob()
    {
        $job = Job::where('id', $this->ine('job_id', $this->filters))
                    ->withTrashed()
                    ->first();
        return $job;
    }

    /**
     * Get parent id.
     *  If parent id is set in request then get requested parent id.
     *  and if parent id is not set in request then find parent id
     *  on the basis of requested type and company id.
     */
    public function getParentId()
    {
        if($this->parentId) {
            return $this->parentId;
        }

        $path = [];
		if($this->scope->has()) {
			$path[] = $this->scope->id();
		}
        $path[] = 'jobs';
        $path[] = $this->job->number;
        $path[] = $this->entityType;

        $parentId = null;
		foreach ($path as $keyName) {
			$parentId = $this->repository->findOrCreateByName($keyName, $parentId);
        }
        $this->parentId = $parentId;
        return $parentId;
    }

    /**
     * bind query builders and get filtered items with folders.
     *
     * @return self.
     */
    public function bind()
    {
        $this->getParentId();
        $this->getFolders();
        $this->getFiles();
        $this->mergeData();
        return $this;
    }

    /**
     * get folders listing on the basis of requested parameters.
     *
     * @return boolean (true)
     */
    public function getFolders()
    {
        $tableName = (new Folder)->getTable();
        $builder = clone $this->builder;
        $fileIds = $builder->pluck('id')->toArray();
        $folderBuilder = Folder::whereParentId($this->parentId)
                                ->where(function($q) use($fileIds) {
                                    $q->whereIn('reference_id', (array)$fileIds)
                                        ->orWhere('is_dir', true);
                                })
                                ->orderBy('is_dir', 'desc');

        if($this->sortable) {
            $folderBuilder = $folderBuilder->Sortable();
        }

        if($this->scope->has()) {
            $folderBuilder = $folderBuilder->where("$tableName.company_id", $this->scope->id());
        }

        $folderBuilder = $this->appendNameFiltering($folderBuilder);
        $folderBuilder = $this->appendDirFilter($folderBuilder);
        $folderBuilder = $folderBuilder->selectRaw("$tableName.*");

        $with = $this->includeData($this->filters);
        $folderBuilder = $folderBuilder->with($with);

        if($this->limit) {
            $this->folders = $folderBuilder->paginate($this->limit);
            return true;
        }
        $this->folders = $folderBuilder->get();
        return true;
    }

    protected function appendNameFiltering($builder)
    {
        return $builder;
    }

    /**
     * get template files from the templates table.
     *
     * @return boolean (true)
     */
    public function getFiles()
    {
        $fileIds = $this->getFileIds();
        if(!$fileIds) {
            return false;
        }
        $rTableName = $this->otherTableName;
        $builder = $this->builder;
        $builder = $builder->whereIn("$rTableName.id", (array) $fileIds);
        $this->files = $builder->get();
        return true;
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
        if($files) {
            foreach($files as $file) {
                $file['parent_id'] = $this->parentId;
                $this->items[] = $file;
            }
        }

        if(!$this->limit) {
            return $this->items;
        }
        $this->items = Paginator::make($this->items, $this->folders->getTotal(), $this->folders->getPerPage());
        return true;
    }

    protected function includeData($filter = array())
    {
        $with = [
            'children'
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('created_by', $includes))	 {
			$with[] = 'createdBy.profile';
		}

        return $with;
    }

    /**
     * Get file ids from folders collection.
     *
     * @return array of file ids.
     */
    private function getFileIds()
    {
        $fileIDs = [];
        foreach($this->folders as $item) {
            if(!$item->is_dir) {
                $fileIDs[] = $item->reference_id;
            } else {
                $this->items[] = $item;
            }
        }
        return $fileIDs;
    }
}