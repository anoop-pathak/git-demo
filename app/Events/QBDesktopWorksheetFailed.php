<?php 
namespace App\Events;

class QBDesktopWorksheetFailed 
{
	/**
	 * Appointment Model
	 */
	public $worksheetId;
	public function __construct($worksheetId, $queueId)
	{
		$this->worksheetId = $worksheetId;
		$this->queueId = $queueId;
	}
} 