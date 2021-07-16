<?php
namespace App\Events\Folders;

class DeleteFolderRecursively {

    public $id;

	public function __construct($id) {
		$this->id = $id;
	}
}