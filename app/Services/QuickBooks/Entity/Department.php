<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use QuickBooksOnline\API\Facades\Department as QBODepartment;
use App\Models\Division;
use Exception;
use Illuminate\Support\Facades\DB;

class Department
{
	private $repo = null;

	public function __construct()
	{
		$this->repo  = app()->make('App\Repositories\DivisionRepository');
	}

    public function create($id = null, $meta = null, $location = 'JP')
    {
        if($location == 'QB') {
            $qboItem = QBODepartment::create($meta);
            $quickBookItem = QuickBooks::getDataService()->Add($qboItem);
            return $quickBookItem;
		}

		$response = $this->get($id);

		if(!ine($response, 'entity')) {
			throw new Exception("Unable to fetch Department");
		}

		$department = QuickBooks::toArray($response['entity']);

		try {
			DB::beginTransaction();
			$this->repo->createOrUpdateDivision($department);
			DB::commit();
		} catch(Exception $e) {

			DB::rollback();
			QuickBooks::quickBookExceptionThrow($e);
		}
    }

    public function update($id = null, $existingEnity = null, $meta = [], $location = 'JP')
    {
        if($location == 'QB') {

            $item = null;

			if($existingEnity
				&& $existingEnity instanceof \QuickBooksOnline\API\Data\IPPDepartment
			) {
                $item = $existingEnity;
            }

            if($id) {
                $enity = $this->get($id);
                $item = $enity['entity'];
            }

            if($item) {
                $qboItem = QBODepartment::update($item, $meta);
                $item = QuickBooks::getDataService()->Update($qboItem);
            }

            return $item;
		}

		$response = $this->get($id);

		if(!ine($response, 'entity')) {
			throw new Exception("Unable to fetch Department");
		}

		$department = QuickBooks::toArray($response['entity']);

		try {
			DB::beginTransaction();
			$this->repo->createOrUpdateDivision($department);
			DB::commit();
		} catch(Exception $e) {

			DB::rollback();
			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	public function delete($id)
    {
        try {
			DB::beginTransaction();
			$this->repo->qbDeleteDivision($id);
			DB::commit();
		} catch(Exception $e) {

			DB::rollback();
			QuickBooks::quickBookExceptionThrow($e);
		}
    }

    public function get($id)
    {
        return QuickBooks::findById('department', $id);
    }

    /**
	 * Get QB Divisions
	 * @param $limit
	 * @param $page
	 * @return Divisions
	 */
	public function getDivisions($limit, $filters = array())
	{
		try {
			if($limit > 1000) {
				$limit = 1000;
            }

            $page  = ine($filters, 'page') ? $filters['page'] : 1;
            $accounts = [];
            $totaRecords = 0;
            $query = "SELECT * FROM Department WHERE Active = true AND ParentRef = '0'";
            $name = null;

            if(ine($filters, 'Name')) {
				$name = addslashes($filters['Name']);
				$query .=  " And Name LIKE '{$name}%'";
			}

			if(ine($filters, 'Id')) {
				$query .=  " And Id = '{$filters['Id']}'";
			}

			$query .= ' ORDERBY Id DESC';

			if($limit) {

				$searchQuery = "SELECT count(*) FROM Department WHERE Active = true AND ParentRef = '0'";

				if(ine($filters, 'Name')) {
					$searchQuery .=  " And Name LIKE '{$name}%'";
				}

				if(ine($filters, 'Id')) {
					$searchQuery .=  " And Id = '{$filters['Id']}'";
				}

				$param = [
					'query' => $searchQuery
				];

                $queryResponse = QuickBooks::getDataByQuery($param['query']);

                $totaRecords =  (int) $queryResponse;

				$start = 1;

                if($page > 1) {
					$start += ($page - 1) * $limit;
				}

                $query .= " STARTPOSITION " . $start ." MAXRESULTS ". $limit."";
			}

			$param = [
				'query' => $query
			];

            $queryResponse = QuickBooks::getDataByQuery($param['query']);

			if(!empty($queryResponse)) {
				$accounts = $queryResponse;
			}

			if($limit) {
				return QuickBooks::paginatedResponse($accounts, $totaRecords, $limit, $page);
            }

			$data['data'] = $accounts;

			return $data;
		} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * create or Update QB Division
	 * @param  $divisionName Division Name
	 * @return Division
	 */
	public function createOrUpdateDivision($division)
	{
		try {

            $qbId = $division->qb_id;
            $divisionName = $division->name;
            $data = $this->getQuickbookDivision($qbId, $divisionName);

			$divisionEntity = [
				'Name' => $divisionName,
			];

            if(!empty($data) && $data instanceof \QuickBooksOnline\API\Data\IPPDepartment) {
				$divisionEntity['Id'] = $data->Id;
				$divisionEntity['SyncToken'] = $data->SyncToken;
				$resultingProduct = $this->update(null, $data, $divisionEntity, 'QB');
			} else {
				$resultingProduct = $this->create(null, $divisionEntity, 'QB');
			}

			$division->update([
				'qb_id' => $resultingProduct->Id
            ]);
		} catch(Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
    }

    /**
	 * Get QuickBook Division For Updation
	 * @param $id    Quickbook Division Id
	 * @param $name  Division Name
	 *
	 * @return QuickBook Division
	 */
	private function getQuickbookDivision($id, $name)
	{
        $queryResponse = [];

		$entity = false;

		if($id) {
			$param = [
				'query' => "SELECT Id, SyncToken  FROM  Department WHERE Id = '".$id."'"
			];

			$queryResponse = QuickBooks::getDataByQuery($param['query']);
		}

		if(empty($queryResponse)) {
			$param = [
				'query' => "SELECT Id, SyncToken  FROM  Department WHERE Name = '".addslashes(removeQBSpecialChars($name))."'"
			];
			$queryResponse = QuickBooks::getDataByQuery($param['query']);
		}

		if(!empty($queryResponse)
            && gettype($queryResponse) == 'array'
            && $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPDepartment) {
            $entity = $queryResponse[0];
        }

		return $entity;
	}

}