<?php
namespace App\Services\Folders\Helpers;

use App\Models\Folder;
use App\Repositories\FolderRepository;
use App\Services\Contexts\Context;
use App\Models\Measurement;
use App\Services\Folders\Helpers\BaseQueryBuilder;

class JobMeasurementsQueryBuilder extends BaseQueryBuilder
{
    protected $entityType = Folder::JOB_MEASUREMENT;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->items = [];
        $this->otherTableName = (new Measurement)->getTable();
    }

    protected function includeData($filter = array())
    {
        $with = [
            'children',
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('created_by', $includes))	 {
			$with[] = 'createdBy.profile';
		}

        return $with;
    }
}