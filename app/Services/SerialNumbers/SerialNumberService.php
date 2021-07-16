<?php

namespace App\Services\SerialNumbers;

use App\Models\Estimation;
use App\Models\MaterialList;
use App\Models\Proposal;
use App\Models\SerialNumber;
use App\Repositories\SerialNumberRepository;
use Illuminate\Support\Facades\DB;
use App\Models\Job;

class SerialNumberService
{
    public function __construct(SerialNumberRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Save Serial Number
     * @param  String $type Serial Number Type
     * @param  Int $startFrom Starting Number of serial number
     * @param  array $meta Meta Info
     * @return Serial Number
     */
    public function save($type, $startFrom, $prefix = null)
    {
        $lastRecordId = $this->getLastRecordId($type);

        $serialNo = $this->repo->save($type, $startFrom, $active = true, $lastRecordId, $prefix);

        return $serialNo;
    }

    /**
     * Get Serial number
     * @param  String $type Type
     * @return Serial Number (Instance)
     */
    public function getByType($type)
    {
        return $this->repo->getByType($type);
    }

    public function generateNewSerialNumber($types = [])
	{
		$data = [];
		foreach ($types as $type) {
			$data[$type] = $this->issueNewNumber($type);
		}
		return $data;
    }

    public function issueNewNumber($type)
	{
		$serialNumber  = $this->repo->getByType($type);
        if(!$serialNumber) {
            return "";
        }

        if(($type == 'job_lead_number' || $type == 'job_alt_id')
			&& !($serialNumber->current_allocated_number)
		){
			return "";
		}
		$locked = DB::select("SELECT `current_allocated_number` FROM `serial_numbers` WHERE `id` = {$serialNumber->id} limit 0, 1 FOR UPDATE;");
		DB::update("update `serial_numbers` set `current_allocated_number` = `current_allocated_number` + 1  WHERE `id` = {$serialNumber->id};");

		$count = $locked[0]->current_allocated_number + 1;
		if($serialNumber->prefix) {
			$count = $serialNumber->prefix .'-'. $count;
		}
		return $count;
	}

    /**
     * Get Current serial number
     * @param  String $type type of serial number
     * @return Current serial number (INT)
     */
    public function getCurrentSerialNumber($type)
    {
        $serialNumber = $this->repo->getByType($type);
        if (!$serialNumber) {
            $count = $this->getCount($type);
        }else {
            $count = $this->getCount($type, $serialNumber->last_record_id);
            $count = $count + $serialNumber->start_from;
        }

        if($type == SerialNumber::MATERIAL_LIST) {
            $count = getScopeId()."-$count";
        }

        if($type == SerialNumber::MATERIAL_LIST && $serialNumber) {
            $companyScope = getScopeId()."-$count";
			if($serialNumber->prefix) {
                $count .= "$companyScope-{$serialNumber->prefix}-{$count}";
            } else {
                $count .= "$companyScope-{$count}";
            }
        } else {
			if($serialNumber && $serialNumber->prefix) {
				$count = $serialNumber->prefix .'-'.$count;
            }
        }

        return $count;
    }

    /**
     * Get Generate serial number for proposal,estimate and worksheet.
     * @param  String $type Serial number type
     * @return serial number(int)
     */
    public function generateSerialNumber($type)
    {
        $serialNumber = $this->repo->getByType($type);

        if (!$serialNumber) {
            $count = $this->getCount($type) + 1;
        } else {
            $count = $this->getCount($type, $serialNumber->last_record_id);
            $count = $serialNumber->start_from + $count + 1;
        }

        if($type == SerialNumber::MATERIAL_LIST && $serialNumber) {
            $companyScope = getScopeId()."-$count";
			if($serialNumber->prefix) {
				$count .= "$companyScope-{$serialNumber->prefix}-{$count}";
			} else {
				$count .= "$companyScope-{$count}";
			}
		} else {
			if($serialNumber && $serialNumber->prefix) {
				$count = $serialNumber->prefix .'-'.$count;
            }
        }

        return $count;
    }

    /**
     * Get All Serial Numbes
     * @return Get All Serial Numbers
     */
    public function getAllSerialNumbers()
    {
        return $this->repo->getAllSerialNumbers();
    }

    /**
     * Get Last Record Id
     * @param  String $type Proposal|Estimate|Material List
     * @return Int Id
     */
    private function getLastRecordId($type)
    {
        if ($type == 'proposal') {
            $object = Proposal::whereCompanyId(getScopeId())->latest()->first();
        } elseif ($type == 'estimate') {
            $object = Estimation::whereCompanyId(getScopeId())->latest()->first();
        } elseif ($type == 'material_list') {
            $object = MaterialList::whereCompanyId(getScopeId())->whereType(MaterialList::MATERIAL_LIST)->latest()->first();
        } elseif ($type == 'work_order') {
            $object = MaterialList::whereCompanyId(getScopeId())->whereType(MaterialList::WORK_ORDER)->latest()->first();
        } elseif (in_array($type, [SerialNumber::JOB_ALT_ID, SerialNumber::JOB_LEAD_NUMBER])) {
            $object = Job::whereCompanyId(getScopeId())->latest()->first();
        }

        if (!$object) {
            return false;
        }

        return $object->id;
    }

    /**
     * Get Count by type
     * @param  String $type Proposal|Estimate|Material List
     * @param  integer $id Integer
     * @return Int Count
     */
    public function getCount($type, $id = 0)
    {
        $count = 0;
        if ($type == 'proposal') {
            $count = Proposal::where('id', '>', $id)->whereCompanyId(getScopeId())->count();
        } elseif ($type == 'estimate') {
            $count = Estimation::where('id', '>', $id)->whereCompanyId(getScopeId())->count();
        } elseif ($type == 'material_list') {
            $count = MaterialList::where('id', '>', $id)->whereCompanyId(getScopeId())->whereType(MaterialList::MATERIAL_LIST)->count();
        } elseif ($type == 'work_order') {
            $count = MaterialList::where('id', '>', $id)->whereCompanyId(getScopeId())->whereType(MaterialList::WORK_ORDER)->count();
        }

        return $count;
    }
}
