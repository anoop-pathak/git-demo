<?php

namespace App\Services\Templates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Models\Folder;
use App\Models\Template;
use FlySystem;
use App\Models\TemplatePage;
use Carbon\Carbon;
use App\Services\Contexts\Context;
use App\Services\Folders\FolderService;
use App\Services\Google\GoogleSheetService;
use App\Services\Resources\ResourceServices;
use App\Services\CompanyCam\CompanyCamService;
use App\Events\Folders\TemplateStoreFile;
use App\Repositories\TemplatesRepository;
use App\Models\PageTableCalculation;
use Illuminate\Support\Facades\Auth;
use Exception;

class TemplateServices
{
    protected $scope;

    /**
     * App\Repositories\TemplatesRepository
     * @var repo
     */
    protected $repo;

    public function __construct(
        TemplatesRepository $repo,
        GoogleSheetService $googleSheetService,
        Context $scope,
		ResourceServices $resourcesService,
		CompanyCamService $companyCamService
    ) {

        $this->repo = $repo;
        $this->googleSheetService = $googleSheetService;
        $this->scope = $scope;
        $this->resourcesService = $resourcesService;
        $this->companyCamService = $companyCamService;
    }

    /**
     * Create Template
     * @param  string $title | Template Title
     * @param  string $type | Template type e.g., estimate or proposal
     * @param  array $trades | Array of trades ids
     * @param  array $pages | Array of template pages
     * @return template
     */
    public function createTemplate($title, $type, $trades, $pages, $meta = [])
    {
        DB::beginTransaction();
        try {
            //to check folder / parent dir exist.
			$parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$folderService->getParentDir($parentId, Folder::TEMPLATE_TYPE_PREFIX.$meta['type']);
            }

            $template = $this->repo->createTemplate($title, $type, $trades, Auth::id(), $meta);
            foreach ((array)$pages as $key => $page) {
                $content = isset($page['content']) ? $page['content'] : "";
                $order = $key + 1;
                $this->createPage($template, $content, $order, $page);
            }

            if($parentId) {
				$template->parent_id = (int)$parentId;
			}

			$eventData = [
				'reference_id' => $template->id,
				'name' => $template->id,
				'parent_id' => $parentId,
				'type' => $type,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new TemplateStoreFile($eventData));
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        return $template;
    }

    /**
     * Update Template
     * @param  Template $template | Template
     * @param  array $data | Data
     * @return [type]             [description]
     */
    public function updateTemplate(Template $template, $data)
    {

        DB::beginTransaction();
        try {
            $template->updated_at = Carbon::now();
            $template->update($data);
            $template->trades()->detach();
            if (!$template->for_all_trades) {
                $template->trades()->attach($data['trades']);
            }

            if($template->all_divisions_access){
				$template->divisions()->sync([]);
            }

            if(isset($data['division_ids'])){
                $this->repo->assignDivisions($template, $data['division_ids']);
            }
            $existingPages = $template->pages;
            foreach ((array)$data['pages'] as $key => $page) {
                $content = isset($page['content']) ? $page['content'] : "";
                $order = $key + 1;
                $this->createPage($template, $content, $order, $page);
            }
            if ($existingPages->count()) {
                foreach ($existingPages as $page) {
                    $this->deletePage($page);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        return $template;
    }

    public function createGoogleSheet($title, $type, $trades, $meta = [])
    {
        DB::beginTransaction();
        try {
            $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::TEMPLATE_TYPE_PREFIX.$meta['type']);
            }

            $template = $this->repo->createTemplate($title, $type, $trades, Auth::id(), $meta);

            $eventData = [
				'reference_id' => $template->id,
				'name' => $template->id,
				'parent_id' => $parentId,
				'type' => $type,
            ];

			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new TemplateStoreFile($eventData));

            if (isset($meta['file'])) {
                $sheetId = $this->googleSheetService->uploadFile(
                    $meta['file'],
                    $template->title
                );
            } elseif (isset($meta['google_sheet_id'])) {
                $sheetId = $this->googleSheetService->createFromExistingSheet(
                    $meta['google_sheet_id'],
                    $template->title
                );
            } else {
                $sheetId = $this->googleSheetService->createEmptySpreadSheet($template->title);
            }

            $template->google_sheet_id = $sheetId;

            $template->save();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        return $template;
    }

    /**
     * Save template Page
     * @param  Template $template | template
     * @param  text $content | Html content of template page
     * @param  int $order | Page Order
     * @param  array $meta | Meta Data
     * @return Page
     */
    public function createPage($template, $content, $order, $meta = [])
    {
        $fileName = $this->createThumb($content, $template);
        $page = TemplatePage::create([
            'template_id' => $template->id,
            'content' => $content,
            'image' => 'templates/' . $fileName,
            'thumb' => 'templates/thumb/' . $fileName,
            'editable_content' => isset($meta['editable_content']) ? $meta['editable_content'] : null,
            'order' => $order,
            'auto_fill_required' => ine($meta, 'auto_fill_required') ? $meta['auto_fill_required'] : null
        ]);

        if(ine($meta, 'tables')) {
			foreach ((array)$meta['tables'] as $table) {
				$pageTableCalculation = PageTableCalculation::create([
					'page_type' => PageTableCalculation::TEMPLATE_PAGE_TYPE,
					'type_id' => $page->template_id,
					'page_id' => $page->id,
					'name' 	  => isset($table['name']) ? $table['name'] : null,
					'ref_id'  => $table['ref_id'],
					'head'    => $table['head'],
					'body'    => $table['body'],
					'foot'    => $table['foot'],
					'options' => ine($table, 'options') ? $table['options']  : [],
				]);
			}
        }

        return $page;
    }

    public function deletePage(TemplatePage $page)
    {

        if (!empty($page->image)) {
            $filePath = config('jp.BASE_PATH') . $page->image;
            FlySystem::delete($filePath);
        }

        if (!empty($page->thumb)) {
            $filePath = config('jp.BASE_PATH') . $page->thumb;
            FlySystem::delete($filePath);
        }

        $page->pageTableCalculations()->delete();
        $page->delete();
        return true;
    }

    /**
     * use for copy template
     * @param  [type] $ids       [template ids]
     * @param  [type] $companyId [company id]
     * @return [array]            [copy templates]
     */
    public function copyTemplate($ids, $companyId)
    {
        $templates = Template::whereIn('id', (array)$ids)
            ->with('trades', 'pages')
            ->get();

        $templateCopied = [];

        foreach ($templates as $template) {
            $templateCopy = $template->replicate();
            $templateCopy->created_by = 1;
            $templateCopy->company_id = $companyId;
            $templateCopy->save();

            $eventData = [
				'reference_id' => $templateCopy->id,
				'name' => $templateCopy->id,
				'parent_id' => null,
				'type' => Folder::TEMPLATE_TYPE_PREFIX.$templateCopy->type,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new TemplateStoreFile($eventData));

            //save pages
            if ($template->pages->count() > 0) {
                $pagesArray = [];
                foreach ($template->pages as $page) {
                    $pageCopy = $page->replicate();
                    $pageCopy->template_id = $templateCopy->id;
                    $pageCalculations = $page->pageTableCalculations;
                    foreach ($pageCalculations as $pageCalculation) {
                        $pageTableCalculation = PageTableCalculation::create([
                            'page_type' => $pageCalculation->page_type,
                            'page_id' => $templateCopy->id,
                            'name' 	  => isset($pageCalculation['name']) ? $pageCalculation['name'] : null,
                            'ref_id'  => $pageCalculation['ref_id'],
                            'head'    => $pageCalculation['head'],
                            'body'    => $pageCalculation['body'],
                            'foot'    => $pageCalculation['foot'],
                            'options' => ine($pageCalculation, 'options') ? $pageCalculation['options']  : null
                        ]);
                    }
                    array_push($pagesArray, $pageCopy);
                }
                $templateCopy->pages()->saveMany($pagesArray);
            }

            //attach trades
            if ($template->trades->count() > 0) {
                $ids = $template->trades->pluck('id')->toArray();
                $templateCopy->trades()->attach($ids);
            }

            //attach divisions
			if($template->divisions->count() > 0) {
				$ids = $template->divisions->pluck('id')->toArray();
				$templateCopy->divisions()->sync($ids);
            }

            $templateCopied[] = $templateCopy;
        }
        return $templateCopied;
    }

    /**
     * Get templates by group ids
     * @param  array $groupIds group ids
     * @return Templates
     */
    public function getTemplatesByGroupIds(array $groupIds = [])
    {
        $columns = ['id', 'group_id', 'group_name', 'title', 'type', 'page_type', 'insurance_estimate', 'group_order'];

        return $this->repo->getTemplatesByGroupIds($groupIds, $columns);
    }

    public function saveCompanyCamImage($photoId)
	{
		try {
			$photoUrl = $this->companyCamService->getPhotoUrl($photoId);
			// get image contents..
			$imageContent = file_get_contents($photoUrl);
			$name = 'cc_'.timestamp().'_'.uniqid().'.jpg';
			$mimeType = 'image/jpeg';

			$filename  = Carbon::now()->timestamp.'_'.rand().'.jpg';
			$baseName = 'templates/media/' . $name;
			$fullPath = config('jp.BASE_PATH').$baseName;
			$url = FlySystem::uploadPublicaly($fullPath, $imageContent);

			return $url;
		} catch (Exception $e) {
			throw $e;
		}
	}

    /********************* Private Function ************************/

    /**
     * Create Thumb
     * @param  text $templateContent | Html Content
     * @return filename
     */
    private function createThumb($templateContent, $template)
    {

        $pageType = $template->page_type;

        if ($template->type == 'proposal') {
            $pageType .= ' legal-size-proposal a4-size-proposal';
        } else {
            $pageType .= ' legal-size-estimate a4-size-estimate';
        }
        $contents = \view('templates.template', [
            'content' => $templateContent,
            'pageType' => $pageType
        ])->render();

        $filename = Carbon::now()->timestamp . rand() . '.jpg';
        $imageBaseName = 'templates/' . $filename;
        $thumbBaseName = 'templates/thumb/' . $filename;
        $imageFullPath = config('jp.BASE_PATH') . $imageBaseName;
        $thumbFullPath = config('jp.BASE_PATH') . $thumbBaseName;

        $snappy = App::make('snappy.image');
        $snappy->setOption('width', '794');
        if ($pageType == 'legal-page') {
            $snappy->setOption('height', '1344');
        } else {
            $snappy->setOption('height', '1122');
        }

        $image = $snappy->getOutputFromHtml($contents);

        // save image...
        FlySystem::put($imageFullPath, $image, ['ContentType' => 'image/jpeg']);

        // resize for thumb..
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
        // save thumb ..
        FlySystem::put($thumbFullPath, $image->encode()->getEncoded());

        return $filename;
    }
}
