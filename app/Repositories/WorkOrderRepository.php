<?php

namespace App\Repositories;

use App\Models\MaterialList;
use App\Services\Contexts\Context;
use App\Services\Folders\Helpers\JobWorkOrderQueryBuilder;
use Illuminate\Support\Facades\Auth;

class WorkOrderRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    /**
     * Company Scope
     * @var Scope
     */
    protected $scope;
    protected $jobProposalsQueryBuilder;

    function __construct(MaterialList $model, Context $scope, JobWorkOrderQueryBuilder $jobProposalsQueryBuilder)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->jobProposalsQueryBuilder = $jobProposalsQueryBuilder;
    }

    /**
     * Save Material
     * @param  int $jobId job id
     * @param  int $worksheetId worksheet id
     * @param  string $title title
     * @param  int $serialNumber serial number
     * @param  array $meta Meta information
     * @return WorkOrder
     */
    public function save($jobId, $worksheetId, $linkType, $linkId, $serialNumber, $createdBy, $meta = [])
    {

        $title = "";
        if (isset($meta['title']) && (strlen($meta['title']) > 0)) {
            $title = $meta['title'];
        }

        $workOrder = $this->model->create([
            'company_id' => $this->scope->id(),
            'job_id' => $jobId,
            'title' => $title,
            'worksheet_id' => $worksheetId,
            'link_type' => $linkType,
            'link_id' => $linkId,
            'serial_number' => $serialNumber,
            'created_by' => $createdBy,
            'type'          => MaterialList::WORK_ORDER,
            'measurement_id' => ine($meta, 'measurement_id') ? $meta['measurement_id'] : null,
        ]);

        if (!strlen($title)) {
            $workOrder->generateName();
        }

        return $workOrder;
    }

    /**
     * Save File Data
     * @param  Int $jobId Job Id
     * @param  String $fileName File Name
     * @param  String $filePath File Path
     * @param  String $mimeType Mime Type
     * @param  Int $fileSize File Size
     * @param  Int $createdBy User Id
     * @param  array $meta Array
     * @return Workorder
     */
    public function saveUploadedFile($jobId, $fileName, $filePath, $mimeType, $fileSize, $serialNumber, $createdBy, $thumb)
    {
        $workOrder = $this->model->create([
            'company_id' => $this->scope->id(),
            'job_id' => $jobId,
            'type' => MaterialList::WORK_ORDER,
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

        return $workOrder;
    }

    /**
     * Get filtered material lists
     * @param  Array        $filters      Array of filters
     * @return QueryBuilder $queryBuilder QueryBuilder
     */
    public function get($filters)
    {
        $includeData = $this->includeData($filters);
        $query = $this->make($includeData)->orderBy('id', 'desc');
        $query->whereType(MaterialList::WORK_ORDER);
        $this->applyFilters($query, $filters);
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
		/* $service = $this->jobProposalsQueryBuilder->setBuilder($builder)
            ->setFilters($filters)
            ->bind();
        $templates = $service->get(); */
        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
		if(!$limit) {
            $templates = $builder->get();
        } else {
            $templates = $builder->paginate($limit);
        }
		return $templates;
    }

    /**
     * Get filtered material lists
     * @param  Array $filters Array of filters
     * @return QueryBuilder $queryBuilder QueryBuilder
     */
    public function getFilteredWorkOrders($filters)
    {
        $includeData = $this->includeData($filters);
        $query = $this->make($includeData)->orderBy('id', 'desc');

        $query->whereType(MaterialList::WORK_ORDER);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Get work order by id
     * @param  Int $id Work Order Id
     * @param  array $with Relations
     * @return Work Order
     */
    public function getById($id, array $with = [])
    {
        $workOrder = $this->make()
            ->whereType(MaterialList::WORK_ORDER);

        if(Auth::user()->isSubContractorPrime()) {
            $workOrder->whereCreatedBy(Auth::id());
        }
        return $workOrder->findOrFail($id);
    }

    /***************** Private Method *******************/

    /**
     * Apply filter on query builder
     * @param  QueryBuilder $query Query Builder
     * @param  Array $filters Filters
     * @return Void
     */
    private function applyFilters($query, $filters)
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

    /**
     * Include data
     * @param  array $input Array input
     * @return array
     */
    private function includeData($input = [])
    {
        $with = [
            'linkedEstimate.linkedProposal',
            'linkedProposal.linkedEstimate',
            'linkedProposal.worksheet',
            'linkedEstimate.worksheet',
            'linkedEstimate.linkedProposal.worksheet',
            'linkedProposal.linkedEstimate.worksheet',
        ];
        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }


        if (in_array('schedules', $includes)) {
            $with[] = 'schedules.recurrings';
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
