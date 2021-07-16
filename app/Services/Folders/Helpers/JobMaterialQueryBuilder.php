<?php
namespace App\Services\Folders\Helpers;

use App\Services\Folders\Helpers\BaseQueryBuilder;
use App\Models\Folder;
use App\Models\MaterialList;
use App\Repositories\FolderRepository;
use App\Services\Contexts\Context;

class JobMaterialQueryBuilder extends BaseQueryBuilder
{
    protected $entityType = Folder::JOB_MATERIAL_LIST;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->items = [];
        $this->otherTableName = (new MaterialList)->getTable();
    }
}