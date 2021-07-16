<?php
namespace App\Services\Folders\Helpers\Delete;

use App\Models\Folder;
use App\Models\Template;
use App\Services\Folders\Helpers\Delete\BaseDeleteRecursively;

class DeleteTemplateEstimatesRecursively extends BaseDeleteRecursively
{
    protected $id;
    protected $childrenFolderIds = [];
    protected $childrenReferenceIds = []; // referene ids means templates tables id.

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getModel()
    {
        return new Folder;
    }

    public function getRefModel()
    {
        return (new Template)->where('type', Template::ESTIMATE);
    }
}