<?php

namespace App\Http\Controllers;

use PDF;
use Request;
use FlySystem;
use Carbon\Carbon;
use App\Helpers\SecurityCheck;
use Sorskod\Larasponse\Larasponse;
use App\Services\Folders\FolderService;
use App\Services\Folders\MoveFileService;
use App\Repositories\JobRepository;
use App\Services\Resources\ResourceServices;
use App\Services\Templates\TemplateServices;
use App\Repositories\TemplatesRepository;
use App\Transformers\TemplateTransformer;
use App\Events\Folders\TemplateDeleteFile;
use App\Events\Folders\TemplateRestoreFile;
use App\Exceptions\InvalidDivisionException;
use App\Transformers\TemplatePageTransformer;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\GoogleDriveFileNoteFound;
use App\Exceptions\Folders\DuplicateFolderException;
use App\Exceptions\GoogleSheetTokenExpiredException;
use App\Services\HtmlValidateService;
use App\Exceptions\HTMLValidateException;
use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\CompanyCamPhotoIdInvalidException;
use App\Exceptions\UnauthorizedException;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Validator;
use APp\Models\ApiResponse;
use APp\Models\Template;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Exception;

class TemplatesController extends ApiController
{

    protected $repo;
    protected $response;
    protected $folderService;
	protected $moveFolderFileService;

	public function __construct(
        TemplateServices $service,
        TemplatesRepository $repo,
        FolderService $folderService,
        MoveFileService $moveFolderFileService,
        HtmlValidateService $htmlValidateService,
        Larasponse $response
    ) {
        $this->repo = $repo;
        $this->response = $response;
        $this->folderService = $folderService;
		$this->moveFolderFileService = $moveFolderFileService;
        $this->service = $service;
        $this->htmlValidateService = $htmlValidateService;

        parent::__construct();
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /templates
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        try{
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
            $validator = Validator::make($input, ['type' => 'required|in:estimate,proposal,blank']);
			if( $validator->fails() ){

				return ApiResponse::validation($validator);
			}
			$input['limit'] = $limit;
			$templates = $this->repo->get($input);

            if (!$limit) {
                return ApiResponse::success($this->response->collection($templates, new TemplateTransformer));
            }
            // $templates = $templates->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($templates, new TemplateTransformer));
        } catch(InvalidDivisionException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /templates
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy(
            'type',
            'title',
            'trades',
            'pages',
            'option',
            'page_type',
            'insurance_estimate',
            'for_all_trades',
            'division_ids',
            'all_divisions_access',
            'parent_id'
        );

        $validator = Validator::make($input, Template::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            foreach ($input['pages'] as $page) {
				$this->htmlValidateService->content($page['content']);
	        }

            $template = $this->service->createTemplate(
                $input['title'],
                $input['type'],
                $input['trades'],
                $input['pages'],
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Template']),
                'template' => $this->response->item($template, new TemplateTransformer)
            ]);
        } catch(HTMLValidateException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /templates/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $scope = App::make(\App\Services\Contexts\Context::class);
        $template = $this->repo->getById($id);
        if (config('is_mobile')
            && in_array($scope->id(), config('disable_proposal_for_company'))
            && $template->type == 'proposal') {
            return ApiResponse::errorInternal(trans('response.error.proposal_tool_soon_available'));
        }
        //@TO DO
        Request::replace(['group_id' => $template->group_id]);
        // dd( in_array($scope->id(), config('disable_proposal_for_company') ) );

        return ApiResponse::success([
            'data' => $this->response->item($template, new TemplateTransformer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /templates/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $template = Template::findOrFail($id);

        if ($template->google_sheet_id) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        if ($template->type == 'blank' && $template->company_id == null) {
            return ApiResponse::errorGeneral(trans('response.error.not_updated', ['attribute' => 'This record']));
        }

        $input = Request::all();
        $validator = Validator::make($input, Template::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            foreach ($input['pages'] as $page) {
				$this->htmlValidateService->content($page['content']);
	        }

            $this->service->updateTemplate($template, $input);
            $template = Template::find($template->id);
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Template']),
                'template' => $this->response->item($template, new TemplateTransformer)
            ]);
        }catch(HTMLValidateException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function createGoogleSheet()
    {
        $input = Request::onlyLegacy('type', 'title', 'trades', 'for_all_trades', 'file', 'google_sheet_id');

        $validator = Validator::make($input, Template::getCreateGoogleSheetRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $template = $this->service->createGoogleSheet(
                $input['title'],
                $input['type'],
                $input['trades'],
                $input
            );
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(GoogleSheetTokenExpiredException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Template']),
            'data' => $this->response->item($template, new TemplateTransformer)
        ]);
    }

    /**
     * Delete Page
     * DELETE /templates/page/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function delete_page($pageId)
    {
        $page = TemplatePage::findOrFail($pageId);
        try {
            $this->service->deletePage($page);
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Page'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /templates/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
		try {
			$inputs = Request::all();
			$template = Template::findOrFail($id);

			if($template->type == 'blank' && $template->company_id == Null) {
				return ApiResponse::errorGeneral(trans('response.error.not_deleted', ['attribute' => 'This record']));
			}

			// $template->trades()->detach();
			if($template->delete()){
				Event::fire('JobProgress.Templates.Events.Folder.deleteFile', new TemplateDeleteFile($template->id, $template->type));
				DB::commit();
				return ApiResponse::success([
					'message' => trans('response.success.deleted', ['attribute' => 'Template'])
				]);
			}
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
		DB::rollback();
    	return ApiResponse::errorInternal();
    }

    public function restore($id)
	{
		if(!Auth::user()->isAuthority()) {
			return ApiResponse::errorForbidden();
		}

		if(!SecurityCheck::verifyPassword()) {
			return SecurityCheck::$error;
		}

		$template = $this->repo->getDeletedById($id);

		try {
			$template->restore();
			$template->deleted_by = null;
			$template->save();

            Event::fire('JobProgress.Templates.Events.Folder.restoreFile', new TemplateRestoreFile($template->id, $template->type));

		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		return ApiResponse::success([
			'message' => trans('response.success.restored', ['attribute' => 'Template'])
		]);
	}

    /**
     * Upload attachment image for template
     * POST /templates/image
     *
     * @return Response
     */
    public function upload_image()
    {

        $input = Request::all();
        $validator = Validator::make($input, Template::getUploadImageRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = null;
		if(ine($input, 'job_id')) {
			$jobRepo = App::make(JobRepository::class);
			$job = $jobRepo->getById($input['job_id']);
		}

        if (Request::hasFile('image')) {
            $filename = Carbon::now()->timestamp . '_' . rand() . '.jpg';
            $baseName = 'templates/media/' . $filename;
            $fullPath = config('jp.BASE_PATH') . $baseName;
            $image = \Image::make(Request::file('image'));
            $url = FlySystem::uploadPublicaly($fullPath, $image->encode()->getEncoded());

            if($job) {
				$photoDir = $job->getMetaByKey('default_photo_dir');
				$resourceService = App::make(ResourceServices::class);
				$resourceService->uploadFile($photoDir, Request::file('image'));
            }

            return ApiResponse::success([
                'data' => [
                    'image' => $url,
                ]
            ]);
        }

        // attach image from estimates or resources.. (already uploaded files)
        if (Request::has('attachments')) {
            try {
                $urls = $this->handleResources($input['attachments']);

                return ApiResponse::success(['data' => $urls]);
            } catch(UnauthorizedException $e) {

				return ApiResponse::errorUnauthorized($e->getMessage());
			} catch (CompanyCamPhotoIdInvalidException $e) {

				return ApiResponse::errorGeneral($e->getMessage());
			} catch(Exception $e) {
                return ApiResponse::errorInternal(trans('response.error.internal'), $e);
            }
        }
    }

    /**
     * Delete image attachment by url..
     * DELETE /templates/image
     *
     * @return Response
     */
    public function delete_attachment_image()
    {
        $input = Request::onlyLegacy('url');
        $validator = Validator::make($input, ['url' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $fileName = basename($input['url']);
        $basePath = 'templates/media/' . $fileName;
        $filePath = config('jp.BASE_PATH') . $basePath;
        // get file extension..
        $extension = File::extension($filePath);

        try {
            if (!empty($fileName) && !empty($extension) && FlySystem::has($filePath)
            ) {
                FlySystem::delete($filePath);

                return ApiResponse::success([
                    'message' => trans('response.success.deleted', ['attribute' => 'Image'])
                ]);
            }
            return ApiResponse::errorInternal('Invalid url');
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function download($id)
    {
        $template = Template::findOrFail($id);

        if ($template->google_sheet_id) {
            $googleSheetService = App::make(GoogleSheetService::class);

            return $googleSheetService->downloadFile($template->google_sheet_id);
        }

        $pages = $template->pages;

        $pageSize = 'A4';

        if ($template->type == 'estimate') {
            $viewTemplate = 'templates.estimate';
            $marginTop = 0;
            $marginBottom = 0;
            $pageHeight = '23.78cm';
        } else {
            $viewTemplate = 'templates.multipages';
            $marginTop = '0.5cm';
            $marginBottom = '0.5cm';
            $pageHeight = '23.9cm';
        }

        if (($template->page_type == 'legal-page')) {
            $pageSize = 'Legal';
            if ($template->type == 'estimate') {
                $pageHeight = '28.5cm';
            } else {
                $pageHeight = '28.6cm';
            }
        }
        $pageType = $template->page_type;

        // return view($viewTemplate, ['pages' => $template->pages, 'pageType' => $pageType]);
        return $pdf = PDF::loadView($viewTemplate, ['pages' => $template->pages, 'pageType' => $pageType])
            ->setOption('page-size', $pageSize)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', $marginTop)
            ->setOption('margin-bottom', $marginBottom)
            ->setOption('page-width', '16.8cm')
            ->setOption('page-height', $pageHeight)
            ->stream('template.pdf');
    }

    /**
     * Change Template Type (Estimate, Proposal,...)
     *
     * @param  int $id ,type
     * @return Response
     */
    public function changeTemplateType($id)
    {
        $template = Template::findOrFail($id);

        if (($template->type == 'blank') && ($template->company_id == null)) {
            return ApiResponse::errorGeneral(Lang::get('response.error.not_updated', [
                'attribute' => 'This record'
            ]));
        }

        $input = Request::onlyLegacy('type');
        $validator = Validator::make($input, Template::getChangeTypeRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $newType = $input['type'];
        try {
            if ($template->type != $newType) {
                $template->type = $newType;
                $template->save();
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Template'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * copy template
     * @return [type] [description]
     */
    public function copyTemplate()
    {
        $input = Request::onlyLegacy('ids', 'company_id', 'password');

        $validator = Validator::make($input, Template::getMoveTemplateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        if ($input['password'] != config('jp.demo_pass')) {
            return ApiResponse::errorUnauthorized();
        }

        DB::beginTransaction();
        try {
            $templates = $this->service->copyTemplate($input['ids'], $input['company_id']);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }

        DB::commit();

        if (empty($templates)) {
            return ApiResponse::success([
                'message' => 'No Template selected.'
            ]);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.copied', ['attribute' => 'Template'])
        ]);
    }

    /**
     * Create Group
     * POST /templates/create_group
     *
     * @return Response
     */
    public function createGroup()
    {
        $input = Request::onlyLegacy('template_ids', 'group_name');

        $validator = Validator::make($input, [
            'template_ids' => 'required|array',
            'group_name' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // if not valid templates ids for grouping..
        if (!$this->repo->isValidTemplateIdsForGrouping($input['template_ids'])) {
            return ApiResponse::errorGeneral('Invalid template ids');
        }

        $groupId = generateUniqueToken();
        $template = Template::where('id', reset($input['template_ids']))->first();

        $this->createTemplatesGroup($groupId, $input['group_name'], arry_fu($input['template_ids']));

        $data = [
            'group_id' => $groupId,
            'group_name' => $input['group_name'],
            'count' => count($input['template_ids']),
            'page_type' => $template->page_type,
            'type' => $template->type,
        ];

        return ApiResponse::success([
            'message' => 'Grouped successfully.',
            'data' => $data,
        ]);
    }

    /**
     * Add to Group
     * POST /templates/add_to_group
     *
     * @return Response
     */
    public function addToGroup()
    {
        $input = Request::onlyLegacy('group_id', 'template_ids');

        $validator = Validator::make($input, [
            'template_ids' => 'required|array',
            'group_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // check is group id valid
        $groupTemplate = $this->repo->getByGroupId($input['group_id']);
        if (!$groupTemplate) {
            return ApiResponse::errorGeneral('Invalid group id');
        }

        // if not valid templates ids for grouping..
        if (!$this->repo->isValidTemplateIdsForGrouping($input['template_ids'])) {
            return ApiResponse::errorGeneral('Invalid template ids');
        }

        $this->createTemplatesGroup($groupTemplate->group_id, $groupTemplate->group_name, arry_fu($input['template_ids']));

        return ApiResponse::success(['message' => 'Grouped successfully.']);
    }

    /**
     * Remove From Group
     * POST /templates/remove_from_group
     *
     * @return Response
     */
    public function removeFromGroup()
    {
        $input = Request::onlyLegacy('template_ids');

        $validator = Validator::make($input, [
            'template_ids' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        Template::whereId($input['template_ids'])->update([
            'group_id' => null,
            'group_name' => null,
        ]);

        return ApiResponse::success(['message' => 'Template(s) removed from group']);
    }

    /**
     * Remove From Group
     * Delete /templates/ungroup
     *
     * @return Response
     */
    public function ungroupTemplates()
    {
        $input = Request::onlyLegacy('group_id');

        $validator = Validator::make($input, [
            'group_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // check is group id valid
        $group = $this->repo->getByGroupId($input['group_id']);
        if (!$group) {
            return ApiResponse::errorGeneral('Invalid group id');
        }

        $templatesIds = Template::whereGroupId($input['group_id'])->pluck('id')->toArray();

        // ungroup the templates
        Template::whereIn('id', $templatesIds)->update([
            'group_id' => null,
            'group_name' => null,
        ]);

        // get ungrouped templates
        $templates = Template::whereIn('id', $templatesIds)->get();

        return ApiResponse::success([
            'message' => 'Templates ungrouped successfully',
            'data' => $this->response->collection($templates, new TemplateTransformer)['data']
        ]);
    }


    /**
     * Get page ids
     * Get /templates/page_ids
     * @return Array of page ids
     */
    public function getPageIds()
    {
        $input = Request::onlyLegacy('template_ids', 'group_ids');

        $validator = Validator::make($input, [
            'template_ids' => 'required_without:group_ids',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $templateIds = [];

        // get valid template ids
        if (ine($input, 'template_ids')) {
            $templateIds = $this->repo->getTemplateIds((array)$input['template_ids']);
        }

        // get valid template ids by group ids
        if (ine($input, 'group_ids')) {
            $ids = $this->repo->getTemplateIdsByGroupId((array)$input['group_ids']);
            $templateIds = array_merge($templateIds, $ids);
        }

        //get template page ids
        $templatePageIds = TemplatePage::whereIn('template_id', (array)$templateIds)
            ->pluck('id')->toArray();

        return ApiResponse::success([
            'data' => $templatePageIds
        ]);
    }

    /**
     * Get single template page by id
     * @param  Int $id Template page id
     * @return Response
     */
    public function getSinglePage($id)
    {
        $page = $this->repo->getPageByPageId($id);

        return ApiResponse::success([
            'data' => $this->response->item($page, new TemplatePageTransformer)
        ]);
    }

    /**
     * Get templates by group ids
     * Get templates/by_groups
     * @return Templates
     */
    public function getTemplatesByGroupIds()
    {
        $input = Request::onlyLegacy('group_ids');

        $validator = Validator::make($input, ['group_ids' => 'array|required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $templates = $this->service->getTemplatesByGroupIds((array)$input['group_ids']);

        return ApiResponse::success(['data' => $templates]);
    }

    /**
     * Archive Templates by template id
     * Put templates/id/archive
     * @param  Int $id [description]
     * @return [type]     [description]
     */
    public function archive($id)
    {
        $input = Request::onlyLegacy('archive');
        $template = $this->repo->getById($id);
        try {
            $archived = null;
            $msg = trans('response.success.restored', ['attribute' => 'Template']);

            if (ine($input, 'archive')) {
                $archived = \Carbon\Carbon::now();
                $msg = trans('response.success.archived', ['attribute' => 'Template']);
            }
            $template->archived = $archived;
            $template->update();

            return ApiResponse::success([
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Assign template division
     */
    public function assignDivision($id)
    {
        $input = Request::onlyLegacy('division_ids');
        $validator = Validator::make($input, ['division_ids' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $template = $this->repo->getById($id);
            $this->repo->assignDivisions($template, $input['division_ids']);
            return ApiResponse::success([
                'message' => trans('response.success.changed', ['attribute' => 'Template divisions']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
	 * Create folder in templates.
	 *
	 * POST - /templates/folder
	 * @return json response.
	 */
	public function createFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, Template::getFolderRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {

			$item = $this->folderService->createTemplateFolder($inputs);

			return ApiResponse::success([
				'data' => $this->response->item($item, new TemplateTransformer)
			]);
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function moveFilesToFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, Template::getDocumentMoveRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		DB::beginTransaction();
		try {
			$type = $inputs['type'];
			$ids = (array)$inputs['ids'];
			$parentId = issetRetrun($inputs, 'parent_id');

			$groupIds = issetRetrun($inputs, 'group_ids');
			if($groupIds) {

				$refIds = $this->repo->getTemplateIdsByGroupId((array)$groupIds);
				if($refIds) {
					$ids = array_merge($ids, $refIds);
				}
			}
			if(!$ids = array_values(array_filter($ids))) {
				throw new FolderNotExistException("Invalid Ids", IlluminateResponse::HTTP_PRECONDITION_FAILED);
			}

			$items = $this->moveFolderFileService->moveTemplateFiles($ids, $type, $parentId, $inputs);
			DB::commit();
			return ApiResponse::success($this->response->collection($items, new TemplateTransformer));
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /************** Private Section ******************/
    /**
     * Copy resources or estimates images for template attachments
     * @param  Array $attachments | Data of resources or estimates
     * @return json response
     */
    private function handleResources($attachments)
    {
        $urls = [];

        if (empty($attachments)) {
            return $urls;
        }

        foreach ((array)$attachments as $key => $attachment) {
            // validat data formate
            if (!ine($attachment, 'type') || !ine($attachment, 'value')) {
                throw new \Exception("Invalid attachment data", 412);
            }

            if ($attachment['type'] == 'resource') {
                $resourcesRepo = App::make(\App\Repositories\ResourcesRepository::class);
                $file = $resourcesRepo->getFile($attachment['value']);
                $filePath = config('resources.BASE_PATH').$file->path;
                if($file->multi_size_image) {
                    $filePath = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', "_pv768$1", $filePath);
                }
            } elseif ($attachment['type'] == 'estimate') {
                $estimateRepo = App::make(\App\Repositories\EstimationsRepository::class);
                $file = $estimateRepo->getById($attachment['value']);
                $filePath = config('jp.BASE_PATH') . $file->file_path;
            }elseif ($attachment['type'] == 'company_cam') {
				$url = $this->service->saveCompanyCamImage($attachment['value']);
				$urls[$key]['url']   = $url;
				$urls[$key]['type']  = $attachment['type'];
				$urls[$key]['value'] = $attachment['value'];
				continue;
			} else {
                continue;
            }
            // get file extension..
            $extension = File::extension($filePath);

            // create physical file name..
            $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;
            $basePath = 'templates/media/' . $physicalName;

            $destination = config('jp.BASE_PATH') . $basePath;

            // copy file to attachment directory..

            if (FlySystem::copy($filePath, $destination, ['ACL' => 'public-read'])) {
                $path = config('jp.BASE_PATH') . $basePath;
                $urls[$key]['url'] = FlySystem::getUrl($path, false);
                $urls[$key]['type'] = $attachment['type'];
                $urls[$key]['value'] = $attachment['value'];
            }
        }

        return $urls;
    }

    /**
     * Create template group
     * @param  Int $groupId Group Id
     * @param  String $groupName Group Name
     * @param  Array $ids Array of ids
     * @return Boolean
     */
    private function createTemplatesGroup($groupId, $groupName, $ids)
    {
        $caseString = 'CASE id';
        $groupName = addslashes($groupName);
        
        foreach ($ids as $key => $id) {
            $key++;
            $caseString .= " WHEN $id THEN $key";
        }

        $ids = implode(', ', $ids);

        DB::statement("UPDATE templates SET group_order = $caseString END, group_id = '{$groupId}', group_name = '{$groupName}' WHERE id IN ($ids)");

        return true;
    }
}
