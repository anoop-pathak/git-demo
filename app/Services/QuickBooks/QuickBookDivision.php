<?php

namespace App\Services\QuickBooks;

use App\Exceptions\QuickBookException;
use App\Exceptions\StaleObjectException;
use App\Models\Division;
use App\Models\QuickBook;
use Exception;
use Illuminate\Support\Facades\Log;

class QuickBookDivision
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

    public function __construct(QuickBookService $service, Client $client)
    {
        $this->service = $service;
        $this->client = $client;
    }

    /**
     * Get QB Divisions
     * @param $token
     * @param $limit
     * @param $page
     *
     * @return Divisions
     *
     */
    public function getQBDivisions($token, $limit, $filters = [])
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

            $accounts = [];
            $totaRecords = 0;
            $query = "SELECT * FROM Department WHERE Active = true AND ParentRef = '0'";

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
                $searchQuery = "SELECT count(*) FROM Department WHERE Active = true AND ParentRef = '0'";

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
                $accounts = $queryResponse['Department'];
            }

            if ($limit) {
                return $this->service->paginatedResponse($accounts, $totaRecords, $limit, $page);
            }
            $data['data'] = $accounts;

            return $data;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * create QB Division
     * @param  $divisionName Division Name
     *
     * @return Division
     *
     */
    public function createOrUpdateDivision($token, $division)
    {
        try {
            if (!$token) {
                return false;
            }
            $this->client->setToken($token);

            $qbId = $division->qb_id;
            $divisionName = $division->name;

            $data = $this->getQuickbookDivision($qbId, $divisionName);

            $divisionEntity = [
                'Name' => $divisionName,
            ];

            if (ine($data, 'Id')) {
                $divisionEntity['Id'] = $data['Id'];
                $divisionEntity['SyncToken'] = $data['SyncToken'];
            }

            $response = $this->client->request("/department", 'POST', $divisionEntity);

            $division->update([
                'qb_id' => $response['Department']['Id']
            ]);
        } catch (\Exception $e) {
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
     * Get QuickBook Division For Updation
     * @param $token Token
     * @param $id    Quickbook Division Id
     * @param $name  Division Name
     *
     * @return QuickBook Division
     */
    private function getQuickbookDivision($id, $name)
    {
        $queryResponse = [];

        $entity = false;

        if ($id) {
            $param = [
                'query' => "SELECT Id, SyncToken  FROM  Department WHERE Id = '" . $id . "'"
            ];

            $queryResponse = $this->getDataByQuery($param);
        }

        if (empty($queryResponse)) {
            $param = [
                'query' => "SELECT Id, SyncToken  FROM  Department WHERE Name = '" . addslashes(removeQBSpecialChars($name)) . "'"
            ];
            $queryResponse = $this->getDataByQuery($param);
        }

        if (ine($queryResponse, 'Department')) {
            $entity['Id'] = $queryResponse['Department'][0]['Id'];
            $entity['SyncToken'] = $queryResponse['Department'][0]['SyncToken'];
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
                case 6240:
                    throw new QuickBookException("The division name is already exist on quickbook.");
                    break;

                case 4001:
                    throw new QuickBookException($response['Fault']['Error'][0]['Detail']);
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
                    break;
                case 620:
                    break;

                case 6210:
                case 6540:
                case 6190:
                    throw new QuickBookException("Quickbook Error: " . $response['Fault']['Error'][0]['Detail']);
                    break;

                default:
                    Log::error('QuickBook Division: ' . json_encode($response));
                    throw new QuickBookException(trans('response.error.something_wrong'));
                    break;
            }
        }

        Log::error('QuickBook Division: ' . json_encode($response));
        throw new QuickBookException('QuickBook: ' . trans('response.error.something_wrong'));
    }
}
