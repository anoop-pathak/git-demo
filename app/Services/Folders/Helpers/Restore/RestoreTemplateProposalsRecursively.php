<?php
namespace App\Services\Folders\Helpers\Restore;

use App\Services\Folders\Helpers\Restore\BaseRestoreRecursively;
use App\Models\Folder;
use App\Models\Template;

class RestoreTemplateProposalsRecursively extends BaseRestoreRecursively
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
        return (new Folder)->onlyTrashed()->where('is_auto_deleted', true);
    }

    public function getRefModel()
    {
        return (new Template)->where('type', Template::PROPOSAL)->onlyTrashed();
    }
}