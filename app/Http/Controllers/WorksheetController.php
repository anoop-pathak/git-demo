<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Worksheet;
use FlySystem;
use App\Services\Worksheets\WorksheetsService;
use App\Transformers\FinancialCategoryTransformer;
use App\Transformers\WorksheetTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\WorksheetTemplatePagesTransformer;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\WorksheetLeastAmountException;
use App\Exceptions\Proposal\ProposalCannotBeUpdate;

class WorksheetController extends ApiController
{
    protected $service;

    public function __construct(WorksheetsService $service, Larasponse $response)
    {
        parent::__construct();

        $this->service = $service;
        $this->response = $response;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function worksheetsList()
    {
        $input = Request::onlyLegacy('job_id', 'type');

        $validator = Validator::make($input, Worksheet::getShowWorksheetRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $worksheets = $this->service->getWorksheetList($input['job_id'], $input['type']);

        return ApiResponse::success($worksheets);
    }

    public function getMultipleWorksheets()
    {
        $input = Request::onlyLegacy('job_id', 'type');
        $validator = Validator::make($input, Worksheet::getShowWorksheetRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $data = $this->service->getMultipleWorksheets($input['job_id'], $input['type']);

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * Get Worksheet By Id
     * @param  int $id | Worksheet Id
     * @return [type]     [description]
     */
    public function getWorksheet($id)
    {
        $input = Request::all();

        $worksheet = Worksheet::with(
            [
                'jobEstimate' => function ($jobEstimate) {
                    $jobEstimate->select('id', 'worksheet_id', 'serial_number', 'xactimate_file_path', 'created_by', 'measurement_id');
                },
                'jobProposal' => function ($jobProposal) {
                    $jobProposal->select('id', 'worksheet_id', 'signature', 'created_by', 'serial_number', 'measurement_id');
                },
                'linkedEstimate',
            ]
        )->findOrFail($id);

        Request::merge(['worksheet_id' => $id]);

        $data = $this->service->getWorksheetWithDetails($worksheet, $input);

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /financialdetails
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $rules = array_merge(Worksheet::getRules(), Worksheet::getTemplatePagesRules());
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if(in_array($input['type'], [Worksheet::PROFIT_LOSS, Worksheet::SELLING_PRICE])) {
			if (Auth::user()->isStandardUser() && !in_array('manage_financial',
				Auth::user()->listPermissions())) {

				return ApiResponse::errorForbidden();
			}
        }

        if (!ine($input, 'enable_selling_price')
			&& !Auth::user()->hasPermission('view_unit_cost')) {
			return ApiResponse::errorGeneral('You does not have permission to save worksheet with unit cost.');
		}

        $update = false;

        if (ine($input, 'id')) {
            $update = true;
        }

        // $supplierIds = arry_fu(array_column($input['details'], 'supplier_id'));

        // if (!empty($supplierIds)) {

        // 	$chkSrsSupplier = Supplier::whereName(Supplier::SRS_SUPPLIER)
        // 		->whereNull('company_id')
        // 		->whereIn('id', $supplierIds)
        // 		->count();

        // 	// if($chkSrsSupplier && config('srs_disabled_for_mobile')) {

        // 	// 	return ApiResponse::errorGeneral('Worksheet with SRS materials is unavailable on mobile App.');
        // 	// }
        // }

        if (!$update) {
            if (ine($input, 'serial_number')
                && $this->service->isExistSerialNumber($input['serial_number'], $input['type'])) {
                $data['serial_number'] = $this->service->getSerialNumber($input['type']);

                return ApiResponse::errorGeneral(trans('response.error.serial_number_already_exist', ['attribute' => 'Worksheet']), [], $data);
            }
        }

        DB::beginTransaction();
        try {
            $worksheet = $this->service->createOrUpdateWorksheet($input);
        } catch(WorksheetLeastAmountException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(FolderNotExistException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ProposalCannotBeUpdate $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Worksheet']),
            'data' => $this->response->item($worksheet, new WorksheetTransformer)
        ]);
    }

    /**
     * Delete a worksheet and its financial details in storage.
     * Delete /financialdetails
     *
     * @return Response
     */
    public function deleteWorksheet($id)
    {

        $worksheet = Worksheet::findOrFail($id);

        DB::beginTransaction();
        try {
            $worksheet->finacialDetail()->where('company_id', getScopeId())->delete();
            $worksheet->favouriteEntities()->delete();
            $worksheet->delete();
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Worksheet'])
        ]);
    }

    /**
     * Rename a worksheet in storage.
     * Put /rename_worksheet
     *
     * @return Response
     */
    public function renameWorksheet($id)
    {
        $worksheet = Worksheet::findOrFail($id);

        $input = Request::onlyLegacy('name');

        $validation = Validator::make($input, ['name' => 'required']);

        if ($validation->fails()) {
            return ApiResponse::validation($validation);
        }

        try {
            $worksheet->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.renamed', ['attribute' => 'Worksheet'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Worksheet pdf preview
     * Post /worksheet/{id}/pdf_preview
     * @return $worksheet
     */
    public function pdfPreview()
    {
        $input = Request::all();
        $validator = Validator::make($input, Worksheet::getPdfPreviewRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        return $this->service->pdfPreview($input);
    }

    public function getPDF($id)
    {
        $input = Request::onlyLegacy('download');
        $worksheet = Worksheet::findOrFail($id);
        if (empty($worksheet->file_path)) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'File']));
        }

        $fullPath = config('jp.BASE_PATH') . $worksheet->file_path;

        $fileResource = FlySystem::read($fullPath);
        $response = \response($fileResource, 200);
        $response->header('Content-Type', 'application/pdf');

        if (!$input['download']) {
            $response->header('Content-Disposition', 'filename="' . $worksheet->type . '.pdf"');
        } else {
            $response->header('Content-Disposition', 'attachment; filename="' . $worksheet->type . '.pdf"');
        }

        return $response;
    }

    /**
     * Get worksheet categories list
     * Get worksheet/{id}/categories
     * @param  Int $id Worksheet Id
     * @return Categories list
     */
    public function getCategoriesList($id)
    {
        $worksheet = $this->service->getById($id);
        $financialCategories = $worksheet->financialCategories()
            ->where('name', '!=', 'MATERIALS')
            ->get();

        return ApiResponse::success($this->response->collection($financialCategories, new FinancialCategoryTransformer));
    }

    /**
	 * get template pages of a worksheet
	 * GET - /worksheet/template_pages
	 * @return WorksheetTemplatePage
	 */
	public function getTemplatePages($id)
	{
		$input = Request::all();
		$worksheet = $this->service->getById($id);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		$pages = $this->service->getTemplatePages($worksheet);
		if(!$limit) {
			$pages = $pages->get();
			return ApiResponse::success($this->response->collection($pages, new WorksheetTemplatePagesTransformer));
		}
		$pages = $pages->paginate($limit);
		return ApiResponse::success($this->response->paginatedCollection($pages, new WorksheetTemplatePagesTransformer));
    }

	/**
	 * get template pages of a worksheet
	 * GET - /worksheet/template_pages
	 * @return WorksheetTemplatePage
	 */
	public function getTemplatePage($id)
	{
		$page = $this->service->getTemplatePageById($id);
		return ApiResponse::success(['data' => $this->response->item($page, new WorksheetTemplatePagesTransformer)]);
    }

	/**
	 * save template pages of a worksheet
	 * PUT - /worksheet/{id}/template_pages
	 * @return response
	 */
	public function saveTemplatePagesByIds($id)
	{
		$worksheet = $this->service->getById($id);
		$input = Request::all();
		$validator = Validator::make($input, Worksheet::getTemplatePagesIdsRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$worksheet = $this->service->saveTemplatePagesByIds($worksheet, $input);
		return ApiResponse::success([
			'message' => trans('response.success.saved', ['attribute' => 'Worksheet template pages']),
			'data' => $this->response->collection($worksheet->templatePages, new WorksheetTemplatePagesTransformer)['data']
		]);
	}
}
