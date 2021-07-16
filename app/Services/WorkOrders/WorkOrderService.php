<?php

namespace App\Services\WorkOrders;

use App\Events\WorkOrderCreated;
use App\Repositories\WorkOrderRepository;
use FlySystem;
use App\Services\SerialNumbers\SerialNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use App\Models\SerialNumber;
use PDF;
use Settings;
use App\Services\FileSystem\FileService;
use App\Events\Folders\JobWorkOrderStoreFile;
use App\Services\Folders\FolderService;
use App\Models\Folder;

class WorkOrderService
{

    public function __construct(WorkOrderRepository $repo, SerialNumberService $serialNoService, FileService $fileService)
    {
        $this->repo = $repo;
        $this->serialNoService = $serialNoService;
        $this->fileService = $fileService;
    }

    public function get($input)
	{
		return $this->repo->get($input);
	}

    /**
     * Save Material
     * @param  int $jobId job id
     * @param  int $worksheetId worksheet id
     * @param  string $title title
     * @param  int $serialNumber serial numb
     * @param  array $meta Meta information
     * @return Work Order
     */
    public function saveWorkOrder($jobId, $worksheetId, $meta = [])
    {
        $linkId = null;
        $linkType = null;

        //linking to proposal or estimate
        if (ine($meta, 'link_type') && ine($meta, 'link_id')) {
            $linkType = $meta['link_type'];
            $linkId = $meta['link_id'];
        }

        //get serial number
        $serialNumber = ine($meta, 'serial_number') ? $meta['serial_number'] : null;
        if (!$serialNumber) {
            $serialNumber = $this->getSerialNumber();
        }

        //save work order
        $workOrder = $this->repo->save(
            $jobId,
            $worksheetId,
            $linkType,
            $linkId,
            $serialNumber,
            \Auth::id(),
            $meta
        );

        return $workOrder;
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    /**
     * Work order rename
     * @param  Instance $workOrder Work Order
     * @param  String $title Title
     * @return Work Order
     */
    public function rename($workOrder, $title)
    {
        $workOrder->title = $title;
        $workOrder->save();

        //update worksheet name
        if ($worksheet = $workOrder->worksheet) {
            $worksheet->name = $title;
            $worksheet->save();
        }

        return $workOrder;
    }

    /**
     * Get filtered work orders
     * @param  Array $filters Filters
     * @return Query Builder
     */
    public function getFilteredWorkOrders($filters)
    {
        return $this->repo->getFilteredWorkOrders($filters);
    }

    /**
     * Upload File
     * @param  Int $jobId Job Id
     * @param  File $file File Data
     * @return Response
     */
    public function uploadFile($jobId, $file, $input = [])
    {
        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
		if($parentId) {
			$folderService = app(FolderService::class);
			$parentDir = $folderService->getParentDir($parentId, Folder::JOB_WORK_ORDER);
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

        $serialNumber = $this->getSerialNumber();

        // save thumb for images..
        if (!ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath = 'material_lists/thumb/' . $physicallyName;
            $thumbPath = config('jp.BASE_PATH') . $thumbBasePath;

            $image = \Image::make($file);
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

        //save file
        $workOrder = $this->repo->saveUploadedFile(
            $jobId,
            $fileName,
            $basePath,
            $mimeType,
            $fileSize,
            $serialNumber,
            $createdBy = \Auth::id(),
            $thumbBasePath
        );

        $eventData = [
			'reference_id' => $workOrder->id,
			'name' => $workOrder->id,
			'job_id' => $jobId,
			'parent_id' => $parentId,
        ];

		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobWorkOrderStoreFile($eventData));

        //work order created event activity log
        Event::fire('JobProgress.WorkOrders.Events.WorkOrderCreated', new WorkOrderCreated($workOrder));

        return $workOrder;
    }

    /**
     * create work order from file contents
     * @param  Int      $jobId          | Id of Job
     * @param  String   $fileContent    | File content
     * @param  String   $name           | File name
     * @param  String   $mimeType       | File mime type
     * @return $workOrder
     */
    public function createFileFromContents($jobId, $fileContent, $name, $mimeType, $fileName = null)
    {
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
        $serialNumber = $this->getSerialNumber();
        $createdBy = \Auth::id();
        $name = $fileName ? $fileName : $name;
        $workOrder = $this->repo->saveUploadedFile(
            $jobId,
            $name,
            $basePath,
            $mimeType,
            $fileSize,
            $serialNumber,
            $createdBy,
            $thumbBasePath
        );

        $eventData = [
			'reference_id' => $workOrder->id,
			'name' => $workOrder->id,
			'job_id' => $jobId,
			'parent_id' => null,
		];
        Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobWorkOrderStoreFile($eventData));

        //work order created event activity log
        Event::fire('JobProgress.WorkOrders.Events.WorkOrderCreated', new WorkOrderCreated($workOrder));
        return $workOrder;
    }

    /**
     * Rotate image
     * @param  Instance $workOrder Material List
     * @param  integer $rotateAngle Rotation Angle
     * @return Work Order
     */
    public function rotateImage($workOrder, $rotateAngle = 0)
    {
        $oldFilePath = $workOrder->file_path;
        $oldThumbPath = $workOrder->thumb;

        //rotate image
        $ext = File::extension($workOrder->file_path);
        $physicallyName = uniqueTimestamp() . '.' . $ext;
        $newFilePath = 'material_lists/' . $physicallyName;
        $this->rotate($workOrder->file_path, $newFilePath, $rotateAngle);

        //rotate thumb
        $newThumbPath = 'material_lists/thumb/' . $physicallyName;
        $this->rotate($workOrder->thumb, $newThumbPath, $rotateAngle);

        //update workorder with file path
        $workOrder->update([
            'file_path' => $newFilePath,
            'thumb' => $newThumbPath
        ]);

        if (!empty($oldFilePath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldFilePath);
        }

        if (!empty($oldThumbPath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldThumbPath);
        }

        return $workOrder;
    }

    /*************** PRIVATE FUNCTIONS ***************/

    /**
     * Get Serial Number
     * @param  String $type Type
     * @return Serial Number
     */
    private function getSerialNumber()
    {
        $serialNumber = $this->serialNoService->generateSerialNumber(SerialNumber::WORK_ORDER);

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
