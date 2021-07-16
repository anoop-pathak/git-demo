<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class GoogleDriveFilesTransformer extends TransformerAbstract
{

    public function transform($file)
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'mime_type' => $file->mimeType,
            'size' => $file->size,
            'thumb' => $file->thumbnailLink,
            'icon_link' => $file->iconLink,
            'web_view_link' => $file->webViewLink,
            'web_content_link' => ($file->webContentLink) ?: googleDocsExportLink($file->mimeType, $file->id),
        ];
    }
}
