<?php

namespace App\Services\MaterialLists;

use App\Events\MaterialListCreated;
use App\Models\Material;
use App\Models\MaterialList;
use App\Models\SerialNumber;
use App\Repositories\MaterialListRepository;
use FlySystem;
use App\Services\SerialNumbers\SerialNumberService;
use App\Services\Worksheets\WorksheetsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use PDF;
use Image;
use Settings;
use App\Services\FileSystem\FileService;
use App\Events\Folders\JobMaterialListStoreFile;
use App\Models\Folder;
use App\Models\Supplier;
use App\Models\Job;
use App\Services\Folders\FolderService;

class MaterialListService
{

    public function __construct(
        MaterialListRepository $repo,
        SerialNumberService $serialNoService,
        WorksheetsService $worksheetsService,
        FileService $fileService
    ) {

        $this->repo = $repo;
        $this->serialNoService = $serialNoService;
        $this->worksheetsService = $worksheetsService;
        $this->fileService = $fileService;
    }

    public function get($filters)
	{
		return $this->repo->get($filters);
	}

    /**
     * Save Material
     * @param  int $jobId job id
     * @param  int $worksheetId worksheet id
     * @param  string $title title
     * @param  int $serialNumber serial numb
     * @param  array $meta Meta information
     * @return Material List
     */
    public function saveMaterial($jobId, $worksheetId, $meta = [])
    {
        $linkId = null;
        $linkType = null;

        if (ine($meta, 'link_type') && ine($meta, 'link_id')) {
            $linkType = $meta['link_type'];
            $linkId = $meta['link_id'];
        }
        $serialNumber = ine($meta, 'serial_number') ? $meta['serial_number'] : null;

        if ((!$serialNumber) && ine($meta, 'type')) {
            $serialNumber = $this->getSerialNumber($meta['type']);

            if(ine($meta,'for_supplier_id')) {
				$srsSupplier = Supplier::srs();
				if($srsSupplier->id == $meta['for_supplier_id']) {
					$job = Job::find($jobId);

					if($srsSupplier->companySupplier && $job->purchase_order_number) {
						$serialNumber = $job->purchase_order_number.'-'.$serialNumber;
					}
				}
			}
        }

        $materialList = $this->repo->save(
            $jobId,
            $worksheetId,
            $linkType,
            $linkId,
            $serialNumber,
            Auth::id(),
            $meta
        );

        return $materialList;
    }

    /**
     * Create Supplier Material List
     * @param  array $input | Input data
     * @return materialList
     */
    public function createSupplierMaterialList($input)
    {
        DB::beginTransaction();
        try {

            $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_MATERIAL_LIST);
			}
            // create material List with worksheet..
            $worksheet = $this->worksheetsService->createOrUpdateWorksheet($input);
            $materialList = $worksheet->materialList;

            // create supplier template pdf..
            $contents = \view('worksheets.suppliers.abc-template', ['content' => $input['template']])->render();

            $pdf = PDF::loadHTML($contents)
                ->setPaper('a4')
                ->setOrientation('portrait')
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setOption('dpi', 200);

            $name = $materialList->id . '_' . timestamp();
            $fileName = 'material_lists/' . $name . '.pdf';
            $thumbName = 'material_lists/thumb/' . $name . '.jpg';
            // full paths..
            $fullPath = config('jp.BASE_PATH') . $fileName;
            $thumbFullPath = config('jp.BASE_PATH') . $thumbName;
            FlySystem::write($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);
            $fileSize = FlySystem::getSize($fullPath);
            // create thumb
            $snappy = App::make('snappy.image');
            $image = $snappy->getOutputFromHtml($contents);
            $image = Image::make($image);
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

            // attach supplier pdf file to material list..
            $materialList->file_name = $materialList->title . '.pdf';
            $materialList->file_path = $fileName;
            $materialList->file_size = $fileSize;
            $materialList->file_mime_type = 'application/pdf';
            $materialList->thumb = $thumbName;
            $materialList->template = $input['template'];
            $materialList->save();
            $eventData = [
				'reference_id' => $materialList->id,
				'job_id' => $materialList->job_id,
				'name' => $materialList->id,
				'parent_id' => $parentId,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMaterialListStoreFile($eventData));
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        return $materialList;
    }

    public function rename($materialList, $title)
    {
        $materialList->title = $title;
        $materialList->save();

        if ($worksheet = $materialList->worksheet) {
            $worksheet->name = $title;
            $worksheet->save();
        }

        return $materialList;
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    public function getFilteredMaterials($filters)
    {
        return $this->repo->getFilteredMaterials($filters);
    }

    /**
     * Upload File
     * @param  Int $jobId Job Id
     * @param  String $type Type
     * @param  File $file File Data
     * @return Response
     */
    public function uploadFile($jobId, $type, $file, $input = [])
    {
        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
		if($parentId) {
			$folderService = app(FolderService::class);
			$parentDir = $folderService->getParentDir($parentId, Folder::JOB_MATERIAL_LIST);
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

        $thumbBasePath = null;
        $mimeType = $file->getMimeType();
        $originalName = ine($input, 'file_name') ? addExtIfMissing($input['file_name'], $mimeType) : $file->getClientOriginalName();

        if (ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $originalName = substr($originalName, 0, strpos($originalName, '.')) . '.pdf';
            $physicallyName = uniqueTimestamp() . '_' . str_replace(' ', '_', strtolower($originalName));
            $basePath = 'material_lists/' . $physicallyName;
            $fullPath = config('jp.BASE_PATH') . $basePath;
            $mimeType = 'application/pdf';
            $imgContent = base64_encode(file_get_contents($file));
            $data = [
                'imgContent' => $imgContent,
            ];

            $content = \view('resources.single_img_as_pdf', $data)->render();

            $pdf = PDF::loadHTML($content)->setPaper('a4')->setOrientation('portrait');
            $pdf->setOption('dpi', 200);

            //upload file..
            FlySystem::write($fullPath, $pdf->output(), ['ContentType' => $mimeType]);

            $fileSize = FlySystem::getSize($fullPath);
        } else {
            $physicallyName = uniqueTimestamp() . '_' . str_replace(' ', '_', strtolower($originalName));
            $basePath = 'material_lists/' . $physicallyName;
            $fullPath = config('jp.BASE_PATH') . $basePath;
            $fileSize = $file->getSize();

            //upload file..
            if (in_array($mimeType, config('resources.image_types'))) {
                $image = \Image::make($file)->orientate();

            if(isTrue(Settings::get('WATERMARK_PHOTO'))) {
                $image = addPhotoWatermark($image, $jobId);
            }

                FlySystem::put($fullPath, $image->encode()->getEncoded(), ['ContentType' => $mimeType]);
            } else {
                FlySystem::writeStream($fullPath, $file, ['ContentType' => $mimeType]);
            }
        }

        // save thumb for images..
        if (!ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath = 'material_lists/thumb/' . $physicallyName;
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

        $serialNumber = $this->getSerialNumber($type);

        $fileName = ine($input, 'file_name') ? $input['file_name'] : $originalName;

        $materialList = $this->repo->saveUploadedFile(
            $jobId,
            $type,
            $fileName,
            $basePath,
            $mimeType,
            $fileSize,
            $serialNumber,
            $createdBy = \Auth::id(),
            $thumbBasePath
        );

        $eventData = [
			'reference_id' => $materialList->id,
			'job_id' => $materialList->job_id,
			'name' => $materialList->id,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMaterialListStoreFile($eventData));

        //material list created event activity log
        Event::fire('JobProgress.MaterialLists.Events.MaterialListCreated', new MaterialListCreated($materialList));

        return $materialList;
    }

    /**
     * create material list from file contents
     * @param  Int      $jobId          | Id of Job
     * @param  String   $fileContent    | File content
     * @param  String   $name           | File name
     * @param  String   $mimeType       | File mime type
     * @return $workOrder
     */
    public function createFileFromContents($jobId, $fileContent, $name, $mimeType, $fileName = null)
    {
        $type         = SerialNumber::MATERIAL_LIST;
        $physicalName = uniqueTimestamp().'_'.str_replace(' ', '_', strtolower($name));
        $basePath     = 'material_lists/' . $physicalName;
        $fullPath     = config('jp.BASE_PATH').$basePath;
        FlySystem::put($fullPath, $fileContent, ['ContentType' => $mimeType]);
        $fileSize = FlySystem::getSize($fullPath);
        $thumbBasePath = null;
        if(in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath  = 'material_lists/thumb/' . $physicalName;
            $thumbPath      = config('jp.BASE_PATH'). $thumbBasePath;
            $image = \Image::make($fileContent);
            if($image->height() > $image->width()) {
                $image->heighten(200, function($constraint) {
                    $constraint->upsize();
                });
            }else {
                $image->widen(200, function($constraint) {
                    $constraint->upsize();
                });
            }

            FlySystem::put($thumbPath, $image->encode()->getEncoded());
        }
        $serialNumber = $this->getSerialNumber($type);
        $createdBy = Auth::id();
        $name = $fileName ? $fileName : $name;
        $materialList = $this->repo->saveUploadedFile(
            $jobId,
            $type,
            $name,
            $basePath,
            $mimeType,
            $fileSize,
            $serialNumber,
            $createdBy,
            $thumbBasePath
        );

        $eventData = [
			'reference_id' => $materialList->id,
			'job_id' => $materialList->job_id,
			'name' => $materialList->id,
			'parent_id' => null,
		];
        Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMaterialListStoreFile($eventData));

        //work order created event activity log
        Event::fire('JobProgress.MaterialLists.Events.MaterialListCreated', new MaterialListCreated($materialList));
        return $materialList;
    }

    /**
     * Rotate image
     * @param  Instance $materialList Material List
     * @param  integer $rotateAngle Rotation Angle
     * @return Material List
     */
    public function rotateImage($materialList, $rotateAngle = 0)
    {
        $oldFilePath = $materialList->file_path;
        $oldThumbPath = $materialList->thumb;

        //rotate image
        $ext = File::extension($materialList->file_path);
        $physicallyName = uniqueTimestamp() . '.' . $ext;
        $newFilePath = 'material_lists/' . $physicallyName;
        $this->rotate($materialList->file_path, $newFilePath, $rotateAngle);

        //rotate thumb
        $newThumbPath = 'material_lists/thumb/' . $physicallyName;
        $this->rotate($materialList->thumb, $newThumbPath, $rotateAngle);

        //update materiallist with file path
        $materialList->update([
            'file_path' => $newFilePath,
            'thumb' => $newThumbPath
        ]);

        if (!empty($oldFilePath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldFilePath);
        }

        if (!empty($oldThumbPath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldThumbPath);
        }

        return $materialList;
    }

    /**
     * Get Serial Number
     * @param  String $type Type
     * @return Serial Number
     */
    private function getSerialNumber($type)
    {
        $serialNumber = null;
        switch ($type) {
            case SerialNumber::MATERIAL_LIST:
                $serialNumber = $this->serialNoService->generateSerialNumber(SerialNumber::MATERIAL_LIST);
                break;
            case SerialNumber::WORK_ORDER:
                $serialNumber = $this->serialNoService->generateSerialNumber(SerialNumber::WORK_ORDER);
                break;
        }

        return $serialNumber;
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
}
