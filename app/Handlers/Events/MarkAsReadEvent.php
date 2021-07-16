<?php 

namespace App\Handlers\Events;

class MarkAsReadEvent{
 	/**
	 * Job Model
	 */
	public $userId;
 	public function __construct($userId) {
		$this->userId = $userId;
	}
} 