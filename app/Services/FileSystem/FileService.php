<?php

namespace App\Services\FileSystem;

use App\Services\Resources\ResourceServices;
use PDF;
use FlySystem;
use Carbon\Carbon;
use App\Models\Resource;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\App;
use App\Exceptions\MaxFileSizeException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidURLException;

class FileService
{

    /**
     * Generate html to pdf
     * @param  html $html html content
     * @param  string $name name
     * @param  array $meta meta
     * @return pdf
     */
    public function generateHtmlToPdf($html, $name, $meta = [])
    {

        $pdf = PDF::loadHTML($html);
        $mode = issetRetrun($meta, 'mode') ?: 'portrait';
        $pdf->setOrientation($mode);
        $pdf->setOption('margin-left', 0);
        $pdf->setOption('margin-right', 0);

        if (ine($meta, 'paper-size')) {
            $pdf->setPaper($meta['paper-size']);
        }

        $pdf->setOption('dpi', 200);

        if (ine($meta, 'save_as_attachment')) {
            $file = $this->saveAsAttachment($pdf, $name);

            return ApiResponse::success([
                'file' => $file,
            ]);
        } elseif (ine($meta, 'download')) {
            return $pdf->download($name);
        }

        return $pdf->stream($name);
    }

    public function getDataFromUrl($url, $fileName = null)
    {
        $headers = @get_headers($url, 1);

        if ($headers) {
            $headers = array_change_key_case($headers, CASE_LOWER);
        }

        $headerStatus = strpos($headers[0], '200');

        if (!$headers || !ine($headers,'content-length') || !$headerStatus) {
            throw new InvalidURLException(trans('response.error.invalid', ['attribute' => 'File URL']));
        }

        $fileSizeInMB = byteToMB($headers['content-length']);
        $mimeType = $headers['content-type'];

        if (preg_match('/;/', $mimeType)) {
            $mimeType = substr($mimeType, 0, strpos($mimeType, ";"));
        }

        $validFiles = array_merge(config('resources.image_types'), config('resources.docs_types'));
        $maxSize = config('jp.max_file_size');

        if (!in_array($mimeType, $validFiles)) {
            throw new InvalidFileException(trans('This file mime type is not allowed.'));
        }

        if ($fileSizeInMB > $maxSize) {
            throw new MaxFileSizeException(trans('response.error.max_size', ['attribute' => 'File']));
        }

        $name = $fileName ? $fileName : baseName($url);

        $file = file_get_contents($url);
        $data['file']      = $file;
        $data['mime_type'] = $mimeType;
        $data['file_name'] = addExtIfMissing($name, $mimeType);

        return $data;
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
        $rootPath = config('resources.BASE_PATH') . $rootDir->path;
        $physicalName = Carbon::now()->timestamp . '_' . $name;
        $filePath = $rootPath . '/' . $physicalName;
        $mimeType = 'application/pdf';

        FlySystem::put($filePath, $pdfObject->output(), ['ContentType' => $mimeType]);

        $size = FlySystem::getSize($filePath);
        $resourcesRepo = App::make(\App\Repositories\ResourcesRepository::class);
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
