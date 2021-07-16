<?php

namespace App\Services\Appointments;

use App\Repositories\AppointmentResultOptionRepository;
use App\Models\Appointment;

class AppointmentResultOptionService
{
	public function __construct(AppointmentResultOptionRepository $repo)
	{
		$this->repo = $repo;
	}
 	/**
	* update appointment result 
	*
	* @param $result
	* @param $fields
	*
	* @return result
	*/
	public function saveOrUpdate($name, $fields, $resultOption = null)
	{
		return  $this->repo->saveOrUpdate($name, $fields, $resultOption);
	}
 	/**
	* count appointments by result option id
	*/
	public function appointmentCount($result)
	{
		return $result->appointments()->count();
	}
} 
