<?php

namespace App\Services\QuickBooks;

use App\Exceptions\QuickBookException;
use App\Exceptions\StaleObjectException;
use App\Models\Product;
use App\Models\QuickBook;
use App\Services\Contexts\Context;
use Exception;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Item as QBItem;
use QuickBooksOnline\API\Data\IPPItem;

class QuickBookProducts
{

    /**
     * $service quickbook Service
     * @var instance
     */
    protected $service;


    /**
     * $client quickbook Client
     * @var instance
     */
    protected $client;

    public function __construct(QuickBookService $service, Context $scope, Client $client)
    {
        $this->service = $service;
        $this->client = $client;
        $this->scope = $scope;
    }

    /**
     * Get QB Products
     * @param $token
     * @param $limit
     * @param $page
     *
     * @return Products
     *
     */
    public function getQBProducts($token, $limit, $filters = [])
    {

        if ($limit > 1000) {
            $limit = 1000;
        }
        $page = ine($filters, 'page') ? $filters['page'] : 1;

        try {
            if (!$token) {
                return false;
            }
            $this->client->setToken($token);

            $products = [];
            $totaRecords = 0;
            $query = "SELECT Id, Name FROM Item WHERE Active = true AND Type = 'Service'";

            $name = null;
            if (ine($filters, 'Name')) {
                $name = addslashes($filters['Name']);
                $query .= " And Name LIKE '{$name}%'";
            }

            if (ine($filters, 'Id')) {
                $query .= " And Id = '{$filters['Id']}'";
            }

            $query .= ' ORDERBY Id DESC';

            if ($limit) {
                $searchQuery = "SELECT count(*) FROM Item WHERE Active = true AND Type = 'Service'";
                if (ine($filters, 'Name')) {
                    $searchQuery .= " And Name LIKE '{$name}%'";
                }

                if (ine($filters, 'Id')) {
                    $searchQuery .= " And Id = '{$filters['Id']}'";
                }

                $param = [
                    'query' => $searchQuery,
                    'minorversion' => 40
                ];

                $queryResponse = $this->getDataByQuery($param);
                $totaRecords = $queryResponse['totalCount'];
                $start = 1;
                if ($page > 1) {
                    $start += ($page - 1) * $limit;
                }
                $query .= " STARTPOSITION " . $start . " MAXRESULTS " . $limit . "";
            }

            $param = [
                'query' => $query,
                'minorversion' => 40
            ];

            $queryResponse = $this->getDataByQuery($param);
            if (!empty($queryResponse)) {
                $products = $queryResponse['Item'];
            }

            if ($limit) {
                return $this->service->paginatedResponse($products, $totaRecords, $limit, $page);
            }
            $data['data'] = $products;

            return $data;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Get QB Accounts
     * @param $token
     * @param $limit
     * @param $page
     *
     * @return Accounts
     *
     */
    public function getQBAccounts($token, $limit, $filters = [])
    {
        try {
            if (!$token) {
                return false;
            }

            if ($limit > 1000) {
                $limit = 1000;
            }

            $page = ine($filters, 'page') ? $filters['page'] : 1;

            $this->client->setToken($token);

            $products = [];
            $totaRecords = 0;
            $query = "SELECT Name, AccountType, id, SubAccount, ParentRef FROM Account WHERE Active = true";

            $name = null;
            if (ine($filters, 'Name')) {
                $name = addslashes($filters['Name']);
                $query .= " And Name LIKE '{$name}%'";
            }

            if (ine($filters, 'Id')) {
                $query .= " And Id = '{$filters['Id']}'";
            }

            $query .= ' ORDERBY Id DESC';

            if ($limit) {
                $searchQuery = "SELECT count(*) FROM Account WHERE Active = true";

                if (ine($filters, 'Name')) {
                    $searchQuery .= " And Name LIKE '{$name}%'";
                }

                if (ine($filters, 'Id')) {
                    $searchQuery .= " And Id = '{$filters['Id']}'";
                }

                $param = [
                    'query' => $searchQuery
                ];
                $queryResponse = $this->getDataByQuery($param);
                $totaRecords = $queryResponse['totalCount'];
                $start = 1;
                if ($page > 1) {
                    $start += ($page - 1) * $limit;
                }
                $query .= " STARTPOSITION " . $start . " MAXRESULTS " . $limit . "";
            }

            $param = [
                'query' => $query
            ];

            $queryResponse = $this->getDataByQuery($param);
            if (!empty($queryResponse)) {
                $products = $queryResponse['Account'];
            }

            if ($limit) {
                return $this->service->paginatedResponse($products, $totaRecords, $limit, $page);
            }
            $data['data'] = $products;

            return $data;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Create Or Update Local Product(Work Types) on QuickBook
     * @param $token    Token
     * @param $workType Work Type Data
     * @param $input    Input Data
     *
     * @return QB Product
     */
    public function createOrUpdateProduct($token, $workType, $qbAccountId)
    {

        try {
            if (!$token) {
                return false;
            }
            $this->client->setToken($token);

            $trade = $workType->trade;
            $productName = $trade->name . '/' . $workType->name;
            $qbId = $workType->qb_id;
            // Get Product from QuickBooks
            $data = QBItem::getQuickbookProduct($qbId, $productName);

            $productEntity = [
                'Name' => $productName,
                'IncomeAccountRef' => [
                    'value' => $qbAccountId,
                ],
                'Type' => 'Service'
            ];

            // Update the Item
			if(!empty($data) && $data instanceof IPPItem) {
				$productEntity['Id'] = $data->Id;
				$productEntity['SyncToken'] = $data->SyncToken;
				$resultingProduct = QBItem::update(null, $data, $productEntity, 'QB');
			} else {
				$resultingProduct = QBItem::create(null, $productEntity, 'QB');
			}

            $workType->update([
                'qb_id' => $resultingProduct->Id
            ]);
        } catch (Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /*************    Private Method ******************/

    /**
     * Get Data By Query
     * @param  Array $param [Query]
     * @return Data
     */
    private function getDataByQuery($param)
    {
        $item = $this->client->request("/query", 'GET', $param);
        if (empty($item['QueryResponse'])) {
            return false;
        }

        return $item['QueryResponse'];
    }

    /**
     * Get QuickBook Product For Updation
     * @param $token Token
     * @param $id    Quickbook Product Id
     * @param $name  Product Name
     *
     * @return QuickBook Product
     */
    private function getQuickbookProducts($id, $name)
    {
        $queryResponse = [];

        $entity = false;

        if ($id) {
            $param = [
                'query' => "SELECT Id, SyncToken  FROM  Item WHERE Id = '" . $id . "'"
            ];

            $queryResponse = $this->getDataByQuery($param);
        }

        if (empty($queryResponse)) {
            $param = [
                'query' => "SELECT Id, SyncToken  FROM  Item WHERE Name = '" . addslashes(removeQBSpecialChars($name)) . "'"
            ];
            $queryResponse = $this->getDataByQuery($param);
        }

        if (ine($queryResponse, 'Item')) {
            $entity['Id'] = $queryResponse['Item'][0]['Id'];
            $entity['SyncToken'] = $queryResponse['Item'][0]['SyncToken'];
        }

        return $entity;
    }

    /**
     * Handle Quickbook Exception
     * @param  Exception $e Exception
     * @return error throw
     */
    private function quickBookExceptionThrow(Exception $e)
    {
        switch ($e->getCode()) {
            case 401:
                throw new AuthorizationException(trans('response.error.quickbook_unauthorized'));
                break;
            case 500:
                throw new QuickBookException('QuickBook: An internal error occured.');
                break;
        }

        if (!method_exists($e, 'getResponse')) {
            throw $e;
        }

        $quickBookErrorResponse = $e->getResponse()->getBody()->getContents();
        $response = json_decode($quickBookErrorResponse, true);

        if (isset($response['Fault']['Error'][0]['code']) && $response['Fault']['Error'][0]['code'] == 5010) {
            throw new StaleObjectException('stale object error');
        }

        if (isset($response['Fault']['Error'][0]['Message'])) {
            $error = $response['Fault']['Error'][0];
            $errorMessage = $error['Message'];
            if (array_key_exists('Detail', $error)) {
                $errorMessage = $error['Detail'];
            }

            switch ((int)$response['Fault']['Error'][0]['code']) {
                case 2500:
                    throw new QuickBookException("Invalid account id");
                    break;

                case 400:
                    throw new QuickBookException("The work type name is already exist on quickbook.");
                    break;

                case 6002:
                    throw new QuickBookException('Internet connection slow.');
                    break;

                //deprecated field
                case 6002:
                    break;

                case 6000:
                    throw new QuickBookException('An unexpected error occurred on quickbook. Please wait a few minutes and try again.');
                    break;

                case 6190:
                    throw new QuickBookException('Quickbook subscription has ended or canceled.');
                    break;

                //batch size limit
                case 1040:
                    throw new QuickBookException('Batch size limit exceed.');
                    break;

                case 620:
                    break;

                case 6210:
                case 6540:
                case 6190:
                    throw new QuickBookException("Quickbook Error: " . $response['Fault']['Error'][0]['Detail']);
                    break;

                default:
                    Log::error('QuickBook Product: ' . json_encode($response));
                    throw new QuickBookException(trans('response.error.something_wrong'));
                    break;
            }
        }

        Log::error('QuickBook Product: ' . json_encode($response));
        throw new QuickBookException('QuickBook Product: ' . trans('response.error.something_wrong'));
    }
}
