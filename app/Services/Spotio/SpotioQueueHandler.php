<?php
namespace App\Services\Spotio;

use Log;
use App\Models\Resource;
use App\Services\Resources\ResourceServices;

class SpotioQueueHandler
{

    public function __construct(ResourceServices $resourceService)
    {
        $this->resourceService = $resourceService;
    }

    /**
    * save spotio docs in DB
    */
    public function saveDocumentInDB($spotioQueue, $data = [])
    {
        $resourceId = ine($data, 'resource_id') ? $data['resource_id'] : null;
        $content = ine($data, 'fileUrl') ? file_get_contents($data['fileUrl']) : null;
        $fileName = ine($data, 'fileName') ? $data['fileName'] : null;
        $mimeType = ine($data, 'mimeType') ? $data['mimeType'] : null;
        $meta = ine($data, 'meta') ? $data['meta'] : [];

        if(!$resourceId || !$content || !$fileName || !$mimeType || !$meta) return $spotioQueue->delete();

        // check already exists record
        $isExists = Resource::where('parent_id', $resourceId)->where('name', $fileName)->where('external_full_url', $data['fileUrl'])->first();
        if($isExists) return $spotioQueue->delete();

        // create record in DB
        $this->resourceService->createFileFromContents($resourceId, $content, $fileName, $mimeType, $meta);

        $spotioQueue->delete();
    }
}