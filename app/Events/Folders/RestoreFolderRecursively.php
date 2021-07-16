<?php
namespace App\Events\Folders;

class RestoreFolderRecursively {

    public $id;

	public function __construct($id) {
		$this->id = $id;
	}
}