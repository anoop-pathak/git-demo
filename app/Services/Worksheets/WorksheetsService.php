<?php

namespace App\Services\Worksheets;

use App\Events\EstimationCreated;
use App\Events\MaterialListCreated;
use App\Events\ProposalCreated;
use App\Events\WorkOrderCreated;
use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\Estimation;
use App\Models\FinancialCategory;
use App\Models\FinancialDetail;
use App\Models\JobFinancialCalculation;
use App\Models\JobInsuranceDetails;
use App\Models\Material;
use App\Models\SerialNumber;
use App\Models\Worksheet;
use App\Models\WorksheetImage;
use App\Repositories\FinancialRepository;
use App\Repositories\JobRepository;
use App\Services\MaterialLists\MaterialListService;
use App\Services\SerialNumbers\SerialNumberService;
use App\Services\WorkOrders\WorkOrderService;
use App\Transformers\WorksheetTransformer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use PDF;
use FlySystem;
use DataMasking;
use App\Services\Worksheets\MultiTierStructure;
use App\Models\Supplier;
use App\Models\Division;
use App\Models\SupplierBranch;
use QBDesktopQueue;
use App\Models\WorksheetTemplatePage;
use App\Helpers\ProposalPageAutoFillDbElement;
use App\Models\Template;
use App\Models\TempProposalPage;
use App\Models\FinancialProduct;
use App\Models\PageTableCalculation;
use App\Exceptions\WorksheetLeastAmountException;
use App\Events\WorkSheetCreated;
use App\Events\WorkSheetUpdated;
use App\Events\Folders\JobEstimationStoreFile;
use App\Events\Folders\JobMaterialListStoreFile;
use App\Events\Folders\JobProposalStoreFile;
use App\Events\Folders\JobWorkOrderStoreFile;
use App\Services\Folders\FolderService;
use App\Models\Folder;
use App\Models\Proposal;
use App\Exceptions\Proposal\ProposalCannotBeUpdate;

class WorksheetsService
{

    protected $repo;
    protected $jobRepo;
    protected $printColumns;

    public function __construct(FinancialRepository $repo, JobRepository $jobRepo, Larasponse $response, SerialNumberService $serialNoService)
    {
        $this->repo = $repo;
        $this->jobRepo = $jobRepo;
        $this->response = $response;
        $this->serialNoService = $serialNoService;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function createOrUpdateWorksheet($input)
    {
        set_time_limit(0);
        $job = $this->jobRepo->getById($input['job_id']);

        if (ine($input, 'line_tax')) {
            $input['taxable'] = '';
            $input['tax_rate'] = '';
            $input['material_tax_rate'] = '';
            $input['labor_tax_rate'] = '';
        }

        if (ine($input, 'line_margin_markup')) {
            $input['profit'] = '';
        }

        $worksheet = null;
        if (ine($input, 'id')) {
            $worksheet = Worksheet::findOrFail($input['id']);

            if($worksheet->type == Worksheet::PROPOSAL) {
				$proposal = Proposal::where('company_id', getScopeId())
					->where('worksheet_id', $input['id'])
					->first();

				if($proposal->hasDigitalAuthorizationQueue()) {
					throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
				}
			}
        }

        try {

            $unitCost = 0;
			$sellingPrice = 0;

			foreach($input['details'] as $item) {
				if(isset($item['unit_cost'])){
					$unitCost += $item['quantity'] * $item['unit_cost'];
				}
				if(isset($item['selling_price'])){
					$sellingPrice += $item['quantity'] * $item['selling_price'];
				}
			}

			if(($unitCost < 0) && ($sellingPrice < 0)) {
				throw new WorksheetLeastAmountException(trans('response.error.least_amount', [
					'attribute' => 'Worksheet'
				]));
			}

            if ($worksheet) {
                $worksheet = $this->updateWorksheet($worksheet, $input);
            } else {
                if (ine($input, 'save_as')) {
                    $worksheet = Worksheet::findOrFail($input['save_as']);
                    $estimate = $worksheet->jobEstimate;
                    if ($estimate && ($estimate->type == Estimation::XACTIMATE)) {
                        $input['type'] = Estimation::XACTIMATE;
                        // $xactimateFile = $this->copyXactimateFile($estimate);
                        $input['xactimate_file_path'] = $estimate->xactimate_file_path;
                    }
                }

                /* add worksheet */
                $worksheet = $this->addWorksheet($input);
            }

            $worksheetId = $worksheet->id;

            $details = $this->getValideData($input);
            /* add finanacial-details of worksheet */
            $supplierIds = [];
            if (!empty($details)) {
                $supplierIds = $this->saveDetails($details, $worksheetId);
            }

            if (!empty($supplierIds)) {
                $worksheet->suppliers()->sync($supplierIds);
            }

            if($worksheet->srs_old_worksheet) {
				$productIds = $worksheet->finacialDetail()->pluck('product_id')->toArray();
				$oldSrsProductCount = FinancialProduct::where('company_id', getScopeId())
					->whereIn('id', $productIds)
					->where('srs_old_product', true)
					->onlyTrashed()
					->count();
				if(!$oldSrsProductCount) {
					$worksheet->srs_old_worksheet = false;
					$worksheet->save();
				}
			}

            $financialData = $this->updateFinancialSum($worksheet, $input);
            $worksheet = $financialData['worksheet'];
            $data = $financialData['data'];
            $insuranceMeta = $financialData['insuranceMeta'];

            $object = false;

            switch ($worksheet->type) {
                case Worksheet::ESTIMATE:
                    $object = $this->saveJobEstimate($worksheet, $input);
                    break;
                case Worksheet::PROPOSAL:
                    $object = $this->saveJobProposal($worksheet, $input);
                    $worksheet = $this->saveTemplatePages($worksheet, $input);
                    break;
                case Worksheet::MATERIAL_LIST:
                    $object = $this->saveJobMaterialList($worksheet, $input);
                    break;
                case WorksHeet::WORK_ORDER:
                    $object = $this->saveWorkOrder($worksheet, $input);
                    break;
                case Worksheet::PROFIT_LOSS:
                    JobFinancialCalculation::updateProfitLossTotal($job, $data['cost_total']);
                    break;
                case Worksheet::XACTIMATE:
                    $object = $this->saveJobEstimate($worksheet, $input);
                    $this->jobRepo->changeCategory($job);
                    break;
            }

            // Attachments For Work Order
            $this->attachments($worksheet, $input);

            // create pdf..
            DataMasking::disable();
            $subWorksheetOldPath = $worksheet->file_path;
            $worksheet = $this->createPDF($worksheet, $object);

            // create pdf for sub contractor
            if((\Auth::user()->isSubContractorPrime() && \Auth::user()->dataMaskingEnabled())
                || ($object && $object->createdBy && $object->createdBy->isSubContractorPrime() && $object->createdBy->dataMaskingEnabled()))
            {
                DataMasking::enable();
                $this->createPDF($worksheet, $object, false, true, $subWorksheetOldPath);
            }

            // update job insurance details
            if (ine($input, 'update_job_insurance') && !$job->isProject()) {
                $jobInsuranceDetail = [
                    'rcv' => $insuranceMeta['rcv_total'],
                    'acv' => $insuranceMeta['acv_total'],
                    'insurance_number' => $insuranceMeta['claim_number'],
                    'policy_number' => $insuranceMeta['policy_number'],
                    'depreciation'		=> $insuranceMeta['depreciation_total'],
					'net_claim'			=> $insuranceMeta['acv_total'],
					'deductable_amount'	=> 0,
					'supplement'		=> 0,
					'total'				=> $insuranceMeta['rcv_total'],
                ];

                if ($job->insuranceDetails) {
                    $job->insuranceDetails()->update($jobInsuranceDetail);
                } else {
                    $jobInsurance = new JobInsuranceDetails($jobInsuranceDetail);
                    $jobInsurance->job_id = $job->id;
                    $jobInsurance->save();
                }

                DB::table('jobs')->whereCompanyId($job->company_id)
					->whereId($job->id)
	  				->update(['insurance' => true]);
            }

            if($worksheet->qb_desktop_id) {
				QBDesktopQueue::addWorksheet($worksheet);
			} else {
				QBDesktopQueue::queryWorksheet($worksheet);
            }

        } catch (\Exception $e) {
            throw $e;
        }

        if (!Auth::user()->hasPermission('view_unit_cost')) {
			$worksheet->where('enable_selling_price', 1);
		}

        return $worksheet;
    }

    /**
     * Get Worklists
     * @param  Int $jobId Job Id
     * @param  $type         Worksheet type
     * @return Worksheet Collections
     */
    public function getWorksheetList($jobId, $type)
    {
        $worksheets = $this->getWorksheet($jobId, $type);
        $worksheets = $worksheets->get();

        return $this->response->collection($worksheets, new WorksheetTransformer);
    }

    /**
     * Get Worksheets By JobId and Type
     * @param  int $jobId | Job Id
     * @param  string $type | Worksheet Type
     * @return Query Builder
     */
    public function getWorksheet($jobId, $type)
    {
        $worksheet = Worksheet::with('linkedEstimate')
            ->whereJobId($jobId)
            ->whereType($type)
            ->orderBy('created_at', 'desc');

        switch ($type) {
            case 'material_list':
                $worksheet->has('materialList');
                break;
            case 'estimate':
                $worksheet->has('jobEstimate');
                break;
            case 'proposal':
                $worksheet->has('jobProposal');
                break;
            case 'work_order':
                $worksheet->has('workOrder');
                break;
        }

        return $worksheet;
    }

    /**
     * Get Worksheets by Type and job Id
     * @param  int $jobId | Job Id
     * @param  string $type | Worksheet type
     * @return array
     */
    public function getMultipleWorksheets($jobId, $type, $filters = [])
    {
        $data = [];
        $worksheets = $this->getWorksheet($jobId, $type)->get();

        foreach ($worksheets as $worksheet) {
            $data[] = $this->getWorksheetWithDetails($worksheet, $filters);
        }

        return $data;
    }

    /**
     * Get Worksheet with details
     * @param  Instance $worksheet | Worksheet instance
     * @return Array
     */
    public function getWorksheetWithDetails($worksheet, $filters = [])
    {
        $job = $worksheet->job()->first();

        $worksheet = $this->response->item($worksheet, new WorksheetTransformer);

        $worksheet['details'] = $this->repo->getDetails($job->id, $worksheet['id'], $filters);

        $data = [
            'job_id' => $job->id,
            'job_price' => $job->amount,
            'tax_rate' => $job->tax_rate,
            'worksheet' => $worksheet,
        ];

        return $data;
    }

    /**
     * Pdf Preview
     * @param  Array $input Array of inputs
     * @return Response
     */
    public function pdfPreview($input)
    {
        if (ine($input, 'line_tax')) {
            unset($input['tax_rate']);
            unset($input['labor_tax_rate']);
            unset($input['material_tax_rate']);
        }
        if (ine($input, 'line_margin_markup')) {
            unset($input['profit']);
        }

        $job = $this->jobRepo->getById($input['job_id']);
        switch ($input['type']) {
            case Worksheet::MATERIAL_LIST:
                $view = 'worksheets.material-list-temp-preview';
                break;

            case Worksheet::ESTIMATE:
                $view = 'worksheets.estimate-temp-preview';
                break;

            case Worksheet::PROPOSAL:
                $view = 'worksheets.proposal-temp-preview';
                break;
            case Worksheet::WORK_ORDER:
                $view = 'worksheets.work-order-temp-preview';
                break;
            case Worksheet::XACTIMATE:
                $view = 'worksheets.xactimate-temp-preview';
                break;
        }

        $data = $this->getPreviewData($job, $input);

        $orientationMode = $this->getPdfOrientationMode($data['multi_tier'], $data['printFields']);

        $contents = view($view, $data)->render();

        if(ine($input, 'worksheet_id')) {
			$worksheet = Worksheet::findOrFail($input['worksheet_id']);
			$contents = $this->previewTemplateContent($worksheet, $contents);
		}

        $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation($orientationMode);
        $pdf->setOption('dpi', 200);
        $name = uniqueTimestamp();
        $fileName = 'worksheet_preview/' . $name . '.pdf';
        $fullPath = config('jp.BASE_PATH').$fileName;

        FlySystem::write($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);

        return ApiResponse::success([
            'data' => [
                'file_path' => FlySystem::publicUrl($fullPath)
            ]
        ]);
    }

    /**
     * Is Exist Serial Number
     * @param  Int $serialNumber Serial Number Count
     * @param  String $type Proposal|Estimate|MaterialList
     * @return boolean               [description]
     */
    public function isExistSerialNumber($serialNumber, $type)
    {
        $currentSN = $this->serialNoService->getCurrentSerialNumber($type);

        if(in_array($type, [SerialNumber::MATERIAL_LIST, SerialNumber::PROPOSAL])) {
            $srNumbers      = explode('-', $serialNumber);
            $serialNumber	= end($srNumbers);
            $curSrNumbers   = explode('-', $currentSN);
            $currentSN      = (count($curSrNumbers) > 1)  ? $curSrNumbers[1] : $curSrNumbers[0];
        }

        return ((int)$serialNumber <= (int)$currentSN);
    }

    /**
     * get serial number of Material List
     * @return counts:int
     */
    public function getSerialNumber($type)
    {
        return $this->serialNoService->generateSerialNumber($type);
    }

    /**
     * Worksheet get by id
     * @param  Int $id Worksheet Id
     * @return Worksheet
     */
    public function getById($id)
    {
        return Worksheet::where('worksheets.id', $id)
            ->leftJoin(\DB::raw('(SELECT id, company_id FROM jobs WHERE deleted_at IS NULL) as jobs'), 'jobs.id', '=', 'worksheets.job_id')
            ->where('jobs.company_id', getScopeId())
            ->select('worksheets.*')
            ->firstOrFail();
    }

    /**
     * Save Job Material List
     * @param  Instance $worksheet Worksheet
     * @param  Array $input Array of inputs
     * @return Material List
     */
    public function saveJobMaterialList($worksheet, $data)
    {
        $materialList = $worksheet->materialList;

        if (!$materialList) {

            $parentId = ine($data, 'parent_id') ? $data['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_MATERIAL_LIST);
            }

            if (isset($data['name']) && (strlen($data['name']) > 0)) {
                $data['title'] = $data['name'];
            }

            // save materialList..
            $service = App::make(MaterialListService::class);
            $materialList = $service->saveMaterial(
                $worksheet->job_id,
                $worksheet->id,
                $data
            );

            if (!ine($data, 'name')) {
                $worksheet->name = $materialList->title;
                $worksheet->update();
            }

            //material list created event..
            Event::fire('JobProgress.MaterialLists.Events.MaterialListCreated', new MaterialListCreated($materialList));

			$eventData = [
				'name' => $materialList->id,
				'reference_id' => $materialList->id,
				'job_id' => $materialList->job_id,
				'parent_id' => $parentId,
			];
            Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMaterialListStoreFile($eventData));

        } else {
            if(($materialList) && isset($data['measurement_id'])) {
                $materialList->measurement_id = ($data['measurement_id']) ?: null;
                $materialList->save();
            }
        }

        $worksheet->setRelation('materialList', $materialList);

        return $materialList;
    }

    /**
     * Save WorkOrder
     * @param  Instance $worksheet Worksheet
     * @param  Array $input Array of inputs
     * @return WorkOrder
     */
    public function saveWorkOrder($worksheet, $data)
    {
        $workOrder = $worksheet->workOrder;

        if (!$workOrder) {

            $parentId = ine($data, 'parent_id') ? $data['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_WORK_ORDER);
            }

            if (isset($data['name']) && (strlen($data['name']) > 0)) {
                $data['title'] = $data['name'];
            }

            // save work order
            $service = App::make(WorkOrderService::class);
            $workOrder = $service->saveWorkOrder(
                $worksheet->job_id,
                $worksheet->id,
                $data
            );

            //copy worksheet name from workorder
            if (!ine($data, 'name')) {
                $worksheet->name = $workOrder->title;
                $worksheet->update();
            }

            $eventData = [
				'name' => $workOrder->id,
				'reference_id' => $workOrder->id,
				'job_id' => $workOrder->job_id,
				'parent_id' => $parentId,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobWorkOrderStoreFile($eventData));

            //workorder created event..
            Event::fire('JobProgress.WorkOrders.Events.WorkOrderCreated', new WorkOrderCreated($workOrder));
        } else {
            if(($workOrder) && isset($data['measurement_id'])) {
                $workOrder->measurement_id = ($data['measurement_id']) ?: null;
                $workOrder->save();
            }
        }

        $worksheet->setRelation('workOrder', $workOrder);

        return $workOrder;
    }


    /**
     * @ get current order of workshhet
     */
    public function getWorksheetOrder($input)
    {
        $worksheet = $this->getWorksheet($input['job_id'], $input['type'])->first();
        if ($worksheet) {
            return $worksheet->order + 1;
        }

        return 1;
    }

    /**
     * Save Pdf File
     * @param  Worksheet $worksheet Worksheet
     * @return Void
     */
    public function createPDF($worksheet, $object = false, $stopUpdatedAt = false, $subWorksheet = false, $subWorksheetOldPath = null)
    {
        $pdfColumns = $this->getPdfPrintColumns(
            $worksheet->hide_pricing,
            $worksheet->description_only,
            $worksheet->multi_tier,
            $worksheet->show_quantity,
            $worksheet->show_unit,
            [],
            $worksheet->line_tax,
            $worksheet->line_margin_markup,
            $worksheet->show_tier_total,
            $worksheet->show_line_total
        );

        //PDf file not created for selling price & profilt loss analysis sheet
        if (in_array($worksheet->type, [
            Worksheet::SELLING_PRICE,
            Worksheet::PROFIT_LOSS
        ])) {
            return $worksheet;
        }

        switch ($worksheet->type) {
            case Worksheet::SELLING_PRICE:
                $view = 'worksheets.selling-price';
                break;
            case Worksheet::PROFIT_LOSS:
                $view = 'worksheets.profit-loss';
                break;

            case Worksheet::MATERIAL_LIST:
                $view = 'worksheets.material-list';
                break;

            case Worksheet::ESTIMATE:
                $view = 'worksheets.estimate';
                break;

            case Worksheet::PROPOSAL:
                $view = 'worksheets.proposal';
                break;

            case Worksheet::WORK_ORDER:
                $view = 'worksheets.work-order';
                break;

            case Worksheet::XACTIMATE:
                $view = 'worksheets.xactimate';
                break;
        }

        $oldFilePath = $worksheet->file_path;
        $oldThumb = $worksheet->thumb;
        $job = $worksheet->job;
        $financialDetails = $this->repo->getFinancialDetails($worksheet->job_id, $worksheet->id);

        if ($worksheet->type != Worksheet::XACTIMATE) {
            $multiStructure = new MultiTierStructure();
            $multiStructure->margin = $worksheet->margin;
            $multiStructure->line_tax = $worksheet->line_tax;
            $multiStructure->line_margin_markup = $worksheet->line_margin_markup;
            $financialDetails = $multiStructure->makeMultiTierStructure(
                $financialDetails,
                $addSum = true,
                $worksheet->enable_selling_price
            );
        }

        $financialCalculation = $job->financialCalculation;
        $categories = $this->repo->getFinancialCategory($worksheet->job_id, $worksheet->id);
        $attachments = WorksheetImage::whereWorksheetId($worksheet->id)->get();
        $company = $job->company;
        $data = [
            'job' => $job,
            'customer' => $job->customer,
            'financial_calculation' => $financialCalculation,
            'financial_details' => $financialDetails,
            'categories' => $categories,
            'company' => $company,
            'estimate' => $worksheet->jobEstimate,
            'proposal' => $worksheet->jobProposal,
            'material_list' => $worksheet->materialList,
            'work_order' => $worksheet->workOrder,
            'worksheet' => $worksheet,
            'company_country_code' => $company->country->code,
            'attachments' => $attachments,
            'attachments_per_page' => $worksheet->attachments_per_page,
            'printFields' => $pdfColumns,
        ];

        $contents = \view($view, $data)->render();

        // attach template with proposal
        if ($worksheet->type == Worksheet::PROPOSAL) {
            $company = Company::with('templates.pages')->findOrFail(getScopeId());
            $templates = $company->templates;

            foreach ($templates as $template) {
                foreach ($template->pages as $key => $templatePage) {
                    $contents .= \view('worksheets.worksheet-proposal-template', [
                        'content' => $templatePage->content,
                        'pageType' => $template->page_type
                    ])->render();
                }
            }
        }

        $orientationMode = $this->getPdfOrientationMode($worksheet->multi_tier, $pdfColumns);

        $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation($orientationMode);
        $pdf->setOption('dpi', 200);// sub contractor worksheet

        if($subWorksheet) {
            $fileName = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $worksheet->file_path);
            $fullPath = config('jp.BASE_PATH').$fileName;
            FlySystem::write($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);
            if($subWorksheetOldPath) {
                $this->fileDelete(config('jp.BASE_PATH').preg_replace('/(\.pdf)/i', '_sub_contractor$1', $subWorksheetOldPath));
            }
            return $worksheet;
        }

        $name = $worksheet->id . '_' . timestamp();
        $fileName = 'worksheets/' . $name . '.pdf';
        $thumbName = 'worksheets/thumb/' . $name . '.jpg';
        $fullPath = config('jp.BASE_PATH') . $fileName;
        $thumbFullPath = config('jp.BASE_PATH') . $thumbName;
        FlySystem::write($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);

        if ($stopUpdatedAt) {
            DB::table('worksheets')->whereId($worksheet->id)->update([
                'thumb' => $thumbName,
                'file_path' => $fileName,
                'file_size' => FlySystem::getSize($fullPath)
            ]);
        } else {
            $worksheet->thumb = $thumbName;
            $worksheet->file_path = $fileName;
            $worksheet->file_size = FlySystem::getSize($fullPath);
            $worksheet->save();
        }

        // create thumb
        $snappy = App::make('snappy.image');
        $image = $snappy->getOutputFromHtml($contents);
        $image = \Image::make($image);
        if ($image->height() > $image->width()) {
            $image->heighten(250, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(250, function ($constraint) {
                $constraint->upsize();
            });
        }

        FlySystem::put($thumbFullPath, $image->encode()->getEncoded());

        if (!empty($oldFilePath)) {
            $this->fileDelete(config('jp.BASE_PATH') . $oldFilePath);
        }

        if (!empty($oldThumb)) {
            $this->fileDelete(config('jp.BASE_PATH') . $oldThumb);
        }

        if ($object) {
            $object->title = $worksheet->name;
            if (in_array($worksheet->type, [Worksheet::PROPOSAL, Worksheet::ESTIMATE, Worksheet::XACTIMATE])) {
                $object->is_file = true;
            }

            $object->file_name = $object->title . '.pdf';
            $object->file_path = $worksheet->file_path;
            $object->file_size = $worksheet->file_size;
            $object->file_mime_type = 'application/pdf';

            //save thumb if sheet is workorder and material list
            if (in_array($worksheet->type, [Worksheet::WORK_ORDER, Worksheet::MATERIAL_LIST])) {
                $object->thumb = $thumbName;
            }

            $object->save();
        }

        return $worksheet;
    }

    /**
	 * get template pages listing
	 * @param  Worksheet | $worksheet | worksheet object
	 * @return $pages
	 */
    public function getTemplatePages($worksheet)
    {
        $pages = $worksheet->templatePages();

        return $pages;
    }

	/**
	 * get template page by id
	 * @param  Integer $id id of a template page
	 * @return $page
	 */
	public function getTemplatePageById($id)
	{
        $page = WorksheetTemplatePage::where('company_id', getScopeId())->findOrFail($id);

		return $page;
    }

    /**
	 * save template pages
	 * @param  Worksheet 	| $worksheet | worksheet object
	 * @param  Array 		| $input     | array of inputs
	 * @return $worksheet
	 */
	public function saveTemplatePages($worksheet, $input, $templatePages = [])
	{
		$pagesExist = false;
		$pagesRequired = false;
		$now = Carbon::now();
		if(empty($templatePages)) {
			if(ine($input, 'template_pages')) {
				$proposalDbElemnt = new ProposalPageAutoFillDbElement;
				$proposalDbElemnt->setAttributes($worksheet->job, $worksheet->jobProposal->serial_number);
				foreach (array_filter($input['template_pages']) as $key => $value) {
                    if(ine($value, 'id')) {
						$worksheetTemplatePages = WorksheetTemplatePage::where('id', $value['id'])->first();
						$value['tables'] = $worksheetTemplatePages->pageTableCalculations;
                    }

					$content = $value['content'];
					if(!isset($value['auto_fill_content']) || ine($value, 'auto_fill_content')) {
						$content = $proposalDbElemnt->fillTemplate($content);
					}
					$templatePages[$key] = [
						'company_id'		 => getScopeId(),
						'worksheet_id'		 => $worksheet->id,
						'content'			 => $content,
						'page_type'			 => $value['page_type'],
						'auto_fill_required' => ine($value, 'auto_fill_required') ? $value['auto_fill_required'] : null,
						'title'				 => ine($value, 'title') ? $value['title'] : null,
						'created_at'		 => $now,
						'updated_at'		 => $now,
                    ];
                    if(ine($value, 'tables')) {
						foreach ($value['tables'] as $subKey => $subValue) {
							$templatePages[$key]['tables'][$subKey] = [
								'page_type' => PageTableCalculation::WORKSHEET_TEMPLATE_PAGE,
								'name' => isset($subValue['name']) ? $subValue['name'] : null,
								'type_id' => $worksheet->id,
								'ref_id'  => $subValue['ref_id'],
								'head'    => $subValue['head'],
								'body'    => $subValue['body'],
								'foot'    => $subValue['foot'],
								'options' => ine($subValue, 'options') ? $subValue['options']  : []
							];
						}
					}
				}
			} elseif(!$worksheet->pages_exist) {
				$templates = Template::worksheetTemplates()->get();
                $proposalDbElemnt = new ProposalPageAutoFillDbElement;
				$proposalDbElemnt->setAttributes($worksheet->job, $worksheet->jobProposal->serial_number);
				foreach ($templates as $template) {
					foreach ($template->pages as $key => $templatePage) {
                        $autoFillRequired = (array)$templatePage->auto_fill_required;

						if(ine($autoFillRequired, 'table') || ine($autoFillRequired, 'signature')) {
							$pagesRequired = true;
							break;
						}
						$templatePages[] = [
							'company_id'			=> getScopeId(),
							'worksheet_id'			=> $worksheet->id,
							'content'				=> $proposalDbElemnt->fillTemplate($templatePage->content),
							'page_type'				=> $template->page_type,
							'auto_fill_required'	=> null,
							'title'					=> $template->title,
							'created_at'			=> $now,
							'updated_at'			=> $now,
						];
					}
				}
			}
		}
		$worksheet->pages_required = $pagesRequired;
		if($pagesRequired) {
			$worksheet->save();
			return $worksheet;
		}
		if(ine($input, 'template_pages')) {
            foreach ($worksheet->templatePages as $templatePage) {
				$templatePage->pageTableCalculations()->delete();
			}
			$worksheet->templatePages()->delete();
		}
		if(!empty($templatePages)) {
            $pagesExist = true;
            foreach ($templatePages as $page) {
				$templatePage = WorksheetTemplatePage::create([
					'company_id' => $page['company_id'],
					'worksheet_id' => $page['worksheet_id'],
					'content' => $page['content'],
					'page_type' => $page['page_type'],
					'auto_fill_required' => $page['auto_fill_required'],
					'title' => $page['title'],
				]);
				if(ine($page, 'tables')) {
					foreach ($page['tables'] as $table) {
						$pageTableCalculation = PageTableCalculation::create([
							'page_type' => PageTableCalculation::WORKSHEET_TEMPLATE_PAGE,
							'type_id' => $page['worksheet_id'],
							'page_id' => $templatePage->id,
							'name'	  => isset($table['name']) ? $table['name'] : null,
							'ref_id'  => $table['ref_id'],
							'head'    => $table['head'],
							'body'    => $table['body'],
							'foot'    => $table['foot'],
							'options' => ine($table, 'options') ? $table['options']  : []
						]);
					}
				}
			}
			// WorksheetTemplatePage::insert($templatePages);
		}
		$worksheet->pages_exist = $worksheet->pages_exist ?: $pagesExist;
		$worksheet->save();
		$pages = WorksheetTemplatePage::where('worksheet_id', $worksheet->id)->get();
		$worksheet->setRelation('templatePages', $pages);
		$subWorksheetOldPath = $worksheet->file_path;

		$proposal = $worksheet->jobProposal;
		$this->createPDF($worksheet, $proposal);

		if($proposal->createdBy
			&& $proposal->createdBy->isSubContractorPrime()
			&& $proposal->createdBy->dataMaskingEnabled()) {

			\DataMasking::enable();
			$this->createPDF($worksheet, $proposal, false, true, $subWorksheetOldPath);
			\DataMasking::disable();
		}
		return $worksheet;
    }

	/**
	 * save worksheet template pages by page ids
	 * @param  Worksheet 	| $worksheet
	 * @param  Array 		| $input
	 * @return $worksheet
	 */
	public function saveTemplatePagesByIds($worksheet, $input)
	{
		$templatePages = [];
		$now = Carbon::now();
		$templateRepo = App::make(\App\Repositories\TemplatesRepository::class);
		$proposalDbElemnt = new ProposalPageAutoFillDbElement;
		$proposalDbElemnt->setAttributes($worksheet->job, $worksheet->jobProposal->serial_number);
		foreach ($input['template_pages'] as $key => $page) {
			if($page['type'] == 'temp_proposal_page') {
				$pageData =  TempProposalPage::where('company_id', getScopeId())->findOrFail($page['id']);
				$content  = $proposalDbElemnt->fillSerialNumberElement($pageData->content);
				$templatePages[] = [
					'company_id'			=> getScopeId(),
					'worksheet_id'			=> $worksheet->id,
					'content'				=> $content,
					'page_type'				=> $pageData->page_type,
					'auto_fill_required'	=> $pageData->auto_fill_required,
					'title'					=> $pageData->title,
					'created_at'			=> $now,
					'updated_at'			=> $now,
				];
			} elseif($page['type'] == 'template_page') {
				$pageData = $templateRepo->getPageById($page['id']);
				$templatePages[] = [
					'company_id'			=> getScopeId(),
					'worksheet_id'			=> $worksheet->id,
					'content'				=> $pageData->content,
					'page_type'				=> $pageData->page_type,
					'auto_fill_required'	=> $pageData->auto_fill_required,
					'title'					=> $pageData->title,
					'created_at'			=> $now,
					'updated_at'			=> $now,
				];
			} elseif($page['type'] == 'worksheet_template_page') {
				$pageData = $this->getTemplatePageById($page['id']);
				$templatePages[] = [
					'company_id'			=> getScopeId(),
					'worksheet_id'			=> $worksheet->id,
					'content'				=> $pageData->content,
					'page_type'				=> $pageData->page_type,
					'auto_fill_required'	=> $pageData->auto_fill_required,
					'title'					=> $pageData->title,
					'created_at'			=> $now,
					'updated_at'			=> $now,
				];
            }
            $templatePages[$key]['tables'] = $pageData->pageTableCalculations;
        }

		if(empty($templatePages)){
            return $worksheet;
        }

        $worksheet = $this->saveTemplatePages($worksheet, $input, $templatePages);

		return $worksheet;
	}

    /********************* Private function ***************************/

    private function getValideData($data, $tempDetails = false)
    {
        $details = [];
        $jobId = $data['job_id'];
        // foreach ($data['categories'] as $category) {
        // 	if(!ine($category,'id') || !ine($category,'details') || !(is_array($category['details']))) {
        // 		continue;
        // 	}
        foreach ($data['details'] as $detail) {
            if(!ine($detail, 'quantity')) $detail['quantity'] = 0;

            if (!$detail = $this->getValideDetail($detail, $jobId)) {
                continue;
            }

            $detail['product_name'] = ine($detail, 'product_name') ? $detail['product_name'] : '';
            $detail['enable_actual_cost'] = ine($data, 'enable_actual_cost');
            $detail['enable_job_commission'] = ine($data, 'enable_job_commission');

            // if($tempDetails) {
            // 	$detail['category_name'] = $category['name'];
            // }

            $details[] = $detail;
        }

        return $details;
    }

    private function getValideDetail($detail, $jobId)
    {
        $validator = Validator::make($detail, FinancialDetail::getDetailRules());
        if ($validator->fails()) {
            return false;
        }

        $detail['job_id'] = $jobId;

        return $detail;
    }

    private function saveDetails($details, $worksheetId)
    {
        $supplierIds = [];
        foreach ($details as $detail) {
            $detail = $this->repo->saveDetail(
                $detail['job_id'],
                $detail['category_id'],
                $detail['quantity'],
                $detail['product_name'],
                $detail['unit'],
                $worksheetId,
                $detail
            );

            if ($detail->supplier_id) {
                $supplierIds[] = $detail->supplier_id;
            }
        }

        return $supplierIds;
    }

    /**
     * @ add Worksheet in 'worksheets' table
     * @return worksheet->id or false
     */
    private function addWorksheet($input)
    {
        $order = $this->getWorksheetOrder($input);

        if (isset($input['name']) && (strlen($input['name']) > 0)) {
            $name = $input['name'];
        } else {
            $name = 'Sheet' . $order;
        }

        $enableActualCost = false;

        if (isset($input['enable_actual_cost'])) {
            $enableActualCost = isTrue($input['enable_actual_cost']);
        }

        $enableJobCommission =  false;
		if(isset($input['enable_job_commission'])) {
			$enableJobCommission = isTrue($input['enable_job_commission']);
		}

        $descriptionOnlySubOptions = [
			'show_style'		=> ine($input, 'show_style'),
			'show_size'			=> ine($input, 'show_size'),
			'show_color'		=> ine($input, 'show_color'),
			'show_supplier'		=> ine($input, 'show_supplier'),
			'show_trade_type'	=> ine($input, 'show_trade_type'),
			'show_work_type'	=> ine($input, 'show_work_type'),
			'show_tier_color'	=> isset($input['show_tier_color']) ? (int)$input['show_tier_color'] : 1,
		];

        $worksheet = new WorkSheet;
        $worksheet->job_id = $input['job_id'];
        $worksheet->name = $name;
        $worksheet->title = isset($input['title']) ? $input['title'] : null;
        $worksheet->order = $order;
        $worksheet->type = $input['type'];
        $worksheet->enable_actual_cost = $enableActualCost;
        $worksheet->overhead = isset($input['overhead']) ? $input['overhead'] : null;
        $worksheet->profit = isset($input['profit']) ? $input['profit'] : null;
        $worksheet->note = ine($input, 'note') ? $input['note'] : null;
        $worksheet->material_pricing = ine($input, 'material_pricing') ? $input['material_pricing'] : false;
        $worksheet->enable_selling_price = ine($input, 'enable_selling_price');
        $worksheet->material_tax_rate = ine($input, 'material_tax_rate') ? $input['material_tax_rate'] : null;
        $worksheet->labor_tax_rate = ine($input, 'labor_tax_rate') ? $input['labor_tax_rate'] : null;
        $worksheet->taxable = ine($input, 'taxable');
        $worksheet->multi_tier = ine($input, 'multi_tier');
        $worksheet->material_custom_tax_id = ine($input, 'material_custom_tax_id') ? $input['material_custom_tax_id'] : null;
        $worksheet->labor_custom_tax_id = ine($input, 'labor_custom_tax_id') ? $input['labor_custom_tax_id'] : null;
        $worksheet->hide_pricing = ine($input, 'hide_pricing');
        $worksheet->commission = ine($input, 'commission') ? $input['commission'] : null;
        $worksheet->description_only = ine($input, 'description_only');
        $worksheet->show_tier_total = ine($input, 'show_tier_total');
        $worksheet->show_line_total = ine($input, 'show_line_total');
        $worksheet->collapse_all_line_items = ine($input, 'collapse_all_line_items');
        $worksheet->fixed_price = ine($input, 'fixed_price') ? $input['fixed_price'] : null;
        $worksheet->enable_job_commission = $enableJobCommission;

        //save tax rate
        if ($worksheet->taxable) {
            $worksheet->tax_rate = ine($input, 'tax_rate') ? $input['tax_rate'] : null;
            $worksheet->custom_tax_id = ine($input, 'custom_tax_id') ? $input['custom_tax_id'] : null;
        }

        $worksheet->attachments_per_page = ine($input, 'attachments_per_page') ? $input['attachments_per_page'] : config('jp.worksheet_attachments_per_page');

        $worksheet->margin = ine($input, 'margin');
        $worksheet->hide_customer_info = ine($input, 'hide_customer_info');
        $worksheet->show_quantity = ine($input, 'show_quantity');
        $worksheet->show_unit = ine($input, 'show_unit');
        $worksheet->line_tax = ine($input, 'line_tax');
        $worksheet->line_margin_markup = ine($input, 'line_margin_markup');
        $worksheet->division_id         = ine($input, 'division_id') ? $input['division_id'] : null;
        $worksheet->branch_code         = ine($input, 'branch_code') ? $input['branch_code'] : null;
        $worksheet->branch_id           = ine($input, 'branch_id') ? $input['branch_id'] : null;
        $worksheet->ship_to_sequence_number = ine($input, 'ship_to_sequence_number') ? $input['ship_to_sequence_number'] : null;
        $worksheet->update_tax_order    = ine($input, 'update_tax_order');
        $worksheet->sync_on_qbd_by 		= ine($input, 'sync_on_qbd') ? \Auth::id() : null;
        $worksheet->is_qbd_worksheet 	= ine($input, 'is_qbd_worksheet');

        if($worksheet->sync_on_qbd_by) {
            $worksheet->is_qbd_worksheet = true;
        }
        $worksheet->show_calculation_summary = ine($input, 'show_calculation_summary');
        $worksheet->column_settings = $descriptionOnlySubOptions;
        $worksheet->save();

        return $worksheet;
    }

    private function saveJobEstimate($worksheet, $input)
    {
        $estimate = $worksheet->jobEstimate;

        if (!$estimate) {
            $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;

			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_ESTIMATION);
			}

            if (isset($input['name']) && (strlen($input['name']) > 0)) {
                $data['title'] = $worksheet->name;
            }

            if (ine($input, 'serial_number')) {
                $data['serial_number'] = $input['serial_number'];
            }

            if($worksheet->type == Worksheet::XACTIMATE) {
                $data['estimation_type'] = Worksheet::XACTIMATE;
                if (ine($input, 'xactimate_file')) {
                    $estimationPath = 'estimations/' . uniqueTimestamp() . '_xactimate.pdf';

                    $oldPath = 'temp/'.$input['xactimate_file'];

                    $newPath = config('jp.BASE_PATH') . $estimationPath;

                    if (FlySystem::copy($oldPath, $newPath)) {
                        $data['xactimate_file_path'] = $estimationPath;
                    }
                }
            }

            if(ine($input, 'measurement_id')) {
                $data['measurement_id'] = $input['measurement_id'];
            }

            if (ine($input, 'xactimate_file_path')) {
                $data['xactimate_file_path'] = $input['xactimate_file_path'];
            }

            if (ine($input, 'clickthru_estimate_id')) {
				$data['clickthru_estimate_id'] = $input['clickthru_estimate_id'];
			}

            $data['worksheet_id'] = $worksheet->id;
            // save estimations..
            $estimationRepo = App::make(\App\Repositories\EstimationsRepository::class);

            $estimate = $estimationRepo->saveEstimation(
                $worksheet->job_id,
                Auth::id(),
                $data
            );

            if (!ine($input, 'name')) {
                $worksheet->name = $estimate->title;
                $worksheet->update();
            }

            $eventData = [
				'reference_id' => $estimate->id,
				'job_id' => $estimate->job_id,
				'name' => $estimate->id,
				'parent_id' => $parentId,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobEstimationStoreFile($eventData));

            //estimations created event..
            Event::fire('JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated', new EstimationCreated($estimate));
        } else {
            if(($estimate) && isset($input['measurement_id'])) {
                $estimate->measurement_id = ($input['measurement_id']) ?: null;
                $estimate->save();
            }
        }

        if (ine($input, 'update_job_insurance')) {
            Estimation::whereCompanyId(getScopeId())
                ->whereJobId($worksheet->job_id)
                ->where(function($query) {
					$query->whereNotNull('xactimate_file_path')
						->orWhere('estimation_type', Estimation::XACTIMATE);
                })
                ->update(['job_insurance' => false]);

            $estimate->job_insurance = true;
            $estimate->save();
        }

        $worksheet->setRelation('jobEstimate', $estimate);

        return $estimate;
    }

    private function saveJobProposal($worksheet, $input)
    {
        $proposal = $worksheet->jobProposal;

        if (!$proposal) {

            $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;

			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_PROPOSAL);
            }

            if (isset($input['name']) && (strlen($input['name']) > 0)) {
                $data['title'] = $input['name'];
            }

            if (ine($input, 'serial_number')) {
                $data['serial_number'] = $input['serial_number'];
            }

            if(ine($input, 'measurement_id')) {
                $data['measurement_id'] = $input['measurement_id'];
            }

            if ((ine($input, 'link_type') && $input['link_type'] == 'estimate')
                && ine($input, 'link_id')) {
                $data['estimate_id'] = $input['link_id'];
            }

            $data['worksheet_id'] = $worksheet->id;

            // save estimations..
            $proposalRepo = App::make(\App\Repositories\ProposalsRepository::class);

            $proposal = $proposalRepo->saveProposal(
                $worksheet->job_id,
                \Auth::id(),
                $data
            );

            if (!ine($input, 'name')) {
                $worksheet->name = $proposal->title;
                $worksheet->update();
            }

            //proposal created event..
            Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));
            $eventData = [
				'name' => $proposal->id,
				'reference_id' => $proposal->id,
				'job_id' => $input['job_id'],
				'parent_id' => $parentId,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobProposalStoreFile($eventData));
        } else {
            if(($proposal) && isset($input['measurement_id'])) {
                $proposal->measurement_id = ($input['measurement_id']) ?: null;
                $proposal->save();
            }
        }

        $worksheet->setRelation('jobProposal', $proposal);

        return $proposal;
    }

    /**
     * Delete File
     * @param  Url $filePath File Url
     * @return Void
     */
    private function fileDelete($filePath)
    {
        // Delete old file is stopped because if a db transaction rolled back
		// then the actual file path is not get updated with new file path
		return;

        try {
            FlySystem::delete($filePath);
        } catch (\Exception $e) {
            //handle exception
        }
    }

    private function getPreviewData($job, $input)
    {
        $details = $this->getValideData($input, $tempDetails = true);
        $laborsCost = $materialsCost = $amount = $subTotal = $lineTax = $lineMargin = $lineAmount = $noChargeAmt = 0;
        $tax_rate = false;
        $overhead = ine($input, 'overhead') ? $input['overhead'] : null;
        $profit = ine($input, 'profit') ? $input['profit'] : null;
        $commission = ine($input, 'commission') ? $input['commission'] : null;
        $material_tax_rate = ine($input, 'material_tax_rate') ? $input['material_tax_rate'] : null;
        $labor_tax_rate = ine($input, 'labor_tax_rate') ? $input['labor_tax_rate'] : null;
        $enable_selling_price = ine($input, 'enable_selling_price');
        $margin = ine($input, 'margin');
        $line_tax = ine($input, 'line_tax');
        $line_margin_markup = ine($input, 'line_margin_markup');
        $worksheetDivision  = ine($input, 'division_id') ? Division::find($input['division_id']) : null;
        $updateTaxOrder = ine($input, 'update_tax_order');
        $fixedPrice = ine($input, 'fixed_price') ? $input['fixed_price'] : null;

        if (ine($input, 'attachments')) {
            $attachments = $this->attachments(null, $input, $preview = true);
        }

        if (ine($input, 'tax_rate') && ine($input, 'taxable')) {
            $tax_rate = $input['tax_rate'];
        }

        $noChargeCategory = FinancialCategory::whereCompanyId(getScopeId())
            ->whereName(FinancialCategory::NO_CHARGE)
            ->first();

        $company = $job->company;

        if ($enable_selling_price) {
            foreach ($details as $key => $detail) {
                $categoryName = issetRetrun($detail, 'category_name');
                if($noChargeCategory && $noChargeCategory->name == $categoryName) {
                    $noChargeAmt += $detail['selling_price'] * $detail['quantity'];
                }
                $lineTotal = $detail['selling_price'] * $detail['quantity'];
                $amount += $lineTotal;

                $lineAmount += $lineTotal;
                $lineTax    += ine($detail, 'line_tax') ? calculateTax($lineTotal, $detail['line_tax']) : 0;
                $lineMargin += ine($detail, 'line_profit') ? getWorksheetMarginMarkup($margin, $lineTotal, $detail['line_profit']) : 0;

                $lineTax += ine($detail, 'line_tax') ? calculateTax($lineTotal, $detail['line_tax']) : 0;
                $lineMargin += ine($detail, 'line_profit') ? getWorksheetMarginMarkup($margin, $lineTotal, $detail['line_profit']) : 0;

                if (!($material_tax_rate || $labor_tax_rate) && !($categoryName)) {
                    continue;
                }
                switch (strtoupper($categoryName)) {
                    case 'LABOR':
                        $laborsCost += $lineTotal;
                        break;
                    case 'MATERIALS':
                        $materialsCost += $lineTotal;
                        break;
                }
            }
        } else {
            foreach ($details as $key => $detail) {
                $categoryName = issetRetrun($detail, 'category_name');
                if($noChargeCategory && $noChargeCategory->name == $categoryName) {
                    $noChargeAmt += $detail['unit_cost'] * $detail['quantity'];
                }
                $lineTotal = $detail['unit_cost'] * $detail['quantity'];
                $amount += $lineTotal;

                $lineAmount += $lineTotal;
                $lineTax    += ine($detail, 'line_tax') ? calculateTax($lineTotal, $detail['line_tax']) : 0;
                $lineMargin += ine($detail, 'line_profit') ? getWorksheetMarginMarkup($margin, $lineTotal, $detail['line_profit']) : 0;

                $lineTax += ine($detail, 'line_tax') ? calculateTax($lineTotal, $detail['line_tax']) : 0;
                $lineMargin += ine($detail, 'line_profit') ? getWorksheetMarginMarkup($margin, $lineTotal, $detail['line_profit']) : 0;

                if (!($material_tax_rate || $labor_tax_rate) && !($categoryName)) {
                    continue;
                }

                switch (strtoupper($categoryName)) {
                    case 'LABOR':
                        $laborsCost += $lineTotal;
                        break;
                    case 'MATERIALS':
                        $materialsCost += $lineTotal;
                        break;
                }
            }
        }

        $amount = $amount - $noChargeAmt;
        $totalWithoutTax = $subTotal = $amount;
        if ($overhead) {
            $totalOverhead = calculateTax($subTotal, $overhead);
            $amount          += $totalOverhead;
            $totalWithoutTax += $totalOverhead;
        }

        if ($profit) {
            $totalProfit = getWorksheetMarginMarkup($margin, $subTotal, $profit);
            $amount += $totalProfit;
            $totalWithoutTax += $totalProfit;
        }

        if($line_margin_markup) {
            $amount          += $lineMargin;
            $totalWithoutTax += $lineMargin;
        }

        if ($commission) {
            $amount += calculateTax($totalWithoutTax, $commission);
        }

        $taxAmount = 0;
        if($tax_rate) {
            $taxAmount = $updateTaxOrder ? calculateTax($amount, $tax_rate) : calculateTax($subTotal, $tax_rate);
            $amount += $taxAmount;
        }

        if ($material_tax_rate) {
            $amount += calculateTax($materialsCost, $material_tax_rate);
        }

        if ($labor_tax_rate) {
            $amount += calculateTax($laborsCost, $labor_tax_rate);
        }

        if ($line_tax) {
            $amount += $lineTax;
        }

        if ($line_margin_markup) {
            $amount += $lineMargin;
        }

        $profitLoss = null;

		if(!is_null($fixedPrice)) {
			$profitLoss = $fixedPrice - $amount;
			$amount += $profitLoss;
		}

        // get columns for pdf print
        $printColumns = $this->getPdfPrintColumns(
            ine($input, 'hide_pricing'),
            ine($input, 'description_only'),
            ine($input, 'multi_tier'),
            ine($input, 'show_quantity'),
            ine($input, 'show_unit'),
            $input,
            ine($input, 'line_tax'),
            ine($input, 'line_margin_markup'),
            ine($input, 'show_tier_total'),
            ine($input, 'show_line_total')
        );

        if($input['type'] != Worksheet::XACTIMATE) {
            $multiStructure                     = new MultiTierStructure();
            $multiStructure->margin             = $margin;
            $multiStructure->line_tax           = $line_tax;
            $multiStructure->line_margin_markup = $line_margin_markup;
            $details = $multiStructure->makeMultiTierStructure($details, $addSum = true, $enable_selling_price);
        }
        // for srs material list
        $forSrs = false;
        $branch = null;
        $shipToAddress = null;

        if($input['type'] == Worksheet::MATERIAL_LIST && ine($input, 'for_supplier_id')) {
            $srs = Supplier::srs();
            $forSrs = ($srs->companySupplier && ($srs->id == $input['for_supplier_id']));
        }

        if(ine($input, 'branch_code') && ine($input, 'branch_id')) {
            $branch = SupplierBranch::where('company_id', getScopeId())
                ->where('branch_code', $input['branch_code'])
                ->where('branch_id', $input['branch_id'])
                ->first();
        }

        $descriptionOnlySubOptions = [
			'show_style'		=> ine($input, 'show_style'),
			'show_size'			=> ine($input, 'show_size'),
			'show_color'		=> ine($input, 'show_color'),
			'show_supplier'		=> ine($input, 'show_supplier'),
			'show_trade_type'	=> ine($input, 'show_trade_type'),
			'show_work_type'	=> ine($input, 'show_work_type'),
			'show_tier_color'	=> isset($input['show_tier_color']) ? (int)$input['show_tier_color'] : 1,
		];

        $data = [
            'job' => $job,
            'customer' => $job->customer,
            'company' => $company,
            'financial_details' => $details,
            'type' => $input['type'],
            'note' => (ine($input, 'note')) ? $input['note'] : null,
            'tax_rate' => $tax_rate,
            'overhead' => $overhead,
            'profit' => $profit,
            'signature' => ine($input, 'signature') ? $input['signature'] : null,
            'material_tax_rate' => $material_tax_rate,
            'labor_tax_rate' => $labor_tax_rate,
            'hide_pricing' => ine($input, 'hide_pricing'),
            'show_tier_total' => ine($input, 'show_tier_total'),
            'multi_tier' => ine($input, 'multi_tier'),
            'margin' => $margin,
            'enable_selling_price' => $enable_selling_price,
            'company_country_code' => $company->country->code,
            'commission' => $commission,
            'total_amount' => $amount,
            'sub_total' => $subTotal,
            'materials_cost' => $materialsCost,
            'labors_cost' => $laborsCost,
            'worksheet_title' => ine($input, 'title') ? $input['title'] : null,
            'attachments' => isset($attachments) ? $attachments : null,
            'attachments_per_page' => issetRetrun($input, 'attachments_per_page') ?: config('jp.worksheet_attachments_per_page'),
            'description_only' => ine($input, 'description_only'),
            'hide_customer_info' => ine($input, 'hide_customer_info'),
            'show_quantity' => ine($input, 'show_quantity'),
            'claim_number' => ine($input, 'claim_number') ? $input['claim_number'] : null,
            'policy_number' => ine($input, 'policy_number') ? $input['policy_number'] : null,
            'printFields' => $printColumns,
            'total_without_tax'   => $totalWithoutTax,
            'enable_line_tax'     => $line_tax,
            'enable_line_margin'  => $line_margin_markup,
            'total_line_tax'      => $lineTax,
            'total_line_profit'   => $lineMargin,
            'line_amount'         => $lineAmount,
            'show_quantity'       => ine($input, 'show_quantity'),
            'show_unit'           => ine($input, 'show_unit'),
            'for_srs'             => $forSrs,
            'no_charge_amount'    => $noChargeAmt,
            'division'            => $worksheetDivision,
            'branch'              => $branch,
            'update_tax_order'    => $updateTaxOrder,
            'tax_amount'          => $taxAmount,
            'show_calculation_summary' => ine($input, 'show_calculation_summary'),
            'show_line_total' => ine($input, 'show_line_total'),
            'profit_loss'		=> $profitLoss,
			'collapse_all_line_items' => ine($input, 'collapse_all_line_items'),
            'column_settings' 	=> $descriptionOnlySubOptions,
        ];

        return $data;
    }

    /**
     * Attachments For Work Order
     * @param $worksheet Worksheet Data(id)
     * @param $input     Input
     */
    private function attachments($worksheet, $input, $preview = false)
    {
        if (!ine($input, 'attachments')) {
            return;
        }

        $previeAttachments = [];
        $allowedTypes = ['resource', 'proposal', 'estimate', 'attachment', 'upload'];

        foreach ((array)$input['attachments'] as $attachment) {
            if (!ine($attachment, 'type') || !in_array($attachment['type'], $allowedTypes) || !ine($attachment, 'value')) {
                continue;
            }

            switch ($attachment['type']) {
                case 'resource':
                case 'upload':
                    $resourcesRepo = App::make(\App\Repositories\ResourcesRepository::class);
                    $file = $resourcesRepo->getFile($attachment['value']);
                    $filePath = config('resources.BASE_PATH') . $file->path;
                    $name = $file->name;
                    $size = $file->size;
                    $thumb = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $file->path);
                    $thumbPath = config('resources.BASE_PATH') . $thumb;
                    break;

                case 'proposal':
                    $proposalRepo = App::make(\App\Repositories\ProposalsRepository::class);
                    $file = $proposalRepo->getById($attachment['value']);
                    $filePath  = $file->getFilePathWithoutUrl();
                    $name = $file->title;
                    $size = $file->file_size;
                    $thumbPath = config('jp.BASE_PATH') . $file->thumb;
                    break;

                case 'estimate':
                    $estimateRepo = App::make(\App\Repositories\EstimationsRepository::class);
                    $file = $estimateRepo->getById($attachment['value']);
                    $filePath = config('jp.BASE_PATH') . $file->file_path;
                    $name = $file->title;
                    $size = $file->file_size;
                    $thumbPath = config('jp.BASE_PATH') . $file->thumb;
                    break;
                case 'attachment':
                    $file = WorksheetImage::findOrFail($attachment['value']);
                    $filePath = config('jp.BASE_PATH') . $file->path;
                    $name = $file->name;
                    $size = $file->size;
                    $thumbPath = config('jp.BASE_PATH') . $file->thumb;
                    break;
                default:
                    continue;
                    break;
            }

            // get file extension..
            $extension = File::extension($filePath);

            $destinationPath = config('jp.BASE_PATH');
            // create physical file name..
            $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;
            $basePath = 'worksheets/image_attachments/' . $physicalName;

            if (!$preview) {
                // save thumb for images..
                $thumbBasePath = 'worksheets/image_attachments/thumb/' . $physicalName;
                $newThumbPath = $destinationPath . $thumbBasePath;
                FlySystem::copy($thumbPath, $newThumbPath);

                // copy file to attachment directory ..
                if (FlySystem::copy($filePath, $destinationPath . $basePath)) {
                    WorksheetImage::create([
                        'worksheet_id' => $worksheet->id,
                        'name' => $name,
                        'path' => $basePath,
                        'size' => $size,
                        'thumb' => $thumbBasePath,
                    ]);
                }
            } else {
                $attachments = new WorksheetImage;
                $attachments->name = $name;
                $attachments->path = $filePath;
                $attachments->size = $size;

                $previeAttachments[] = $attachments;
            }
        }

        return Collection::make($previeAttachments);
    }

    private function getPdfPrintColumns($hidePricing, $descriptionOnly, $multiTier, $showQty, $showUnit, $input, $lineTax = false, $lineMargin = false, $showTierTotal = false,  $showLineTotal = false)
    {
        $totalColumns = ['description'];

        if (!$hidePricing || ($showLineTotal && $hidePricing)) {
            $totalColumns[] = 'line_total';
        }

        if(!$hidePricing) {
			$totalColumns[] = 'no_hide_pricing';
		}

        if(($hidePricing && $showTierTotal && $multiTier) && (!$descriptionOnly)) {
            $totalColumns[] = 'hide_tier_pricing';
        }
        if(($hidePricing) && ($showTierTotal) && ($multiTier) && ($descriptionOnly)) {
            $totalColumns[] = 'add_des_column';
        }

        if (!$descriptionOnly) {
            $totalColumns[] = 'type';
            $totalColumns[] = 'name';
            $totalColumns[] = 'others';
        }

        if (!$multiTier) {
            $totalColumns[] = 'serial_number';
        }

        if (!$hidePricing && !$descriptionOnly) {
            $totalColumns[] = 'price_unit_qty';
        } elseif ((!$descriptionOnly && $hidePricing) || ($descriptionOnly && $showQty && $showUnit)) {
            $totalColumns[] = 'unit_qty';
        } else {
            if ($descriptionOnly && $showQty) {
                $totalColumns[] = 'only_qty';
            }
            if ($descriptionOnly && $showUnit) {
                $totalColumns[] = 'only_unit';
            }
        }

        if(!$hidePricing && !$descriptionOnly && $lineTax) {
            $totalColumns[] = 'line_tax';
            $totalColumns[] = 'line_amount';
        }
        if(!$hidePricing && !$descriptionOnly && $lineMargin) {
            $totalColumns[] = 'line_margin_markup';
            $totalColumns[] = 'line_amount';
        }

        return arry_fu($totalColumns);
    }

    /**
     * copy xactimate file when save_as true
     * @param  $estimate
     * @return $estimationPath
     */
    private function copyXactimateFile($estimate)
    {
        $estimationPath = 'estimations/' . uniqueTimestamp() . '_xactimate.pdf';

        $oldPath = config('jp.BASE_PATH') . $estimate->xactimate_file_path;

        $newPath = config('jp.BASE_PATH') . $estimationPath;

        if (FlySystem::connection('local')->copy($oldPath, $newPath)) {
            return $estimationPath;
        }
    }

    private function updateWorksheet($worksheet, &$input)
    {
        $worksheetId = $worksheet->id;

        /* Delete financial details of worksheet */
        FinancialDetail::whereJobId($input['job_id'])
            ->whereWorksheetId($worksheetId)
            ->delete();

        if (ine($input, 'delete_attachments')) {
            WorksheetImage::whereIn('id', $input['delete_attachments'])
                ->whereWorksheetId($worksheetId)
                ->delete();
        }

        $updateData = [];

        if (isset($input['name']) && (strlen($input['name']) > 0)) {
            $updateData['name'] = $input['name'];
        }

        if (ine($input, 'title')) {
            $updateData['title'] = $input['title'];
        }

        if (isset($input['enable_actual_cost'])) {
            $updateData['enable_actual_cost'] = isTrue($input['enable_actual_cost']);
        }

        if (isset($input['overhead'])) {
            $updateData['overhead'] = ine($input, 'overhead') ? $input['overhead'] : null;
        }

        if (isset($input['profit'])) {
            $updateData['profit'] = ine($input, 'profit') ? $input['profit'] : null;
        }

        if (isset($input['note'])) {
            $updateData['note'] = ine($input, 'note') ? $input['note'] : null;
        }

        if (isset($input['hide_pricing'])) {
            $updateData['hide_pricing'] = (bool)$input['hide_pricing'];
        }

        if(isset($input['show_line_total'])) {
			$updateData['show_line_total'] = (bool)$input['show_line_total'];
		}

        if(isset($input['show_tier_total'])) {
            $updateData['show_tier_total'] = (bool)$input['show_tier_total'];
        }

        if (isset($input['enable_selling_price'])) {
            $updateData['enable_selling_price'] = ine($input, 'enable_selling_price');
        }

        //save tax rate
        if (isset($input['taxable'])) {
            $updateData['tax_rate'] = ine($input, 'tax_rate') ? $input['tax_rate'] : null;
            $updateData['taxable'] = ine($input, 'taxable');
            $updateData['custom_tax_id'] = ine($input, 'custom_tax_id') ? $input['custom_tax_id'] : null;
        }

        if (isset($input['material_tax_rate'])) {
            $updateData['material_tax_rate'] = ($input['material_tax_rate']) ?: null;
        }

        if (isset($input['labor_tax_rate'])) {
            $updateData['labor_tax_rate'] = ($input['labor_tax_rate']) ?: null;
        }

        if (isset($input['material_custom_tax_id'])) {
            $updateData['material_custom_tax_id'] = ($input['material_custom_tax_id']) ?: null;
        }

        if (isset($input['labor_custom_tax_id'])) {
            $updateData['labor_custom_tax_id'] = ($input['labor_custom_tax_id']) ?: null;
        }

        if (isset($input['multi_tier'])) {
            $updateData['multi_tier'] = (bool)$input['multi_tier'];
        }

        if (isset($input['commission'])) {
            $updateData['commission'] = ($input['commission']) ?: null;
        }

        $updateData['attachments_per_page'] = ine($input, 'attachments_per_page') ? $input['attachments_per_page'] : config('jp.worksheet_attachments_per_page');

        if (isset($input['margin'])) {
            $updateData['margin'] = ine($input, 'margin');
        }

        if (isset($input['description_only'])) {
            $updateData['description_only'] = (bool)$input['description_only'];
        }

        $updateData['re_calculate'] = false;

        // hide customer info from work order
        if (isset($input['hide_customer_info'])) {
            $updateData['hide_customer_info'] = (bool)$input['hide_customer_info'];
        }

        if (isset($input['show_quantity'])) {
            $updateData['show_quantity'] = (bool)$input['show_quantity'];
        }

        if (isset($input['show_unit'])) {
            $updateData['show_unit'] = (bool)$input['show_unit'];
        }

        // enable line tax for worksheets
        if (isset($input['line_tax'])) {
            $updateData['line_tax'] = (bool)$input['line_tax'];
        }

        // enable line margin for worksheets
        if (isset($input['line_margin_markup'])) {
            $updateData['line_margin_markup'] = (bool)$input['line_margin_markup'];
        }

        if(isset($input['division_id'])) {
            $updateData['division_id'] = ($input['division_id']) ?: null;
        }
        if(isset($input['branch_code'])) {
            $updateData['branch_code'] = $input['branch_code'];
        }
        if(isset($input['ship_to_sequence_number'])) {
            $updateData['ship_to_sequence_number'] = $input['ship_to_sequence_number'];
        }
        if(isset($input['branch_id'])) {
            $updateData['branch_id'] = $input['branch_id'];
        }

        if(isset($input['update_tax_order'])) {
			$updateData['update_tax_order'] = (bool)$input['update_tax_order'];
        }

        if(isset($input['show_calculation_summary'])) {
			$updateData['show_calculation_summary'] = (bool)$input['show_calculation_summary'];
		}

        if(isset($input['is_qbd_worksheet'])) {
			$updateData['is_qbd_worksheet'] = (bool)$input['is_qbd_worksheet'];
        }

        if(ine($input, 'sync_on_qbd')) {
			$updateData['sync_on_qbd_by'] = Auth::id();
			$updateData['is_qbd_worksheet'] = true;
        }

        if(isset($input['collapse_all_line_items'])) {
			$updateData['collapse_all_line_items'] = ine($input, 'collapse_all_line_items');
        }

		if(isset($input['fixed_price'])) {
			$updateData['fixed_price'] = ine($input, 'fixed_price') ? $input['fixed_price'] : null;
        }

        if(isset($input['enable_job_commission'])) {
			$updateData['enable_job_commission'] = isTrue($input['enable_job_commission']);
		}

        $descriptionOnlySubOptions = [];
		if(isset($input['show_style'])) {
			$descriptionOnlySubOptions['show_style'] = (bool)$input['show_style'];
		}
		if(isset($input['show_size'])) {
			$descriptionOnlySubOptions['show_size'] = (bool)$input['show_size'];
		}
		if(isset($input['show_color'])) {
			$descriptionOnlySubOptions['show_color'] = (bool)$input['show_color'];
		}
		if(isset($input['show_supplier'])) {
			$descriptionOnlySubOptions['show_supplier'] = (bool)$input['show_supplier'];
		}
		if(isset($input['show_trade_type'])) {
			$descriptionOnlySubOptions['show_trade_type'] = (bool)$input['show_trade_type'];
		}
		if(isset($input['show_work_type'])) {
			$descriptionOnlySubOptions['show_work_type'] = (bool)$input['show_work_type'];
		}
		if(isset($input['show_tier_color'])) {
			$descriptionOnlySubOptions['show_tier_color'] = (bool)$input['show_tier_color'];
		}
		if(!empty($descriptionOnlySubOptions)) {
			$updateData['column_settings'] = $descriptionOnlySubOptions;
		}

        if (!empty($updateData)) {
            $worksheet->update($updateData);
        }

        return $worksheet;
    }

    private function updateFinancialSum($worksheet, $input)
    {
        $worksheetId = $worksheet->id;
        $financials = FinancialCategory::where('financial_categories.company_id', getScopeId())
            ->leftJoin('financial_details', function ($join) use ($worksheetId) {
                $join->on('financial_details.category_id', '=', 'financial_categories.id');
                $join->where('financial_details.company_id', '=', getScopeId());
                $join->where('financial_details.worksheet_id', '=', $worksheetId);
            })->leftJoin('worksheets', function ($join) use ($worksheetId) {
                $join->on('worksheets.id', '=', 'financial_details.worksheet_id');
                $join->where('worksheets.id', '=', $worksheetId);
            })->selectRaw('
            IF(worksheets.enable_actual_cost = 1, IFNULL(SUM(ROUND(CAST(actual_quantity AS DECIMAL(16,2)) * CAST(actual_unit_cost AS DECIMAL(16,2)), 2)), 0),IFNULL(SUM(ROUND(CAST(quantity AS DECIMAL(16,2)) * CAST(unit_cost AS DECIMAL(16,2)), 2)), 0)) as cost,
            IF(worksheets.enable_actual_cost = 1, IFNULL(SUM(ROUND(CAST(actual_quantity AS DECIMAL(16,2)) * CAST(selling_price AS DECIMAL(16,2)), 2)), 0), IFNULL((SUM(ROUND(CAST(quantity AS DECIMAL(16,2)) * CAST(selling_price AS DECIMAL(16,2)), 2))), 0)) as selling_price_total
                , LOWER(financial_categories.name) as name, SUM(financial_details.rcv) as total_rcv, SUM(financial_details.depreciation) as total_depreciation, SUM(financial_details.acv) as total_acv, SUM(financial_details.tax) as total_tax,
                IF(worksheets.enable_selling_price = 1, SUM( ROUND((((CAST(selling_price AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2))) * CAST(financial_details.line_tax AS DECIMAL(16,2))) / 100), 2) ), SUM(ROUND(((((CAST(unit_cost AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2))) * CAST(financial_details.line_tax AS DECIMAL(16,2))) / 100)), 2))) as line_tax,
                IF(worksheets.margin = 1,
                    IF(worksheets.enable_selling_price = 1,
                    SUM(ROUND((((CAST(selling_price AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2))) * CAST(line_profit AS DECIMAL(16,8))) / (100 - CAST(line_profit AS DECIMAL(16,8)))), 2)), SUM(ROUND((((CAST(unit_cost AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2))) * CAST(line_profit AS DECIMAL(16,8))) / (100 - CAST(line_profit AS DECIMAL(16,8)))), 2))),
                    IF(worksheets.enable_selling_price = 1,
                    SUM( ROUND((((CAST(selling_price AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2))) * CAST(line_profit AS DECIMAL(16,8))) / 100), 2) ), SUM( ROUND((((CAST(unit_cost AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2))) * CAST(line_profit AS DECIMAL(16,8))) / 100), 2) ))) as line_profit,
                    IF(financial_categories.name = "NO CHARGE", IF(worksheets.enable_selling_price = 1, SUM(ROUND(CAST(selling_price AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2)), 2)), SUM(ROUND(CAST(unit_cost AS DECIMAL(16,2)) * CAST(quantity AS DECIMAL(16,2)), 2))), 0) as no_charge_amount'
            )->groupBy('financial_categories.name')
            ->get();

        $data = [];
        foreach ($financials as $financial) {
            $name = str_replace(' ', '_', $financial->name);
            $data[$name . '_cost_total'] = $financial->cost;
            $data[$name . '_selling_price_total'] = $financial->selling_price_total;
        }

        $data['cost_total'] = $financials->sum('cost');
        $data['selling_price_total'] = $financials->sum('selling_price_total');
        $data['total_line_tax'] = $financials->sum('line_tax');
        $data['total_line_profit'] = $financials->sum('line_profit');
        $data['no_charge_amount'] = $financials->sum('no_charge_amount');

        // save insurance meta in worksheet
        $insuranceMeta = null;
        if ($worksheet->type == Worksheet::XACTIMATE) {
            $insuranceMeta = [
                'rcv_total' => numberFormat($financials->sum('total_rcv')),
                'tax_total' => numberFormat($financials->sum('total_tax')),
                'acv_total' => numberFormat($financials->sum('total_acv')),
                'depreciation_total' => numberFormat($financials->sum('total_depreciation')),
                'policy_number' => ine($input, 'policy_number') ? $input['policy_number'] : null,
                'claim_number' => ine($input, 'claim_number') ? $input['claim_number'] : null,
            ];
        }

        $worksheet->update([
            'total'               => $data['cost_total'] - $data['no_charge_amount'],
            'selling_price_total' => $data['selling_price_total'] - $data['no_charge_amount'],
            'meta' => $data,
            'insurance_meta' => $insuranceMeta,
        ]);

        return [
            'worksheet' => $worksheet,
            'data' => $data,
            'insuranceMeta' => $insuranceMeta,
        ];
    }

    private function getPdfOrientationMode($multiTier, $columns)
    {
        $mode = 'portrait';
        $columnCount = count($columns);
        if(in_array('others', $columns)) $columnCount -= 1;
        if(in_array('no_hide_pricing', $columns)) $columnCount -= 1;
        if(in_array('hide_tier_pricing', $columns)) $columnCount -= 1;

        if(($multiTier && ($columnCount > 5)) || $columnCount > 6) {
            $mode = 'landscape';
        }
        return $mode;
    }

    private function previewTemplateContent($worksheet, $contents)
	{
        $serialNumber = $worksheet->jobProposal ? $worksheet->jobProposal->serial_number : null;
		$proposalDbElemnt = new ProposalPageAutoFillDbElement;
		$proposalDbElemnt->setAttributes($worksheet->job, $serialNumber);

		foreach ($worksheet->templatePages as $page) {
			$pageData = $this->getTemplatePageById($page['id']);
            $autoFillingContent  = $proposalDbElemnt->fillTemplate($pageData->content);
			$contents .= view('worksheets.worksheet-proposal-template', [
				'content'  => $autoFillingContent,
				'pageType' => $pageData->page_type
			])->render();
		}
		return $contents;
	}
}
