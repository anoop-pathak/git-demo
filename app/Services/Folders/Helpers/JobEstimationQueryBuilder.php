<?php
namespace App\Services\Folders\Helpers;

use App\Models\Folder;
use App\Repositories\FolderRepository;
use App\Services\Contexts\Context;
use App\Models\Estimation;
use App\Services\Folders\Helpers\BaseQueryBuilder;
use Illuminate\Support\Facades\Auth;

class JobEstimationQueryBuilder extends BaseQueryBuilder
{
    protected $entityType = Folder::JOB_ESTIMATION;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->items = [];
        $this->otherTableName = (new Estimation)->getTable();
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
            'dir_children',
            'createdBy',
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('deleted_by', $includes)) {
            $with[] = 'deletedBy';
        }
        $with["doc_children"] = function($q) {
            $estimationTable = (new Estimation)->getTable();
            $q->join("$estimationTable as estimations", function($j) {
                $j->on('estimations.id', '=', 'folders.reference_id');
            });
            $q = $this->withQueryBuilder($q);
        };

        return $with;
    }

    private function withQueryBuilder($query)
    {
        $filters = $this->filters;
        if(Auth::user()->isSubContractorPrime()) {
            $query->where('estimations.created_by', Auth::id());
        }

        if(ine($filters,'deleted_estimations')) {
            $query->whereNull('estimations.deleted_at');
        }
    }
}