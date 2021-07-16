<?php

namespace App\Services\Pdf;

use PDF;
use FlySystem;
use Carbon\Carbon;
use App\Models\Resource;
use Illuminate\Support\Facades\App;
use App\Repositories\ResourcesRepository;
use App\Services\Resources\ResourceServices;

class PdfService
{

    public $attachment;
    public $pdf;

    /**
     * Get Attachement File Path
     * @return Attachement
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Get Pdf File
     * @return Pdf File
     */
    public function getPdf()
    {
        return $this->pdf;
    }

    /**
     * Create Pdf
     * @param  Array $contents array of contents
     * @param  String $name Pdf File Name
     * @param  Array $meta
     * @return Boolean
     */
    public function create($contents, $name, $meta = [])
    {
        try {
            $mode = ine($meta, 'mode') ? $meta['mode'] : 'portrait';
            $name .= '.pdf';

            $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation($mode);
            $pdf->setOption('dpi', 200);

            if (!ine($meta, 'save_as_attachment')) {
                $this->pdf = $pdf->stream($name);
            }
        } catch (\Exception $e) {
            $this->pdf = \view('error-page', [
                'errorDetail' => getErrorDetail($e),
                'message' => trans('response.error.error_page'),
            ]);
        }


        if (ine($meta, 'save_as_attachment')) {
            $this->attachment = $this->saveAsAttachment($pdf, $name);
        }

        return true;
    }

    /**** PRIVATE METHOD **********/

    /**
     * Save as attachment
     * @param  Object $pdfObject Pdf
     * @return Response
     */
    private function saveAsAttachment($pdfObject, $name)
    {
        $rootDir = $this->getRootDir();
        $rootPath = \config('resources.BASE_PATH') . $rootDir->path;
        $physicalName = Carbon::now()->timestamp . '_' . $name;
        $filePath = $rootPath . '/' . $physicalName;
        $mimeType = 'application/pdf';
        // save pdf

        FlySystem::put($filePath, $pdfObject->output(), ['ContentType' => $mimeType]);

        $size = FlySystem::getSize($filePath);
        $resourcesRepo = App::make(ResourcesRepository::class);
        $resource = $resourcesRepo->createFile($name, $rootDir, $mimeType, $size, $physicalName);

        return $resource;
    }

    /**
     * Get Root Dir
     * @return Parent Dir
     */
    private function getRootDir()
    {
        $parentDir = Resource::name(Resource::EMAIL_ATTACHMENTS)
            ->company(getScopeId())
            ->first();

        if (!$parentDir) {
            $resourceService = App::make(ResourceServices::class);
            $root = Resource::companyRoot($this->scope->id());
            $parentDir = $resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id);
        }
        return $parentDir;
    }
}
