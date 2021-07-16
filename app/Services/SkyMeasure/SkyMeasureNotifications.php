<?php namespace App\Services\SkyMeasure;

use App\Models\SMClient;
use App\Models\SMOrder;
use App\Models\SMReportFile;
use FlySystem;
use Illuminate\Support\Facades\File;

class SkyMeasureNotifications
{

    protected $service;

    function __construct()
    {
        $this->service = new SkyMeasure;
    }

    public function handle(SMOrder $order, $statusCode)
    {

        $client = SMClient::whereCompanyId($order->company_id)->first();
        if (!$client) {
            return false;
        }

        $this->token = $client->token;

        $statuses = config('skymeasure.status');

        if (!isset($statuses[$statusCode])) {
            return false;
        }

        // // same status..
        // if($order->status == $statuses[$statusCode]) return false;

        if (($statusCode == SMOrder::CODE_COMPLETED)
            || ($statusCode == SMOrder::CODE_REFUNDED)
            || ($statusCode == SMOrder::CODE_PARTIAL_REFUNDED)) {
            $this->recieveReportFiles($order);
        }

        $order->status = $statuses[$statusCode];
        $order->save();

        return true;
    }

    private function recieveReportFiles($order)
    {
        $existingFileIds = $order->reportsFiles()->pluck('file_id')->toArray();
        $files = $this->service->getReportFilesList($this->token, $order->order_id);
        foreach ($files as $file) {
            if (isset($file['ID']) && in_array($file['ID'], $existingFileIds)) {
                $order->reportsFiles()->where('file_id', $file['ID'])->delete();
            }

            $this->saveFile($order, $file);
        }
    }

    private function saveFile($order, $file)
    {
        $fileId = $file['ID'];
        $fileName = $file['FileName'];
        $orderId = $order->order_id;
        $fileContent = $this->service->getReportFile($this->token, $orderId, $file['ID']);

        //Write data to file
        $basePath = 'sm_reports/' . $fileName;

        $fullPath = config('jp.BASE_PATH') . $basePath;

        $ext = File::extension($fileName);

        $fileMimeType = getMimeTypeFromExt($ext);

        FlySystem::put($fullPath, $fileContent, ['ContentType' => $fileMimeType]);

        $fileSize = FlySystem::getSize($fullPath);

        $report = SMReportFile::create([
            'order_id' => $orderId,
            'file_id' => $fileId,
            'name' => $fileName,
            'path' => $basePath,
            'size' => $fileSize,
            'mime_type' => $fileMimeType,
        ]);

        // save measurement..
        $pdfReportName = $orderId . '.pdf';
        if (($fileName == $pdfReportName) && ($measurement = $order->measurement)) {
            $measurement->is_file = true;
            $measurement->file_name = $fileName;
            $measurement->file_path = $basePath;
            $measurement->file_size = $fileSize;
            $measurement->file_mime_type = $fileMimeType;
            $measurement->save();
        }

        return true;
    }
}
