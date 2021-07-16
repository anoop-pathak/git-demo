<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use FlySystem;

class EagleViewReportsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($report)
    {
        return [
            'id' => $report->id,
            'file_type' => isset($report->fileType->name) ? $report->fileType->name : null,
            'file_name' => $report->file_name,
            'file_mime_type' => $report->file_mime_type,
            'file_size' => $report->file_size,
            'file_url'      => FlySystem::publicUrl(config('jp.BASE_PATH').$report->file_path),
            'created_at'    => $report->created_at,
            'updated_at'    => $report->updated_at,
        ];
    }
}
