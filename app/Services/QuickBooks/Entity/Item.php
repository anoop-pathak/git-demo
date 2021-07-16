<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use QuickBooksOnline\API\Facades\Item as QBOItem;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use QuickBooksOnline\API\Data\IPPItem;
use Exception;

class Item
{
    /**
     * Create Item In QB or JP
     */

    public function create($id = null, $meta, $location = QuickBooks::JP)
    {
        if($location == 'QB') {
            $qboItem = QBOItem::create($meta);
            $quickBookItem = QuickBooks::getDataService()->Add($qboItem);
            return $quickBookItem;
        }
    }

    public function update($id = null, IPPItem $existingEnity = null, $meta, $location = 'JP')
    {
        if($location == 'QB') {

            $item = null;

            if($existingEnity) {
                $item = $existingEnity;
            } else {
                $item = $this->get($id);
            }

            if($item) {
                $qboItem = QBOItem::update($item, $meta);

                $item = QuickBooks::getDataService()->Update($qboItem);
            }

            return $item;
        }
    }

    public function get($id)
    {
        return QuickBooks::findById('item', $id);
    }

    /**
	 * Get QuickBook Product For Updation
     * Here product is working as Item for JP
     *
	 * @param $id Quickbook Product Id
	 * @param $name Product Name
	 * @return Array
	 */
	public function getQuickbookProduct($id, $name)
	{
		$queryResponse = false;

		$entity = false;

		if($id) {
            $query = "SELECT *  FROM  Item WHERE Id = '".$id."'";

            $queryResponse = QuickBooks::getDataByQuery($query);
        }

		if(empty($queryResponse)) {
            $query = "SELECT *  FROM  Item WHERE Name = '".addslashes($name)."'";
            $queryResponse = QuickBooks::getDataByQuery($query);
        }

		if(!empty($queryResponse)
            && gettype($queryResponse) == 'array'
            && $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPItem) {
            $entity = $queryResponse[0];
        }

		return $entity;
    }

    /**
	 * Get QB Products
	 * @param $limit
	 * @param $page
	 * @return Products
	 */
	public function getProducts($limit, $filters = array())
	{

		if($limit > 1000) {
			$limit = 1000;
        }

        $page  = ine($filters, 'page') ? $filters['page'] : 1;

		try {

            $products = [];
			$totaRecords = 0;
			$query = "SELECT * FROM Item WHERE Active = true AND Type = 'Service'";

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

				$searchQuery = "SELECT count(*) FROM Item WHERE Active = true AND Type = 'Service'";

                if(ine($filters, 'Name')) {
					$searchQuery .= " And Name LIKE '{$name}%'";
				}

				if(ine($filters, 'Id')) {
					$searchQuery .=  " And Id = '{$filters['Id']}'";
				}

				$param = [
					'query' => $searchQuery,
					'minorversion'=>40
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
				'query' => $query,
				'minorversion'=>40
			];

            $queryResponse = QuickBooks::getDataByQuery($param['query']);

            if(!empty($queryResponse)) {
				$products = $queryResponse;
			}

			if($limit) {
				return QuickBooks::paginatedResponse($products, $totaRecords, $limit, $page);
            }

			$data['data'] = $products;

            return $data;
		} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
    }

    /**
	 * Create Or Update Local Product(Work Types) on QuickBook
	 * @param $workType Work Type Data
	 * @param $input    Input Data
	 * @return QB Product
	 */

	public function createOrUpdateProduct($workType, $qbAccountId)
	{

		try {

			$trade = $workType->trade;
            $productName = $trade->name .'/'. $workType->name;
			$qbId = $workType->qb_id;
			// Get Product from QuickBooks
			$data = $this->getQuickbookProduct($qbId, $productName);

			$productEntity = [
				'Name' => $productName,
				'IncomeAccountRef' => [
					'value'	 => $qbAccountId,
				],
				'Type' => 'Service'
			];

			// Update the Item
			if(!empty($data) && $data instanceof \QuickBooksOnline\API\Data\IPPItem) {
				$productEntity['Id'] = $data->Id;
				$productEntity['SyncToken'] = $data->SyncToken;
				$resultingProduct = $this->update(null, $data, $productEntity, 'QB');
			} else {
				$resultingProduct = $this->create(null, $productEntity, 'QB');
			}

			$workType->update([
				'qb_id' => $resultingProduct->Id
			]);

		}  catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * Find Or Create Item(Service)
	 * @param  Object  $token    Token
	 * @param  String  $itemName Item Name
	 * @return Array
	 */
	public function findOrCreateItem($itemName = 'Services')
	{
		$param = [
			'query' => "SELECT *  FROM item WHERE name = '".$itemName."'"
		];

		$queryResponse = QuickBooks::getDataByQuery($param['query']);

		if(!empty($queryResponse)
			&& gettype($queryResponse) == 'array'
			&& $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPItem) {

			$item = [
				'id'   => $queryResponse[0]->Id,
				'name' => $queryResponse[0]->Name
			];

		} else {
			$item = $this->createItem($itemName);
		}

		return $item;
	}


	/**
	 * Create Item
	 * @param  String $itemName Item Name
	 * @return Array
	 */
	private function createItem($itemName)
	{
		try {

			$itemEntity = [
				'Name' => $itemName,
				'IncomeAccountRef' => [
					'value' => QBAccount::getAccountId(),
				],
				'Type' => 'Service'
			];

			$item = \QuickBooksOnline\API\Facades\Item::create($itemEntity);

			$item = QuickBooks::getDataService()->Add($item);

			return [
				'id'   => $item->Id,
				'name' => $item->Name
			];

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}
}