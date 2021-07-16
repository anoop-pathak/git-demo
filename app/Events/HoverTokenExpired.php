<?php 

namespace App\Events;

use App\Models\HoverClient;

class HoverTokenExpired 
{
	/**
	 * Appointment Model
	 */
	public $googleClient;
 	function __construct($companyId)
	{
		$this->companyId = $companyId;
	}
} 