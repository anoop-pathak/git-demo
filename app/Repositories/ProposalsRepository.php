<?php

namespace App\Repositories;

use App\Models\Proposal;
use App\Models\SerialNumber;
use App\Models\Template;
use App\Models\TemplateUse;
use App\Services\Contexts\Context;
use App\Services\SerialNumbers\SerialNumberService;
use Illuminate\Support\Facades\Auth;
use App\Services\Folders\Helpers\JobProposalsQueryBuilder;

class ProposalsRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    protected $jobProposalsQueryBuilder;

    function __construct(Proposal $model, Context $scope, SerialNumberService $serialNoService, JobProposalsQueryBuilder $jobProposalsQueryBuilder)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->serialNoService = $serialNoService;
        $this->jobProposalsQueryBuilder = $jobProposalsQueryBuilder;
    }

    public function saveProposal($jobId, $createdBy, $data = [])
    {

        if (ine($data, 'serial_number')) {
            $serialNumber = $data['serial_number'];
        } else {
            $serialNumber = $this->generateSerialNumber();
        }

        $title = "";
        if (isset($data['title']) && (strlen($data['title']) > 0)) {
            $title = $data['title'];
        }

        $proposal = new Proposal;
        $proposal->company_id = $this->scope->id();
        $proposal->job_id = $jobId;
        $proposal->title = $title;
        $proposal->is_mobile = ine($data, 'is_mobile') ? $data['is_mobile'] : false;
        $proposal->insurance_estimate = ine($data, 'insurance_estimate') ? $data['insurance_estimate'] : false;
        $proposal->attachments_per_page = ine($data, 'attachments_per_page') ? $data['attachments_per_page'] : config('jp.proposal_attachments_per_page');

        if (ine($data, 'page_type')) {
            $proposal->page_type = $data['page_type'];
        }

        $proposal->created_by = $createdBy;
        $proposal->note = ine($data, 'note') ? $data['note'] : null;
        $proposal->serial_number = $serialNumber;
        $proposal->worksheet_id = ine($data, 'worksheet_id') ? $data['worksheet_id'] : null;

        // link estimate
        $proposal->estimate_id = ine($data, 'estimate_id') ? $data['estimate_id'] : null;
        $proposal->linked_gsheet_url = ine($data, 'linked_gsheet_url') ? $data['linked_gsheet_url'] : null;

        //save file data..
        $proposal->is_file = ine($data, 'is_file');
        $proposal->file_name = ine($data, 'file_name') ? $data['file_name'] : null;
        $proposal->file_path = ine($data, 'file_path') ? $data['file_path'] : null;
        $proposal->file_mime_type = ine($data, 'file_mime_type') ? $data['file_mime_type'] : null;
        $proposal->file_size = ine($data, 'file_size') ? $data['file_size'] : null;
        $proposal->thumb = ine($data, 'thumb') ? $data['thumb'] : null;
        $proposal->initial_signature = ine($data, 'initial_signature');
        $proposal->measurement_id  = ine($data, 'measurement_id') ? $data['measurement_id'] : null;
        $proposal->token  =   generateUniqueToken();
        $proposal->save();

        // track template
        if (ine($data, 'template_ids')) {
            Template::trackTemplateUses($data['template_ids'], TemplateUse::PROPOSAL);
        }

        if (!strlen($title)) {
            $proposal->generateName();
        }

        return $proposal;
    }

    public function get($filters = array())
    {
        $with = $this->includeData($filters);
        $proposals = $this->make($with);
        $orderByCol = "id";
        if(ine($filters, 'deleted_proposals')) {
            $orderByCol = "deleted_at";
        }
        $proposals->orderBy("{$orderByCol}",'desc');
        $proposals->has('job');
        $proposals->select('proposals.*');
        $this->applyFilters($proposals, $filters);
        $proposals = $this->getProposalsAlongWithFolders($proposals, $filters);

        return $proposals;
    }

    /**
     * Query on proposals table to get proposals.
     * and also create query to get Folders along with proposals.
     *
     * @param Eloquent $builder: Eloquent query builder.
     * @param Array $filters: array of filtering parameters.
     * @return Collection of Eloquent model instance.
     */
    public function getProposalsAlongWithFolders($builder, $filters = [])
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

    public function getProposals($filters = array()) {
        $with = $this->includeData($filters);
        $proposals = $this->make($with);
        $orderByCol = "id";

        if(ine($filters, 'deleted_proposals')) {
            $orderByCol = "deleted_at";
        }

        $proposals->orderBy("{$orderByCol}",'desc');
        $proposals->has('job');

        $this->applyFilters($proposals, $filters);

        return $proposals;
    }

    /**
     * get serial number of proposal
     * @return counts:int
     */
    public function getSerialNumber()
    {
        return $this->serialNoService->generateSerialNumber(SerialNumber::PROPOSAL);
    }

    public function isExistSerialNumber($serialNumber)
    {
        $currentSN = $this->serialNoService->getCurrentSerialNumber(SerialNumber::PROPOSAL);

        $fullNumber = explode('-', $serialNumber);
        $currentFullNumber = explode('-', $currentSN);
        if(count($fullNumber) > 1) {
            $serialNumber = $fullNumber[1];
        }
        if(count($currentFullNumber) > 1) {
            $currentSN = $currentFullNumber[1];
        }

        return ((int)$serialNumber <= (int)$currentSN);
    }

    /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = [], $filters = [])
    {
        $query = $this->make($with);
        $query->has('job');
        $filters['multi_page'] = true;
        $this->applyFilters($query, $filters);
        $query->select('proposals.*');

        return $query->findOrFail($id);
    }

    public function generateSerialNumber()
    {
        $serialNumber = $this->serialNoService->generateSerialNumber(SerialNumber::PROPOSAL);

        return $serialNumber;
    }

    public function getProposalWithoutPageContents($id)
    {
        $proposal = $this->make()->whereId($id)->with([
            'pages' => function ($query) {
                $query->select('id', 'proposal_id', 'auto_fill_required', 'title');
            }
        ])->first();

        return $proposal;
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

        if(ine($filters,'deleted_proposals')) {
            $query->onlyTrashed();
        }

        if (!ine($filters, 'multi_page')) {
            $query->has('pages', '<=', 1);
        }

        // include first page..
        $query->with([
            'firstPage' => function ($query) use ($filters) {
                if (ine($filters, 'without_content')) {
                    $query->select('id', 'image', 'proposal_id', 'thumb');
                }
            }
        ]);

        // include pages..
        $query->with([
            'pages' => function ($query) use ($filters) {
                if (ine($filters, 'without_content')) {
                    $query->select('id', 'image', 'proposal_id', 'thumb', 'title', 'auto_fill_required');
                }
            }
        ]);

        // include job trades
        if (ine($filters, 'trades')) {
            $query->trades($filters['trades']);
        }

        // include job worktypes
        if (ine($filters, 'work_types')) {
            $query->workTypes($filters['work_types']);
        }

        //job id
        if (ine($filters, 'job_id')) {
            $query->byJob($filters['job_id']);
        }

        // status
        if (ine($filters, 'status')) {
            $query->whereStatus($filters['status']);
        }

        // date range
        if (ine($filters, 'start_date') && ine($filters, 'end_date')) {
            $startDate = $filters['start_date'];
            $endDate = $filters['end_date'];

            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('proposals.created_at') . ", '%Y-%m-%d') >= '$startDate'")
                ->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('proposals.created_at') . ", '%Y-%m-%d') <= '$endDate'");
        }

        if (ine($filters, 'only_google_sheets')) {
            $query->whereNotNull('google_sheet_id');
        }

        if (ine($filters, 'without_google_sheets')) {
            $query->whereNull('google_sheet_id');
        }

        //insurance claim
        if (isset($filters['insurance_estimate'])) {
            $query->insuranceEstimate($filters['insurance_estimate']);
        }

        if (ine($filters, 'customer_ids')) {
            $query->customer($filters['customer_ids']);
        }

        if (ine($filters, 'customer_name')) {
            $query->customerName($filters['customer_name']);
        }

        if (ine($filters, 'with_trashed')) {
            $query->withTrashed();
        }
    }

    private function includeData($filter = [])
    {
        if (ine($filter, 'stop_eager_loading')) {
            return [];
        }
        $with = [
            'createdBy',
            'documentExpire',
            // 'linkedEstimate.linkedMaterialList',
            // 'linkedMaterialList',
            'linkedMaterialLists',
            'linkedEstimate.linkedMaterialLists',
            'worksheet',
            'linkedWorkOrder',
            'linkedEstimate.linkedWorkOrder'
        ];

        $includes = isset($filter['includes']) ? $filter['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('linked_invoices', $includes)) {
            $with[] = 'invoices.payments';
        }

        if (in_array('job_invoice_count', $includes)) {
            $with[] = 'invoices';
        }

        if (in_array('pages', $includes)) {
            $with[] = 'pages.pageTableCalculations';
        }

        if (in_array('attachments', $includes)) {
            $with[] = 'attachments';
        }

        if (in_array('job', $includes)) {
            $with[] = 'job';
            $with[] = 'job.address.state';
            $with[] = 'job.address.country';
            $with[] = 'job.customer.phones';
        }

        if (in_array('customer', $includes)) {
            $with[] = 'customer';
            $with[] = 'customer.phones';
        }

        if (in_array('worksheet.suppliers', $includes)) {
            $with[] = 'worksheet.suppliers';
        }

        if(in_array('linked_measurement', $includes)) {
            $with[] = 'measurement';
        }

        if(in_array('worksheet.template_pages_ids', $includes)) {
            $with[] = 'worksheet.templatePages';
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

        if(in_array('tables', $includes)) {
            $with[] = 'pageTableCalculations';
        }

        if(in_array('digital_sign_queue_status', $includes)) {
            $with[] = 'digitalSignQueueStatus';
        }

        return $with;
    }
}
