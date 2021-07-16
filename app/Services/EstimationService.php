<?php

namespace App\Services;

use App\Events\EstimationCreated;
use App\Models\Estimation;
use App\Models\EstimationPage;
use App\Repositories\EstimationsRepository;
use FlySystem;
use App\Services\Google\GoogleSheetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use App\Exceptions\InvalidFileException;
use PDF;
use Settings;
use App\Services\FileSystem\FileService;
use App\Events\Folders\JobEstimationStoreFile;
use App\Services\Folders\FolderService;
use App\Models\Folder;

class EstimationService
{

    public function __construct(EstimationsRepository $repo, GoogleSheetService $googleSheetService, FileService $fileService)
    {
        $this->googleSheetService = $googleSheetService;
        $this->fileService = $fileService;
        $this->repo = $repo;
    }

    /**
     * Create Estimate
     * @param  int $jobId | Job Id
     * @param  array $pages | Pages
     * @param  int $createdBy | Createby User Id
     * @param  array $meta | Meta Data
     * @return Estimation
     */
    public function create($jobId, $pages, $createdBy, $meta = [])
    {
        $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
        if($parentId) {
            $folderService = app(FolderService::class);
            $parentDir = $folderService->getParentDir($parentId, Folder::JOB_ESTIMATION);
        }

        DB::beginTransaction();
        try {
            $estimation = $this->repo->saveEstimation($jobId, $createdBy, $meta);
            foreach ((array)$pages as $key => $page) {
                $template = isset($page['template']) ? $page['template'] : "";
                $order = $key + 1;
                $this->createPage($estimation, $template, $order, $page);
            }

            $estimation = $this->createPdf($estimation);

            if($parentId){
				$estimation->parent_id = $parentId;
			}

            $eventData = [
				'reference_id' => $estimation->id,
				'job_id' => $estimation->job_id,
				'name' => $estimation->id,
				'parent_id' => $parentId
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobEstimationStoreFile($eventData));
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        //estimations created event..
        Event::fire('JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated', new EstimationCreated($estimation));

        return $estimation;
    }

    /**
     * Update Estimate
     * @param  Estimation $estimation | Estimation
     * @param  array $data | Data
     * @return [type]             [description]
     */
    public function update(Estimation $estimation, $input)
    {
        DB::beginTransaction();
        try {
            $estimation->update($input);
            $existingPages = $estimation->pages;

            foreach ((array)$input['pages'] as $key => $page) {
                $template = isset($page['template']) ? $page['template'] : "";
                $order = $key + 1;
                $this->createPage($estimation, $template, $order, $page);
            }
            if ($existingPages->count()) {
                foreach ($existingPages as $page) {
                    $this->deletePage($page);
                }
            }
            $estimation = $this->createPdf($estimation);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        return $estimation;
    }

    /**
     * Create Estimate Page
     * @param  Estimation $estimation | Estimation
     * @param  string $template | Html content
     * @param  int $order | Order
     * @param  array $meta | Additional Data
     * @return [type]                 [description]
     */
    public function createPage(Estimation $estimation, $template, $order, $meta = [])
    {
        $page = EstimationPage::create([
            'estimation_id' => $estimation->id,
            'template' => $template,
            'template_cover' => ine($meta, 'template_cover') ? $meta['template_cover'] : null,
            'order' => $order
        ]);
        $thumb = $this->createThumb($page, $estimation->page_type);
    }

    /**
     * Delete Page
     * @param  EstimationPage $page | Page Object
     * @return [type]               [description]
     */
    public function deletePage(EstimationPage $page)
    {

        if (!empty($page->image)) {
            $filePath = \config('jp.BASE_PATH') . $page->image;
            FlySystem::delete($filePath);
        }

        if (!empty($page->thumb)) {
            $filePath = config('jp.BASE_PATH') . $page->thumb;
            FlySystem::delete($filePath);
        }

        $page->delete();

        return true;
    }

    /**
     * Create pdf
     * @param  Estimation $estimation | Estimation Object
     * @return [type]                 [description]
     */
    public function createPdf(Estimation $estimation)
    {
        $existingFile = null;
        if (!empty($estimation->file_path)) {
            $existingFile = config('jp.BASE_PATH') . $estimation->file_path;
        }
        $estimation = Estimation::with('pages')->find($estimation->id);
        $filename = $estimation->id . '_' . Carbon::now()->timestamp . '.pdf';
        $baseName = 'estimations/' . $filename;
        $fullPath = \config('jp.BASE_PATH') . $baseName;

        $pageHeight = '23.78cm';

        if ($estimation->page_type == 'legal-page') {
            $pageHeight = '28.5cm';
        }

        $pdf = PDF::loadView('estimation.multipages', [
            'pages' => $estimation->pages,
            'pageType' => $estimation->page_type,
        ])->setOption('page-size', 'A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('page-width', '16.8cm')
            ->setOption('page-height', $pageHeight);

        $mimeType = 'application/pdf';

        FlySystem::write($fullPath, $pdf->output(), ['ContentType' => $mimeType]);

        $estimation->file_name = $estimation->title . '.pdf';
        $estimation->file_path = $baseName;
        $estimation->file_mime_type = $mimeType;
        $estimation->file_size = FlySystem::getSize($fullPath);
        $estimation->save();

        // delete existing Pdf
        if (!is_null($existingFile)) {
            FlySystem::delete($existingFile);
        }

        return $estimation;
    }

    /**
     * Upload file of estimation.
     * @param  Int $jobId Job Id
     * @param  File $file File Data
     * @return Estimation
     */
    public function uploadFile($jobId, $file, $imageBase64 = null, $input = array())
    {
        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
		if($parentId) {
			$folderService = app(FolderService::class);
			$parentDir = $folderService->getParentDir($parentId, Folder::JOB_ESTIMATION);
        }

        if(ine($input, 'file_url')) {
            $data =  $this->fileService->getDataFromUrl($input['file_url'], $input['file_name']);
            return $this->createFileFromContents(
                $jobId,
                $data['file'],
                $data['file_name'],
                $data['mime_type'],
                $input['file_name']
            );
        }

        if($imageBase64 && !is_file($file)) {
            return $this->uploadBase64($file, $jobId, $input);
        }

        $mimeType = $file->getMimeType();
        $originalName = ine($input, 'file_name') ? addExtIfMissing($input['file_name'], $mimeType) : $file->getClientOriginalName();

        if (ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $originalName = substr($originalName, 0, strpos($originalName, '.')) . '.pdf';
            $mimeType = 'application/pdf';
            $physicalName = generateUniqueToken() . '_' . str_replace(' ', '_', strtolower($originalName));
            $basePath = 'estimations/' . $physicalName;
            $fullPath = config('jp.BASE_PATH') . $basePath;
            $imgContent = base64_encode(file_get_contents($file));

            $data = [
                'imgContent' => $imgContent,
            ];

            $content = \view('resources.single_img_as_pdf', $data)->render();

            $pdf = PDF::loadHTML($content)->setPaper('a4')->setOrientation('portrait');
            $pdf->setOption('dpi', 200);

            $uploaded = FlySystem::write($fullPath, $pdf->output(), ['ContentType' => $mimeType]);
            $fileSize = FlySystem::getSize($fullPath);
        } else {
            $physicalName = generateUniqueToken() . '_' . str_replace(' ', '_', strtolower($originalName));
            $basePath = 'estimations/' . $physicalName;
            $fullPath = \config('jp.BASE_PATH') . $basePath;
            $fileSize = $file->getSize();
            //upload file..
            if (in_array($mimeType, config('resources.image_types'))) {
                $image = \Image::make($file)->orientate();

                if(isTrue(Settings::get('WATERMARK_PHOTO'))) {
                    $image = addPhotoWatermark($image, $jobId);
                }
                $uploaded = FlySystem::put($fullPath, $image->encode()->getEncoded(), ['ContentType' => $mimeType]);
            } else {
                $uploaded = FlySystem::writeStream($fullPath, $file, ['ContentType' => $mimeType]);
            }
        }

        if (!$uploaded) {
            return false;
        }

        // save thumb for images..
        $thumbBasePath = null;
        if (!ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath = 'estimations/thumb/' . $physicalName;
            $thumbPath = config('jp.BASE_PATH') . $thumbBasePath;

            if ($image->height() > $image->width()) {
                $image->heighten(200, function ($constraint) {
                    $constraint->upsize();
                });
            } else {
                $image->widen(200, function ($constraint) {
                    $constraint->upsize();
                });
            }

            FlySystem::put($thumbPath, $image->encode()->getEncoded());
        }

        $fileName = ine($input, 'file_name') ? $input['file_name'] : $originalName;

        $createdBy = \Auth::id();
        $input['title'] = $fileName;

        //add file data..
        $input['is_file'] = true;
        $input['file_name'] = $fileName;
        $input['file_path'] = $basePath;
        $input['file_mime_type'] = $mimeType;
        $input['file_size'] = $fileSize;
        $input['thumb'] = $thumbBasePath;

        //create estimation..
        $estimation = $this->repo->saveEstimation($jobId, $createdBy, $input);

        if($parentId) {
			$estimation->parent_id = (int)$parentId;
		}

		$eventData = [
			'reference_id' => $estimation->id,
			'job_id' => $jobId,
			'name' => $estimation->id,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobEstimationStoreFile($eventData));

        //estimations created event..
        Event::fire('JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated', new EstimationCreated($estimation));

        return $estimation;
    }

    /**
     * Create Estimate File from Contents
     * @param  Int $jobId Job Id
     * @param  string $fileContent | Fine contents
     * @param  string $name | File name
     * @param  string $mimeType | Mime type
     * @return Estimation
     */
    public function createFileFromContents($jobId, $fileContent, $name, $mimeType, $fileName = null)
    {
        $physicalName = uniqueTimestamp() . '_' . str_replace(' ', '_', strtolower($name));
        $basePath = 'estimations/' . $physicalName;
        $fullPath = config('jp.BASE_PATH') . $basePath;

        //save file..
        FlySystem::put($fullPath, $fileContent, ['ContentType' => $mimeType]);

        $fileSize = FlySystem::getSize($fullPath);

        // save thumb for images..
        $thumbBasePath = null;
        if (in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath = 'estimations/thumb/' . $physicalName;
            $thumbPath = config('jp.BASE_PATH') . $thumbBasePath;

            $image = \Image::make($fileContent);
            if ($image->height() > $image->width()) {
                $image->heighten(200, function ($constraint) {
                    $constraint->upsize();
                });
            } else {
                $image->widen(200, function ($constraint) {
                    $constraint->upsize();
                });
            }

            FlySystem::put($thumbPath, $image->encode()->getEncoded());
        }

        $name = $fileName ? $fileName : $name;

        $createdBy = \Auth::id();
        $data['title'] = $name;

        //add file data..
        $data['is_file'] = true;
        $data['file_name'] = $name;
        $data['file_path'] = $basePath;
        $data['file_mime_type'] = $mimeType;
        $data['file_size'] = $fileSize;
        $data['thumb'] = $thumbBasePath;

        //create estimation..
        $estimation = $this->repo->saveEstimation($jobId, $createdBy, $data);

        //estimations created event..
        Event::fire('JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated', new EstimationCreated($estimation));

        return $estimation;
    }

    /**
     * Estimation Rename
     * @param  Instance $estimation Estimation Instance
     * @param  String $title Estimation title
     * @return Estimation
     */
    public function rename($estimation, $title)
    {
        if ($estimation->type == Estimation::GOOGLE_SHEET) {
            $this->googleSheetService->renameSpreadSheet($estimation->google_sheet_id, $title);
        }

        $estimation->title = $title;

        if ($estimation->is_file) {
            $estimation->file_name = $title;
        }

        $estimation->save();

        //estimation name rename
        if ($worksheet = $estimation->worksheet) {
            $worksheet->name = $title;
            $worksheet->save();
        }

        return $estimation;
    }

    /**
     * Rotate image
     * @param  Instance $estimation Estimation
     * @param  integer $rotateAngle Rotation Angle
     * @return Estimation
     */
    public function rotateImage($estimation, $rotateAngle = 0)
    {
        // get file paths..
        $oldFilePath = $estimation->file_path;
        $oldThumbPath = $estimation->thumb;

        //rotate image
        $extension = File::extension($estimation->file_path);
        $physicalName = uniqueTimestamp() . '.' . $extension;
        $newFilePath = 'estimations/' . $physicalName;
        $this->rotate($estimation->file_path, $newFilePath, $rotateAngle);

        //rotate thumb
        $newThumbPath = 'estimations/thumb/' . $physicalName;
        $this->rotate($estimation->thumb, $newThumbPath, $rotateAngle);

        //update estimate image with file path
        $estimation->update([
            'file_path' => $newFilePath,
            'thumb' => $newThumbPath
        ]);

        // delete old files
        if (!empty($oldFilePath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldFilePath);
        }

        if (!empty($oldThumbPath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldThumbPath);
        }

        return $estimation;
    }

    /**
     * Create google sheet
     * @param  Int $jobId Job Id
     * @param  array $input Inputs
     * @return estimate
     */
    public function createGoogleSheet($jobId, $input = [])
    {
        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
        if($parentId) {
            $folderService = app(FolderService::class);
            $parentDir = $folderService->getParentDir($parentId, Folder::JOB_ESTIMATION);
        }

        DB::beginTransaction();
        try {
            $createdBy = \Auth::id();
            //create estimate..
            $estimate = $this->repo->saveEstimation($jobId, $createdBy, $input);

            if (isset($input['file'])) {
                $sheetId = $this->googleSheetService->uploadFile(
                    $input['file'],
                    $estimate->title
                );
            } elseif (isset($input['google_sheet_id'])) {
                $sheetId = $this->googleSheetService->createFromExistingSheet(
                    $input['google_sheet_id'],
                    $estimate->title
                );
            } else {
                $sheetId = $this->googleSheetService->createEmptySpreadSheet($estimate->title);
            }

            $estimate->google_sheet_id = $sheetId;

            $estimate->save();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        $eventData = [
            'reference_id' => $estimate->id,
            'job_id' => $estimate->job_id,
            'name' => $estimate->id,
            'parent_id' => $parentId,
        ];

        Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobEstimationStoreFile($eventData));

        //estiate created event..
        Event::fire('JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated', new EstimationCreated($estimate));

        return $estimate;
    }

    /******************** Private Section ********************/

    /**
     * Create Estimation Page
     * @param  EstimationPage $page [description]
     * @return [type]               [description]
     */
    private function createThumb(EstimationPage $page, $pageType)
    {
        $contents = \view('estimation.estimation')
            ->with('page', $page)
            ->with('pageType', $pageType)
            ->render();
        $filename = Carbon::now()->timestamp . rand() . '.jpg';
        $imageBaseName = 'estimations/' . $filename;
        $thumbBaseName = 'estimations/thumb/' . $filename;
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

        $page->image = $imageBaseName;
        $page->thumb = $thumbBaseName;
        $page->save();

        return $page;
    }

    /**
     * Rotate Image
     * @param  String $oldFilePath Old file path
     * @param  String $newFilePath New file path
     * @param  integer $rotateAngle Rotation Angle
     * @return Response
     */
    private function rotate($oldFilePath, $newFilePath, $rotateAngle = 0)
    {
        $filePath = config('jp.BASE_PATH') . $oldFilePath;
        $basePath = config('jp.BASE_PATH') . $newFilePath;
        $img = \Image::make(\FlySystem::read($filePath));
        $img->rotate($rotateAngle);
        FlySystem::put($basePath, $img->encode()->getEncoded());
    }

    private function uploadBase64($data, $jobId, $meta = array()) 
    {
        $name = uniqueTimestamp(). '.jpg';
        $basePath   = 'estimation/';
        $fullPath   = config('jp.BASE_PATH').$basePath;
        $thumbName = 'estimation/thumb/'. $name;
        $fullThumbPath = config('jp.BASE_PATH'). $thumbName;
        
        $rotationAngle = ine($meta, 'rotation_angle') ? $meta['rotation_angle'] : null;
        $file = uploadBase64Image($data, $fullPath, $name, $rotationAngle, true, null, $fullThumbPath);
        if(!$file) {
            throw new InvalidFileException("Invalid File Type");
        }
        $meta['file_name']      = $file['name'];
        $meta['file_size']      = $file['size'];
        $meta['file_mime_type'] = $file['mime_type'];
        $meta['file_path']      = $basePath . '/' . $name;
        $meta['is_file']        = true;
        $meta['thumb']          = $thumbName;
        $createdBy              = \Auth::id();
        $estimation = $this->repo->saveEstimation($jobId, $createdBy, $meta);

        $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
		$eventData = [
			'reference_id' => $estimation->id,
			'job_id' => $estimation->job_id,
			'name' => $estimation->id,
			'parent_id' => $parentId,
        ];

        Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobEstimationStoreFile($eventData));

        //estimations created event..
        Event::fire('JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated', new EstimationCreated($estimation));
        return $estimation;
    }
}
