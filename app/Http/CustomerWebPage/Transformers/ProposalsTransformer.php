<?php

namespace App\Http\CustomerWebPage\Transformers;

use App\Models\Proposal;
use FlySystem;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use App\Http\CustomerWebPage\Transformers\JobsTransformer;
use App\Http\CustomerWebPage\Transformers\UsersTransformer;
use App\Http\CustomerWebPage\Transformers\CustomersTransformer;
use League\Fractal\TransformerAbstract;

class ProposalsTransformer extends TransformerAbstract
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
    public function transform($proposal)
    {

        $data = [
            'id' => $proposal->id,
            'title' => $proposal->title,
            'token' => $proposal->token,
            'job_id' => $proposal->job_id,
            'is_file' => $proposal->is_file,
            'file_name' => str_replace(' ', '_', $proposal->file_name),
            'file_path' => $proposal->getFilePath(),
            'file_mime_type' => $proposal->file_mime_type,
            'status' => $proposal->status,
            'type' => $proposal->type,
        ];

        return $data;
    }
    
}
