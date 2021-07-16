<?php
namespace App\Services\Folders\Helpers;

use App\Services\Folders\Helpers\BaseQueryBuilder;
use App\Models\Folder;
use App\Repositories\FolderRepository;
use App\Services\Contexts\Context;
use App\Models\Proposal;

class JobProposalsQueryBuilder extends BaseQueryBuilder
{
    protected $entityType = Folder::JOB_PROPOSAL;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->items = [];
        $this->otherTableName = (new Proposal)->getTable();
    }

    protected function appendNameFiltering($builder)
    {
        if(!ine($this->filters,'title')) {
            return $builder;
        }
        $tbl = $this->otherTableName;
        $title = $this->filters['title'];

        $builder = $builder->leftJoin("$tbl as t", function($join) {
                                    $join->on('t.id', '=', 'folders.reference_id');
                                });
        $builder = $builder->where(function($q) use($title) {
                                    $q->where('name', 'LIKE', "%$title%")
                                        ->orWhere('t.title', 'LIKE', "%$title%");
                                });
        return $builder;
    }

    protected function includeData($filter = array())
    {
        $with = [
            'children',
            'createdBy',
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('deleted_by', $includes)) {
            $with[] = 'deletedBy';
        }

        return $with;
    }
}