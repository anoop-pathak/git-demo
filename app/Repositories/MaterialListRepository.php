<?php

namespace App\Repositories;

use App\Models\Material;
use App\Models\MaterialList;
use App\Models\Supplier;
use App\Services\Contexts\Context;
use App\Services\Folders\Helpers\JobMaterialQueryBuilder;
use Illuminate\Support\Facades\Auth;

class MaterialListRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    protected $jobMaterialQueryBuilder;

    function __construct(MaterialList $model, Context $scope, JobMaterialQueryBuilder $jobMaterialQueryBuilder)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->jobMaterialQueryBuilder = $jobMaterialQueryBuilder;
    }

    /**
     * Get filtered material lists
     * @param  Array        $filters      Array of filters
     * @return QueryBuilder $queryBuilder QueryBuilder
     */
    public function get($filters)
    {
        $with = $this->includeData($filters);
        $query = $this->make($with)
                    ->orderBy('id', 'desc');
        $query->whereType(MaterialList::MATERIAL_LIST);
        $this->applyFilters($query, $filters);

        $query->select(array_merge(MaterialList::getFillableFields(), ['id']));
        $items = $this->getWorkOrderAlongWithFolders($query, $filters);

        return $items;
    }

    /**
     * Query on work orders table to get work orders.
     * and also create query to get Folders along with work orders.
     *
     * @param Eloquent $builder: Eloquent query builder.
     * @param Array $filters: array of filtering parameters.
     * @return Collection of Eloquent model instance.
     */
    public function getWorkOrderAlongWithFolders($builder, $filters = [])
	{
		/*$service = $this->jobMaterialQueryBuilder->setBuilder($builder)
            ->setFilters($filters)
            ->bind();
		$templates = $service->get();
        return $templates; */
        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
		if(!$limit) {
            $templates = $builder->get();
        } else {
            $templates = $builder->paginate($limit);
        }
        return $templates;
    }

    /**
     * Save Material
     * @param  int $jobId job id
     * @param  int $worksheetId worksheet id
     * @param  string $title title
     * @param  int $serialNumber serial number
     * @param  array $meta Meta information
     * @return Material List
     */
    public function save($jobId, $worksheetId, $linkType, $linkId, $serialNumber, $createdBy, $meta = [])
    {
        $title = "";
        if (isset($meta['title']) && (strlen($meta['title']) > 0)) {
            $title = $meta['title'];
        }

        $materialList = MaterialList::create([
            'company_id' => $this->scope->id(),
            'job_id' => $jobId,
            'title' => $title,
            'worksheet_id' => $worksheetId,
            'link_type' => $linkType,
            'link_id' => $linkId,
            'serial_number' => $serialNumber,
            'created_by' => $createdBy,
            'type' => ine($meta, 'type') ? $meta['type'] : MaterialList::WORK_ORDER,
            'for_supplier_id' => ine($meta, 'for_supplier_id') ? $meta['for_supplier_id'] : null,
            'measurement_id'  => ine($meta,'measurement_id') ? $meta['measurement_id'] : null,
        ]);

        if (!strlen($title)) {
            $materialList->generateName();
        }

        if ($materialList->for_supplier_id
            && ($supplier = Supplier::find($materialList->for_supplier_id))
            && $supplier->name = Supplier::SRS_SUPPLIER
                && ($companySupplier = $supplier->companySupplier)) {
            $materialList->branch_detail = [
                'branch_logo' => $companySupplier->srs_branch_detail['branch_logo'],
                'branch_name' => $companySupplier->branch,
                'branch_code' => $companySupplier->branch_code,
            ];

            $materialList->save();
        }

        return $materialList;
    }

    /**
     * Save File Data
     * @param  Int $jobId Job Id
     * @param  String $type Type
     * @param  String $fileName File Name
     * @param  String $filePath File Path
     * @param  String $mimeType Mime Type
     * @param  Int $fileSize File Size
     * @param  Int $createdBy User Id
     * @param  array $meta Array
     * @return MaterialList
     */
    public function saveUploadedFile($jobId, $type, $fileName, $filePath, $mimeType, $fileSize, $serialNumber, $createdBy, $thumb)
    {
        $materialList = MaterialList::create([
            'company_id' => $this->scope->id(),
            'job_id' => $jobId,
            'type' => $type,
            'title' => $fileName,
            'is_file' => true,
            'created_at' => $createdBy,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_mime_type' => $mimeType,
            'file_size' => $fileSize,
            'created_by' => $createdBy,
            'serial_number' => $serialNumber,
            'thumb' => $thumb
        ]);

        return $materialList;
    }

    /**
     * Get filtered material lists
     * @param  Array $filters Array of filters
     * @return QueryBuilder $queryBuilder QueryBuilder
     */
    public function getFilteredMaterials($filters)
    {
        $with = $this->includeData($filters);
        $query = $this->make($with)
            ->orderBy('id', 'desc');

        $query->whereType(MaterialList::MATERIAL_LIST);

        $this->applyFilters($query, $filters);

        $query->select(array_merge(MaterialList::getFillableFields(), ['id']));

        return $query;
    }

    public function getById($id, array $with = array())
    {
        $materialList = $this->make()
            ->whereType(MaterialList::MATERIAL_LIST);
         if(Auth::user()->isSubContractorPrime()) {
            $materialList->whereCreatedBy(Auth::id());
        }
         return $materialList->findOrFail($id);
    }


    public function applyFilters($query, $filters)
    {
        if(!Auth::user()->hasPermission('view_unit_cost')) {
            $query->excludeUnitCostWorksheet();
        }

        if (ine($filters, 'job_id')) {
            $query->whereJobId($filters['job_id']);
        }

        if(Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(Auth::id());
        }
    }

    private function includeData($filter = [])
    {
        $with = [
            'linkedEstimate.linkedProposal',
            'linkedProposal.linkedEstimate',
            'linkedProposal.worksheet',
            'linkedEstimate.worksheet',
            'linkedEstimate.linkedProposal.worksheet',
            'linkedProposal.linkedEstimate.worksheet',
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('worksheet', $includes)) {
            $with[] = 'worksheet';
        }

        if (in_array('worksheet.suppliers', $includes)) {
            $with[] = 'worksheet.suppliers';
        }

        if(in_array('linked_measurement', $includes)) {
            $with[] = 'measurement';
        }

        if(in_array('my_favourite_entity', $includes)) {
            $with[] = 'myFavouriteEntity';
        }

        return $with;
    }
}
