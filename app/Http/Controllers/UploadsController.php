<?php

namespace App\Http\Controllers;

use App\Exceptions\FileNotFoundException;
use App\Exceptions\InvalidResourcePathException;
use App\Models\ApiResponse;
use App\Models\JobCredit;
use App\Models\JobInvoice;
use App\Models\Resource;
use App\Models\Worksheet;
use App\Repositories\EstimationsRepository;
use App\Repositories\MaterialListRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\ResourcesRepository;
use App\Services\Contexts\Context;
use FlySystem;
use App\Services\Resources\ResourceServices;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use App\Repositories\WorkOrderRepository;
use App\Repositories\MeasurementRepository;

class UploadsController extends ApiController
{

    /**
     * App\Resources\ResourceServices;
     */
    protected $resourceService;
    protected $scope;

    public function __construct(
        ResourceServices $resourceService,
        Context $scope,
        ResourcesRepository $resourcesRepo,
        ProposalsRepository $proposalsRepo,
        EstimationsRepository $estimateRepo,
        MaterialListRepository $materialListRepo,
        WorkOrderRepository $workOrderRepo,
        MeasurementRepository $measurementRepo
    ) {

        $this->resourceService = $resourceService;
        $this->resourcesRepo = $resourcesRepo;
        $this->proposalsRepo = $proposalsRepo;
        $this->estimateRepo = $estimateRepo;
        $this->materialListRepo = $materialListRepo;
        $this->workOrderRepo = $workOrderRepo;
        $this->measurementRepo = $measurementRepo;

        parent::__construct();
        $this->scope = $scope;
    }

    public function upload_file()
    {
        $input = Request::onlyLegacy('file', 'get_url');
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules = ['file' => 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            if ($input['get_url']) {
                return $this->uploadAndGenerateUrl($input['file']);
            }

            $rootId = $this->getRootId();
            $file = $this->resourceService->uploadFile(
                $rootId,
                $input['file']
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.file_uploaded'),
                'file' => $file
            ]);
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }
    }

    public function get_attachment_url()
    {
        $input = Request::onlyLegacy('type', 'file_id');

        return $this->getAttachmentUrlForExistingFiles($input['type'], $input['file_id']);
    }

    public function delete_file($id)
    {
        try {
            $this->resourceService->removeFile($id);
            return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'File'])]);
        } catch (FileNotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        }
    }

    /******************** Private section ********************/

    private function getRootId()
    {
        $parentDir = Resource::name(Resource::EMAIL_ATTACHMENTS)->company($this->scope->id())->first();
        if (!$parentDir) {
            $root = Resource::companyRoot($this->scope->id());
            $parentDir = $this->resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id);
        }
        return $parentDir->id;
    }

    private function uploadAndGenerateUrl($file)
    {
        $companyId = getScopeId();
        $fileName = generateUniqueToken() . '_' . $file->getClientOriginalName();
        $fullPath = "{$companyId}/$fileName";
        // $fullPath = config('jp.BASE_PATH').$physicalPath;
        FlySystem::connection('s3_attachments')->writeStream($fullPath, $file, ['ACL' => 'public-read']);

        $url = FlySystem::connection('s3_attachments')->getUrl($fullPath);

        return ApiResponse::success(['url' => $url]);
    }

    private function getAttachmentUrlForExistingFiles($type, $id)
    {
        switch ($type) {
            case 'resource':
            case 'upload':
                $file = $this->resourcesRepo->getFile($id);
                $filePath = config('resources.BASE_PATH') . $file->path;
                $name = $file->name;
                $mimeType = $file->mime_type;
                break;

            case 'proposal':
                $file = $this->proposalsRepo->getById($id);
                $filePath = $file->getFilePathWithoutUrl();
                $name = $file->title;
                $mimeType = $file->file_mime_type;
                break;

            case 'estimate':
                $file = $this->estimateRepo->getById($id);
                $filePath = config('jp.BASE_PATH') . $file->file_path;
                $name = $file->title;
                $mimeType = $file->file_mime_type;
                break;

            case 'material_list':
                $file = $this->materialListRepo->getById($id);
                $filePath = config('jp.BASE_PATH') . $file->file_path;
                $name = $file->title;
                $mimeType = $file->file_mime_type;
                break;

            case 'workorder':
                $file = $this->workOrderRepo->getById($id);
                $filePath = config('jp.BASE_PATH') . $file->file_path;
                $name = $file->title;
                $mimeType = $file->file_mime_type;
                break;

            case 'invoice':
                $invoice = JobInvoice::find($id);

                $filePath = config('jp.BASE_PATH') . $invoice->file_path;
                if (!$invoice->file_size) {
                    $filePath = 'public/' . $invoice->file_path;
                }

                $name = $invoice->title;
                $mimeType = 'application/pdf';
                break;

            case 'worksheet':
                $worksheet = Worksheet::find($id);
                $filePath = config('jp.BASE_PATH') . $worksheet->file_path;
                $name = $worksheet->type;
                $mimeType = 'application/pdf';
                break;

            case 'credit':
                $jobCredit = JobCredit::find($id);
                $filePath = config('jp.BASE_PATH') . $jobCredit->file_path;
                $name = 'credit_' . basename($jobCredit->file_path);
                $mimeType = 'application/pdf';
                break;

            case 'measurement':
                $file = $this->measurementRepo->getById($id);
                $filePath = config('jp.BASE_PATH') . $file->file_path;  
                $name = $file->title;
                $mimeType = $file->file_mime_type;
                break;

            default:
                return ApiResponse::errorGeneral('Invalid file type.');
        }

        $companyId = getScopeId();
        $fileName = generateUniqueToken() . '_' . basename($filePath);
        $destination = "{$companyId}/$fileName";

        $config['ACL'] = 'public-read';

        FlySystem::setSecondConnection('s3_attachments')->copy($filePath, $destination, $config);

        $url = FlySystem::connection('s3_attachments')->getUrl($destination);

        return ApiResponse::success(['url' => $url]);
    }
}
