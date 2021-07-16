<?php

namespace App\Repositories;

use App\Models\Estimation;
use App\Models\SerialNumber;
use App\Models\Template;
use App\Models\TemplateUse;
use App\Services\Contexts\Context;
use App\Services\SerialNumbers\SerialNumberService;
use Illuminate\Support\Facades\Auth;
use App\Services\Folders\Helpers\JobEstimationQueryBuilder;

class EstimationsRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    protected $jobEstimationQueryBuilder;

    function __construct(Estimation $model, Context $scope, JobEstimationQueryBuilder $jobEstimationQueryBuilder, SerialNumberService $serialNoService)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->serialNoService = $serialNoService;
        $this->jobEstimationQueryBuilder = $jobEstimationQueryBuilder;
    }

    public function saveEstimation($jobId, $createdBy, $data = [])
    {

        if (ine($data, 'serial_number')) {
            $serialNumber = $data['serial_number'];
        } else {
            $serialNumber = $this->serialNoService->generateSerialNumber(SerialNumber::ESTIMATE);
        }
        $title = "";
        if (isset($data['title']) && (strlen($data['title']) > 0)) {
            $title = $data['title'];
        }

        $estimation = new Estimation;
        $estimation->company_id = $this->scope->id();
        $estimation->job_id = $jobId;
        $estimation->title = $title;
        $estimation->is_mobile = ine($data, 'is_mobile') ? $data['is_mobile'] : false;
        $estimation->ev_report_id = ine($data, 'ev_report_id') ? $data['ev_report_id'] : null;
        $estimation->ev_file_type_id = ine($data, 'ev_file_type_id') ? $data['ev_file_type_id'] : null;
        $estimation->worksheet_id = ine($data, 'worksheet_id') ? $data['worksheet_id'] : null;
        $estimation->created_by = $createdBy;
        $estimation->serial_number = $serialNumber;
        if (ine($data, 'page_type')) {
            $estimation->page_type = $data['page_type'];
        }

        //save file data..
        $estimation->is_file = ine($data, 'is_file');
        $estimation->file_name = ine($data, 'file_name') ? $data['file_name'] : null;
        $estimation->file_path = ine($data, 'file_path') ? $data['file_path'] : null;
        $estimation->file_mime_type = ine($data, 'file_mime_type') ? $data['file_mime_type'] : null;
        $estimation->file_size = ine($data, 'file_size') ? $data['file_size'] : null;
        $estimation->thumb = ine($data, 'thumb') ? $data['thumb'] : null;

        $estimation->google_sheet_id = ine($data, 'google_sheet_id') ? $data['google_sheet_id'] : null;
        $estimation->sm_order_id = ine($data, 'sm_order_id') ? $data['sm_order_id'] : null;
        $estimation->xactimate_file_path = ine($data, 'xactimate_file_path') ? $data['xactimate_file_path'] : null;
        $estimation->measurement_id      = ine($data, 'measurement_id') ? $data['measurement_id'] : null;
        $estimation->clickthru_estimate_id      = ine($data, 'clickthru_estimate_id') ? $data['clickthru_estimate_id'] : null;
        $estimation->estimation_type            = ine($data, 'estimation_type') ? $data['estimation_type'] : null;
        $estimation->save();

        // track template
        if (ine($data, 'template_ids')) {
            Template::trackTemplateUses($data['template_ids'], TemplateUse::ESTIMATE);
        }

        if (!strlen($title)) {
            $estimation->generateName();
        }

        return $estimation;
    }

     /**
     * Get all the estimations on the basis of requested parameters.
     *
     * @param Array $filters: array of filtering parameters.
     * @return Collection of Eloquent model instance.
     */
    public function get($filters)
    {
        $with = $this->includeData($filters);
        $estimations = $this->make($with);

        $orderByCol = "id";
        if(ine($filters, 'deleted_estimations')) {
            $orderByCol = "deleted_at";
        }
        $estimations->orderBy("{$orderByCol}", 'desc');

        if(ine($filters, 'job_id')) {
            $estimations->byJob($filters['job_id']);
        }
        $estimations->select('estimations.*');
        $this->applyFilters($estimations, $filters);

        $estimations = $this->getEstimationsAlongWithFolders($estimations, $filters);

        return $estimations;
    }

    /**
     * Query on estimations table to get estimations.
     * and also create query to get Folders along with estimations.
     *
     * @param Eloquent $builder: Eloquent query builder.
     * @param Array $filters: array of filtering parameters.
     * @return Collection of Eloquent model instance.
     */
    public function getEstimationsAlongWithFolders($builder, $filters = [])
	{
		/* $service = $this->jobEstimationQueryBuilder->setBuilder($builder)
            ->setFilters($filters)
            // ->setSortable($sortable)
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

    public function getEstimations($filters)
    {

        $with = $this->includeData($filters);
        $estimations = $this->make($with);
        $orderByCol = "id";

        if(ine($filters, 'deleted_estimations')) {
            $orderByCol = "deleted_at";
        }

        $estimations->orderBy("{$orderByCol}", 'desc');

        if(ine($filters, 'job_id')) {
            $estimations->byJob($filters['job_id']);
        }

        $this->applyFilters($estimations, $filters);

        return $estimations;
    }

    public function isExistSerialNumber($serialNumber)
    {
        $currentSN = $this->serialNoService->getCurrentSerialNumber(SerialNumber::ESTIMATE);

        return ((int)$serialNumber <= (int)$currentSN);
    }

    /**
     * get serial number of proposal
     * @return counts:int
     */
    public function getSerialNumber()
    {
        return $this->serialNoService->generateSerialNumber(SerialNumber::ESTIMATE);
    }

    /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = array())
    {
        $query = $this->make($with);
         if(Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(Auth::id());
        }
         return $query->findOrFail($id);
    }
     /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function findById($id, array $with = array())
    {
        $query = $this->make($with);
         if(Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(Auth::id());
        }
         return $query->whereId($id)->first();
    }

    /******************** Private Function *********************/

    private function applyFilters($query, $filters)
    {
        if(!Auth::user()->hasPermission('view_unit_cost')) {
            $query->excludeUnitCostWorksheet();
        }


        if(Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(Auth::id());
        }

        if(ine($filters,'deleted_estimations')) {
            $query->onlyTrashed();
        }

        if (!ine($filters, 'with_ev_reports')) {
            $query->whereNull('ev_report_id');
        }

        if (!ine($filters, 'multi_page')) {
            $query->has('pages', '<=', 1);
        }

        if(Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(Auth::id());
        }

        // include first page..
        $query->with([
            'firstPage' => function ($query) use ($filters) {
                if (ine($filters, 'without_content')) {
                    $query->select('id', 'image', 'estimation_id', 'thumb');
                }
            }
        ]);

        // include pages..
        $query->with([
            'pages' => function ($query) use ($filters) {
                if (ine($filters, 'without_content')) {
                    $query->select('id', 'image', 'estimation_id', 'thumb');
                }
            }
        ]);
    }

    private function includeData($filter = [])
    {
        $with = [
            'createdBy',
            'documentExpire',
            // 'linkedProposal.linkedMaterialList',
            // 'linkedMaterialList',
            'linkedProposal.linkedMaterialLists',
            'linkedMaterialLists',
            'worksheet',
            'linkedWorkOrder',
            'linkedProposal.linkedWorkOrder'
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('worksheet.suppliers', $includes)) {
            $with[] = 'worksheet.suppliers';
        }

        if(in_array('linked_measurement', $includes)) {
            $with[] = 'measurement';
        }

        if(in_array('job', $includes)) {
            $with[] = 'job.customer.phones';
            $with[] = 'job.jobMeta';
        }

        if(in_array('deleted_by', $includes)) {
            $with[] = 'deletedBy';
        }

        if(in_array('worksheet.qbd_queue_status', $includes)) {
            $with[] = 'worksheet.qbDesktopQueue';
        }

        if(in_array('my_favourite_entity', $includes)) {
            $with[] = 'myFavouriteEntity';
        }

        return $with;
    }
}
