<?php

namespace App\Services\QuickBooks;

use App\Exceptions\QuickBookException;
use App\Exceptions\StaleObjectException;
use App\Models\Customer;
use App\Models\Division;
use App\Models\InvoicePayment;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Models\JobType;
use App\Models\QuickBook;
use App\Repositories\CustomerRepository;
use App\Repositories\QuickBookRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookPayments\Objects\AccessToken;
use FlySystem;
use Settings;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBookPayments\Clients\OAuth2Client;

class QuickBookService
{
    /**
     * $repo quickbook Repository
     * @var instance
     */
    protected $repo;

    /**
     * $customerRepo Customer Repository
     * @var [instance]
     */
    protected $jobRepo;

    public function __construct(
        QuickBookRepository $repo,
        CustomerRepository $customerRepo,
        Client $client
    ) {

        $this->repo = $repo;
        $this->customerRepo = $customerRepo;
        $this->client = $client;
    }

    public function getAuthorizationUrl($withPaymentsScope = false) {
        return $this->client->getAuthorizationUrl($withPaymentsScope);
    }

    /**
     * @method  disconnect
     * @return Boolean
     */
    public function accountDisconnect()
    {
        $qbDetail = QuickBook::whereCompanyId(getScopeId())->firstOrFail();
		$this->repo->deleteToken();

		$oauth2Client = app(OAuth2Client::class);

		return $oauth2Client->revokeAccessToken($qbDetail->access_token);
    }

    /**
     * Check Account Has Connected
     * @param $returnToken Pass true for returning the token as well and not a boolean value
     * @return \QuickBook instance
     * @return boolean
     */
    public function isConnected($returnToken = FALSE)
    {
        $token = $this->repo->getToken();

        if(!$token) {
            return false;
        }

        if($token->isRefreshTokenExpired()) {
            return false;
        }

        if($returnToken) {
            return $token;
        }

        return true;
    }

    /**
     * Check Account Has Connected
     * @param $returnToken Pass true for returning the token as well and not a boolean value
     * @return \QuickBook instance
     * @return boolean
     */
    public function isPaymentsConnected()
    {
        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))){
			return false;
        }

        $token = $this->repo->getToken();
        if (!$token) {
            return false;
        }

        if($token->isRefreshTokenExpired()) {
            return false;
        }

        return $token->isPaymentsConnected();
    }

    /**
     * Get Token
     * @return \Quickbook instance
     * @return boolean
     */
    public function getToken()
    {
        $token = $this->isConnected(TRUE);
        if($token && $this->repo->isAccessTokenExpired($token->access_token)) {
            $tokenObj = new AccessToken;
            $tokenObj->setRefreshToken($token->refresh_token);
            $newTokenObj = $this->client->refreshAccessToken($tokenObj);
            
            $token = $this->repo->updateByRefreshToken(
                $tokenObj->getRefreshToken(), 
                $newTokenObj->getAccessToken(), 
                $newTokenObj->getRefreshTokenExpiresIn(), 
                $newTokenObj->getRefreshToken()
            );
        }
        return $token;
    }

    public function token()
    {
        return $this->getToken();
    }

    /**
     * @param  Object $token Quickbook Token
     * @param  Instance $invoice Job Invoice
     * @return Invoice
     */
    public function createOrUpdateInvoice($token, $invoice)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        try {
            $divisionId = null;
            $customer = $invoice->customer;
            $job = $invoice->job;
            $jobQuickbookId = $this->getJobQuickbookId($token, $job);

            $division = $job->division;
            if($job->isProject()) {
                $parentJob = Job::find($job->parent_id);
                $division  = $parentJob->division;
            }

            if (($division = $job->division) && $division->qb_id) {
                $divisionId = $division->qb_id;
            }
            $item = $this->findOrCreateItem($token);
            $invoiceData = $this->invoiceMapData(
                $token,
                $invoice,
                $jobQuickbookId,
                $item['id'],
                $item['name'],
                $divisionId
            );
            $quickbookInvoice = $this->client->request("/invoice", 'POST', $invoiceData);

            if (!empty($quickbookInvoice)
                && ine($quickbookInvoice, 'Invoice')
                && ine($quickbookInvoice['Invoice'], 'Id')) {
                //create or update qb invoice pdf
                $this->createOrUpdateQbInvoicePdf($invoice, $token);
                $invoice->quickbook_invoice_id = $quickbookInvoice['Invoice']['Id'];
                $invoice->quickbook_sync = true;
                DB::table('job_invoices')->where('id', $invoice->id)
                ->update([
                    'quickbook_invoice_id' => $quickbookInvoice['Invoice']['Id'],
                    'quickbook_sync' => true,
                ]);
            }

            return $invoice;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * create and update Qb invoice pdf file
     * @param  Instance $invoice Job Invoice
     */
    public function createOrUpdateQbInvoicePdf($invoice, $token = null) {

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        if (!$invoice) return false;

        if(!$token) return false;

        $qbInvoice = $this->getPdf($token, $invoice);

        if(!$qbInvoice) return false;

        $fileName = $invoice->id.'_qb_invoice.pdf';
        $baseName = 'job_invoices/'.$fileName;
        $fullPath = config('jp.BASE_PATH') . $baseName;

        if($invoice->qb_file_path){
			$oldFilePath = config('jp.BASE_PATH') . $invoice->qb_file_path;
			FlySystem::delete($oldFilePath);
        }

        FlySystem::put($fullPath, $qbInvoice, ['ContentType' => 'application/pdf']);

        $invoice->qb_file_path = $baseName;
        $invoice->qb_file_size = FlySystem::getSize($fullPath);
        $invoice->update();

        return true;
    }

    /**
     * Invoice Map Data
     * @param  Object $object Token Instance
     * @param  Instance $invoice JobInvoice
     * @param  Int $customerReferenceId Customer Reference Id
     * @param  Int $serviceId Service Id
     * @param  String $serviceName Service Name
     * @return Array Invoice Map Data
     */
    public function invoiceMapData($token, $invoice, $customerReferenceId, $serviceId, $serviceName, $divisionId = null, $searchInvoice = true)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);

        //create quickbook lines
        $lineItems = [];
        $discountAmt = 0;
        foreach ($invoice->lines as $line) {

            $defaultServiceItem = $serviceId;

            if (isset($line->workType->qb_id)) {
                $defaultServiceItem = $line->workType->qb_id;
            }

            if(!$line->is_chargeable) {
                $discountAmt = $discountAmt + ($line->amount * $line->quantity);
                $lineAmount  = $line->amount;
            } else {
                $lineAmount = totalAmount($line->amount, $invoice->getTaxRate());
            }
            $lineItems[] = [
                'Amount' => numberFormat($lineAmount * $line->quantity),
                'DetailType' => 'SalesItemLineDetail',
                'Description' => $line['description'],
                'SalesItemLineDetail' => [
                    'ItemRef' => [
                        'value' => $defaultServiceItem,
                    ],
                    'UnitPrice' => $lineAmount,
                    'Qty' => $line->quantity,
                ]
            ];
        }

        if($discountAmt) {
            $lineItems[] = [
                "DetailType" => "DiscountLineDetail",
                "Amount" => numberFormat($discountAmt),
                "DiscountLineDetail" => [
                    "PercentBased" => false
                ]
            ];
        }

        $invoiceData = [
            'Line' => $lineItems,
            'CustomerRef' => [
                'value' => $customerReferenceId
            ],
            'DocNumber' => JobInvoice::QUICKBOOK_INVOICE_PREFIX . $invoice->invoice_number
        ];

        if ($divisionId) {
            $invoiceData['DepartmentRef']['value'] = $divisionId;
        }
        
        $response = null;
        if($searchInvoice) {
            $response = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);
        }

        //Set Default Invoice Due  Date and Txn Date
        $dateTime = convertTimezone($invoice->created_at, Settings::get('TIME_ZONE'));
        $date = $dateTime->format('Y-m-d');

        $invoiceData['DueDate'] = $date;
        $invoiceData['TxnDate'] = $date;

        if (ine($response, 'Id')) {
            $invoiceData['Id'] = $response['Id'];
            $invoiceData['SyncToken'] = $response['SyncToken'];
        }

        if ($invoice->due_date) {
            $invoiceData['DueDate'] = $invoice->due_date;
        }

        if ($invoice->date) {
            $invoiceData['TxnDate'] = $invoice->date;
        }

        if ($invoice->note) {
            $invoiceData['CustomerMemo']['value'] = $invoice->note;
        }

        return $invoiceData;
    }

    /**
     * Get Job Quickbook Id
     * @param  Object $token Token
     * @param  Instance $job Job
     * @return Quickbook Job Id
     */
    public function getJobQuickbookId($token, $job)
    {
        if (!$token) {
            return false;
        }

        $this->client->setToken($token);

        $jobEntity = [];
        $customer = $job->customer;
        $customer = $this->createOrUpdateCustomer($token, $customer);

        if ($job->isProject()) {
            $parentJob = $job->parentJob;
            $referenceId = $this->getParentJobQuickbookId($token, $parentJob);
        } else {
            if ($job->isMultiJob()) {
                $referenceId = $this->getParentJobQuickbookId($token, $job);

                return $referenceId;
            } else {
                $referenceId = $customer->quickbook_id;
            }
        }

        $displayName = $job->getQuickbookDisplayName();
        $quickbookId = $job->quickbook_id;
        $data = $this->getQuickbookCustomer($token, $quickbookId, $displayName);

        $dateTime = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));
        $createdDate = $dateTime->format('Y-m-d');

        $jobEntity = [
            'MetaData' => [
                'CreateTime' => $createdDate,
            ]
        ];

        $jobEntity['Job'] = true;
        $jobEntity['DisplayName'] = removeQBSpecialChars($displayName);
        $jobEntity['BillWithParent'] = true;
        $jobEntity['ParentRef']['value'] = $referenceId;

        $billingAddress = $customer->billing;
        $jobEntity['GivenName'] = removeQBSpecialChars(substr($customer->getFirstName(), 0, 25)); // maximum of 25 char
        $jobEntity['FamilyName'] = removeQBSpecialChars(substr($customer->last_name, 0, 25));
        $jobEntity['CompanyName'] = substr($customer->getCompanyName(), 0, 25);
        $jobEntity['BillAddr'] = [
            'Line1' => $billingAddress->address,
            'Line2' => $billingAddress->address_line_1,
            'City' => $billingAddress->city ? $billingAddress->city : '',
            'Country' => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
            'CountrySubDivisionCode' => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
            'PostalCode' => $billingAddress->zip
        ];
        $jobEntity = array_filter($jobEntity);

        if (ine($data, 'Id')) {
            $jobEntity = array_merge($data, $jobEntity);
        }

        $customer = $this->client->request("/customer", 'POST', $jobEntity);

        $job->update([
            'quickbook_id' => $customer['Customer']['Id'],
            'quickbook_sync' => true
        ]);

        return $customer['Customer']['Id'];
    }

    /**
     * Get Parent Job Quickbook Id
     * @param  Object $token Token
     * @param  Instance $job Job
     * @return Int Quickbook Id
     */
    public function getParentJobQuickbookId($token, $job)
    {
        if (!$token || !$job->isMultiJob()) {
            return false;
        }

        $jobEntity = [];
        $customer = $job->customer;
        $displayName = $job->getQuickbookDisplayName();
        $quickbookId = $job->quickbook_id;
        $data = $this->getQuickbookCustomer($token, $quickbookId, $displayName);

        // if($job->quickbook_id && ine($data, 'Id')) {

        // 	return $data['Id'];
        // }

        $dateTime = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));
        $createdDate = $dateTime->format('Y-m-d');
        $jobEntity = [
            'MetaData' => [
                'CreateTime' => $createdDate,
            ]
        ];

        $jobEntity['Job'] = true;
        $jobEntity['DisplayName'] = removeQBSpecialChars($displayName);
        $jobEntity['BillWithParent'] = true;
        $jobEntity['ParentRef']['value'] = $customer->quickbook_id;

        $billingAddress = $customer->billing;
        $jobEntity['GivenName'] = removeQBSpecialChars(substr($customer->getFirstName(), 0, 25)); // maximum of 25 char
        $jobEntity['FamilyName'] = removeQBSpecialChars(substr($customer->last_name, 0, 25));
        $jobEntity['CompanyName'] = substr($customer->getCompanyName(), 0, 25);
        $jobEntity['BillAddr'] = [
            'Line1' => $billingAddress->address,
            'Line2' => $billingAddress->address_line_1,
            'City' => $billingAddress->city ? $billingAddress->city : '',
            'Country' => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
            'CountrySubDivisionCode' => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
            'PostalCode' => $billingAddress->zip
        ];

        $jobEntity = array_filter($jobEntity);

        if (ine($data, 'Id')) {
            $jobEntity = array_merge($data, $jobEntity);
        }

        $customer = $this->client->request("/customer", 'POST', $jobEntity);

        $job->update([
            'quickbook_id' => $customer['Customer']['Id'],
            'quickbook_sync' => true
        ]);

        return $job->quickbook_id;
    }

    /**
     * Get Customer Quickbook Id
     * @param  Object $token token
     * @param  Instance $customer Customer
     * @return Customer Quickbook Id
     */
    public function getCustomerQuickbookId($token, $customer)
    {
        if (!$token) {
            return false;
        }

        try {
            $customer = $this->createOrUpdateCustomer($token, $customer);

            return $customer->quickbook_id;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Get Quickbook Pdf File
     * @param  Int $invoiceId Invoice Id
     * @return Pdf Format File
     */
    public function getPdf($token, $jobInvoice)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        if (!$this->isValidToken($token)) {
            return false;
        }

        $this->client->setToken($token);
        $result = false;
        try {
            $invoice = $this->getQuickbookInvoice($jobInvoice->quickbook_invoice_id, $jobInvoice->invoice_number);

            if(!ine($invoice, 'Id')) return $result;

            if($invoice['Id'] != $jobInvoice->quickbook_invoice_id) {
                $jobInvoice->quickbook_invoice_id = $invoice['Id'];
                $jobInvoice->save();
            }

            $result = $this->client->createInvoicePdf("/invoice/{$jobInvoice->quickbook_invoice_id}/pdf");
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }


    /**
     * Invoice Payment
     * @param  Object $token Token
     * @param  Instance $payment JobPayment
     * @return Payment Instance
     */
    public function invoicePayment($token, $payment)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        try {
            $job = $payment->job;
            $customer = $job->customer;
            $referenceId = $this->getCustomerQuickbookId($token, $customer);
            $queryResponse = [];
            $paymentData = $this->jobInvoicePaymentDataMap($token, $payment, $referenceId);
            $paymentResponse = $this->client->request("/payment", 'POST', $paymentData);

            $payment->update([
                'quickbook_id' => $paymentResponse['Payment']['Id'],
                'quickbook_sync_token' => $paymentResponse['Payment']['SyncToken'],
                'quickbook_sync' => true
            ]);

            return $payment;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Invoice Payment
     * @param  Object   $token Token
     * @param  Instance $payment JobPayment
     * @return Payment Instance
     */
    public function invoiceCredits($token, $payment, $referenceId, $data)
    {
        if(!$token) return false;

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        try{
            // $job = $payment->job;
            // $customer = $job->customer;
            // $referenceId = $this->getjobQuickbookId($token, $job);
            $queryResponse = [];
            $paymentData = $this->invoiceCreditPaymentDataMap($token, $payment, $referenceId, $data);
            $paymentResponse = $this->client->request("/payment", 'POST', $paymentData);
            if($paymentResponse['Payment']){
                $payment->update([
                    'quickbook_id'         => $paymentResponse['Payment']['Id'],
                    'quickbook_sync_token' => $paymentResponse['Payment']['SyncToken'],
                    'quickbook_sync'       => true
                ]);
            }
            return $payment;
        } catch (Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * @param  Object $token Token
     * @param  Instance $payment Payment
     * @param  Int $jobQuickbookId Quickbook Id
     * @return Mapped Payment Data
     */
    public function paymentByBatchRequset($token, $payment, $referenceId)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);

        $data = $this->jobInvoicePaymentDataMap($token, $payment, $referenceId);

        return $data;
    }

    /**
     * @param  Object   $token          Token
     * @param  Instance $payment        Payment
     * @param  Int      $jobQuickbookId Quickbook Id
     * @return Mapped Credit Payment Data
     */
    public function creditPaymentByBatchRequset($token, $payment, $referenceId, $data)
    {
        if(!$token) return false;

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        $data = $this->invoiceCreditPaymentDataMap($token, $payment, $referenceId, $data);
        return $data;
    }

    /**
     * Batch Request
     * @param  Object $token Token
     * @param  Array $data batch Data
     * @return Response
     */
    public function batchRequest($token, $data)
    {
        try {
            if (!$token) {
                return false;
            }

            if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
                return false;
            }

            $this->client->setToken($token);
            $invoice = $this->client->request("/batch", 'POST', $data);

            return $invoice;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Get Quickbook Invoice
     * @param  Int $invoiceId Invoice Id
     * @return Response
     */
    public function getInvoice($invoiceId)
    {
        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $param = [
            'query' => "SELECT SyncToken  FROM Invoice  WHERE Id = '" . $invoiceId . "'"
        ];
        try {
            $queryResponse = $this->getDataByQuery($param);
            if (!empty($queryResponse)) {
                return $queryResponse;
            }

            return false;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Quickbook Customer Import
     * @return Array Customer data
     */
    public function customerImport()
    {
        $token = $this->repo->token();
        if (!$token) {
            return false;
        }
        $this->client->setToken($token);

        try {
            $startPosition = 1;
            $maxResult = 1000;
            $response = $this->client->request("/query", 'GET', [
                'query' => "SELECT COUNT(*)FROM Customer WHERE Job = FALSE"
            ]);

            $totalCustomers = $response['QueryResponse']['totalCount'];
            $count = ceil($totalCustomers / $maxResult);
            $customers = [];

            for ($i = 1; $i <= $count; $i++) {
                $response = $this->client->request("/query", 'GET', [
                    'query' => "SELECT *  FROM Customer WHERE Job = FALSE STARTPOSITION {$startPosition} MAXRESULTS {$maxResult}"
                ]);
                $startPosition += $maxResult;

                if (!isset($response['QueryResponse']['Customer'])) {
                    continue;
                }

                $customers = array_merge($customers, (array)$response['QueryResponse']['Customer']);
            }

            $customerQuickbookIds = $this->customerRepo->getQuickbookCustomerList();

            foreach ($customers as $key => $customer) {
                if (in_array($customer['Id'], (array)$customerQuickbookIds)) {
                    unset($customers[$key]);
                }
            }

            return $customers;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Cancel Payment
     * @param  Object $token Token
     * @param  Instance $payment Payment
     * @return boolean
     */
    public function cancelPayment($token, $payment)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        try {
            $param = [
                'query' => "SELECT *  FROM Payment WHERE Id = '" . $payment->quickbook_id . "'"
            ];
            $queryResponse = $this->getDataByQuery($param);
            if (empty($queryResponse)) {
                return false;
            }
            $syncToken = $queryResponse['Payment'][0]['SyncToken'];
            $job = $payment->job;
            $customer = $job->customer;
            $quickbookJobId = $this->getCustomerQuickbookId($token, $customer);

            $payementEntitiy = [
                'Id' => $payment->quickbook_id,
                'SyncToken' => $syncToken,
                'CustomerRef' => [
                    'value' => $quickbookJobId
                ]
            ];

            $this->client->request("/payment", 'POST', $payementEntitiy);
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Cancel Credit Payment
     * @param  Object   $token    Token
     * @param  Instance $payment  Payment
     * @return boolean
     */
    public function cancelCreditPayment($token, $payment)
    {
        if(!$token) return false;

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        try {
            $param = [
                'query' => "SELECT *  FROM Payment WHERE Id = '".$payment->quickbook_id."'"
            ];
            $queryResponse = $this->getDataByQuery($param);
            if(empty($queryResponse)) {
                return false;
            }
            $syncToken = $queryResponse['Payment'][0]['SyncToken'];
            $job = $payment->job;
            $quickbookJobId = $this->getJobQuickbookId($token, $job);
            $payementEntitiy = [
                'Id' => $payment->quickbook_id,
                'SyncToken' => $syncToken,
                'CustomerRef' => [
                    'value' => $quickbookJobId
                ]
            ];
            $this->client->request("/payment", 'POST', $payementEntitiy, ['operation' => 'delete']);
        } catch (Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Invoice Delete On Quickbook
     * @param  Object $token Token
     * @param  Instance $jobInstance jobInvoice
     * @return Boolean
     */
    public function deleteInvoice($token, $invoice)
    {
        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        if (!$token || !($invoice->quickbook_invoice_id)) {
            return false;
        }

        $this->client->setToken($token);

        try {
            $response = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);

            if (!ine($response, 'Id')) {
                return false;
            }

            $this->client->request(
                "/invoice",
                'POST',
                [
                    'Id' => $response['Id'],
                    'SyncToken' => $response['SyncToken']
                ],
                [
                    'operation' => 'delete'
                ]
            );

            $invoice->update([
                'quickbook_invoice_id' => null,
                'quickbook_sync' => false
            ]);
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Payment Data Map
     * @param  Object $token Token
     * @param  Instance $payment Job Payment
     * @param  Int $jobQuickbookId Quickbook Id
     * @return Payment Data
     */
    public function jobInvoicePaymentDataMap($token, $payment, $referenceId)
    {
        if ($payment->quickbook_id) {
            $param = [
                'query' => "SELECT *  FROM Payment WHERE Id = '" . $payment->quickbook_id . "'"
            ];

            $queryResponse = $this->getDataByQuery($param)['Payment'][0];

            if (!empty($queryResponse)) {
                $payment->quickbook_id = $queryResponse['Id'];
                $payment->quickbook_sync_token = $queryResponse['SyncToken'];
                $payment->update();
            } else {
                $payment->update([
                    'quickbook_id' => null,
                    'quickbook_sync_token' => false,
                ]);
            }
        }

        $customerId = $payment->customer_id;

        $mapInput = [
            'CustomerRef' => [
                'value' => $referenceId,
            ],
            'TotalAmt' => $payment->payment,
        ];

        if ($payment->quickbook_id) {
            $mapInput['Id'] = $payment->quickbook_id;
            $mapInput['SyncToken'] = $payment->quickbook_sync_token;
        }

        $data['method'] = $payment->method;

        if ($payment->method === 'echeque') {
            $data['method'] = 'Check';
        }

        if ($payment->unapplied_amount) {
            $mapInput['UnappliedAmt'] = $payment->unapplied_amount;
        }

        //get list of linked quickbook invoice
        
        /* This raw query seems to not be working; So commenting this out.  */
        
        // $invoiceLists = InvoicePayment::where('payment_id', $payment->id)
        //     ->leftJoin(DB::raw("(SELECT * FROM job_invoices) as job_invoices"), 'job_invoices.id', '=', 'invoice_payments.invoice_id')
        //     ->whereNotNull('quickbook_invoice_id')
        //     ->selectRaw('sum(invoice_payments.amount) as amount, job_invoices.quickbook_invoice_id, invoice_id')
        //     ->groupBy('invoice_id')
        //     ->get();
        # TODO: This has to be done in query builder
        $invoiceLists = DB::select(DB::raw("select sum(invoice_payments.amount) as amount, quickbook_invoice_id, invoice_id, invoice_number from `invoice_payments` inner join (SELECT * FROM job_invoices) as job_invoices on `job_invoices`.`id` = `invoice_payments`.`invoice_id` where `payment_id` = " . $payment->id . " and `quickbook_invoice_id` is not null group by `job_invoices`.`id`"));

        if (!empty($invoiceLists)) {
            $count = 0;
            foreach ($invoiceLists as $key => $invoice) {
                $quickbookInvoice = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);
                if (!ine($quickbookInvoice, 'Id')) {
                    continue;
                }

                $txnData[0] = [
                    'TxnId' => $quickbookInvoice['Id'],
                    'TxnType' => "Invoice"
                ];

                $LinkedTxn = [
                    'LinkedTxn' => $txnData,
                    'Amount' => number_format($invoice->amount, 2, '.', '')
                ];

                $mapInput['Line'][$count++] = $LinkedTxn;
            }
        }

        $payentMethodRefId = $this->getPaymentReference($token, ucfirst($data['method']));

        if ($payentMethodRefId) {
            $mapInput['PaymentMethodRef']['value'] = $payentMethodRefId;
        }

        if ($payment->echeque_number) {
            $mapInput['PaymentRefNum'] = $payment->echeque_number;
        }

        if ($payment->date) {
            $mapInput['TxnDate'] = $payment->date;
        }

        return $mapInput;
    }

    /**
     * Payment Data Map
     * @param  Object   $token          Token
     * @param  Instance $payment        Job Payment
     * @param  Int      $jobQuickbookId Quickbook Id
     * @return Payment Data
     */
    public function invoiceCreditPaymentDataMap($token, $payment, $referenceId, $data)
    {
        if($payment->quickbook_id) {
            $param = [
                'query' => "SELECT *  FROM Payment WHERE Id = '".$payment->quickbook_id."'"
            ];
            $queryResponse = $this->getDataByQuery($param)['Payment'][0];
            if(!empty($queryResponse)) {
                $payment->quickbook_id         = $queryResponse['Id'];
                $payment->quickbook_sync_token = $queryResponse['SyncToken'];
                $payment->update();
            }else{
                $payment->update([
                    'quickbook_id'         => null,
                    'quickbook_sync_token' => false,
                ]);
            }
        }
        $customerId = $payment->customer_id;
        $mapInput = [
            'CustomerRef' => [
                'value' => $referenceId,
            ],
            'TotalAmt' => 0,
            'sparse' => true,
        ];
        if($payment->quickbook_id) {
            $mapInput['Id']        = $payment->quickbook_id;
            $mapInput['SyncToken'] = $payment->quickbook_sync_token;
        }
        $data['method'] = $payment->method;
        if($payment->method === 'echeque') {
            $data['method'] = 'Check';
        }
        if($payment->unapplied_amount) {
            $mapInput['UnappliedAmt'] = 0;
        }
        
        $invoice = JobInvoice::find($data['invoice_id']);
        $credit = $payment->credit;
        $quickbookInvoice = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);
        $quickbookCreditMemo = $this->getQuickbookCreditMemo($credit->quickbook_id);
        $txnInvoiceData[0] = [
            'TxnId'   => $quickbookInvoice['Id'],
            'TxnType' => "Invoice"
        ];
        $LinkedInvoiceTxn = [
            'LinkedTxn' => $txnInvoiceData,
            'Amount' => number_format($data['amount'], 2, '.', '')
        ];
        $txnCreditData[0] = [
            'TxnId'   => $quickbookCreditMemo['Id'],
            'TxnType' => "CreditMemo"
        ];
        $LinkedCreditTxn = [
            'LinkedTxn' => $txnCreditData,
            'Amount' => number_format($data['amount'], 2, '.', '')
        ];
        $mapInput['Line'][0] = $LinkedInvoiceTxn;
        $mapInput['Line'][1] = $LinkedCreditTxn;
        $payentMethodRefId = $this->getPaymentReference($token, ucfirst($data['method']));
        if($payentMethodRefId) {
            $mapInput['PaymentMethodRef']['value'] = $payentMethodRefId;
        }
        if($payment->echeque_number) {
            $mapInput['PaymentRefNum'] =  $payment->echeque_number;
        }
        if($payment->date) {
            $mapInput['TxnDate'] = $payment->date;
        }
        return $mapInput;
    }


    /**
     * Create Credit Note
     * @param  Object $token Token
     * @param  Instance $creditNote CreditNote
     * @param  String   Credit Note Description
     * @return Credit Note
     */
    public function createCreditNote($token, $creditNote, $description, $jobQuickbookId = null)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        $job = $creditNote->job;
        try {
            if (!$jobQuickbookId) {
                $jobQuickbookId = $this->getJobQuickbookId($token, $job);
            }

            $itemRef = $this->findOrCreateItem($token);
            $params = [
                'Line' => [
                    [
                        'Amount' => $creditNote->amount,
                        'DetailType' => 'SalesItemLineDetail',
                        'Description' => $description,
                        'SalesItemLineDetail' => [
                            'ItemRef' => [
                                'value' => $itemRef['id'],
                                'name' => $itemRef['name']
                            ]
                        ]

                    ]
                ],
                'CustomerRef' => [
                    'value' => $jobQuickbookId
                ],
                'DocNumber' => $creditNote->id
            ];

            if($creditNote->quickbook_id){
                $param = [
                    'query' => "SELECT *  FROM CreditMemo WHERE DocNumber = '".$creditNote->id."' AND Id = '".$creditNote->quickbook_id."' "
                ];
                $queryResponse = $this->getDataByQuery($param)['CreditMemo'][0];
                if(!empty($queryResponse)) {
                    $params['Id']        = $queryResponse['Id'];
                    $params['SyncToken'] = $queryResponse['SyncToken'];
                }
                $params['Balance'] = $creditNote->unapplied_amount;
            }

            if ($creditNote->date) {
                $params['TxnDate'] = $creditNote->date;
            }

            $response = $this->client->request("/creditmemo", 'POST', $params);

            $creditNote->update([
                'quickbook_id' => $response['CreditMemo']['Id'],
                'quickbook_sync' => true,
            ]);

            return $creditNote;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }

        return $creditmemo;
    }

    /**
     * Mapped Credit Note Data
     * @param  Object $token Token
     * @param  Instance $jobCredit Job Credit
     * @param  Int $serviceId Service id
     * @param  String $serviceName Service Name
     * @return Array Credit Note Data
     */
    public function mapCreditNoteData($token, $jobCredit, $serviceId, $serviceName)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);

        $job = $jobCredit->job;
        $description = $jobCredit->jobTradeDescription();

        $params = [
            'Line' => [
                [
                    'Amount' => $jobCredit->amount,
                    'DetailType' => 'SalesItemLineDetail',
                    'Description' => $description,
                    'SalesItemLineDetail' => [
                        'ItemRef' => [
                            'value' => $serviceId,
                            'name' => $serviceName
                        ]
                    ]

                ]
            ],
            'CustomerRef' => [
                'value' => $job->quickbook_id
            ],
            'DocNumber' => $jobCredit->id
        ];

        if ($jobCredit->date) {
            $params['TxnDate'] = $jobCredit->date;
        }

        $param = [
            'query' => "SELECT *  FROM CreditMemo WHERE DocNumber = '" . $jobCredit->id . "' AND Id = '" . $jobCredit->quickbook_id . "' "

        ];

        $queryResponse = $this->getDataByQuery($param)['CreditMemo'][0];

        if (!empty($queryResponse)) {
            $params['Id'] = $queryResponse['Id'];
            $params['SyncToken'] = $queryResponse['SyncToken'];
        }

        return $params;
    }

    /**
     * Create Or Update Customer
     * @param  Instance $customerData Customer
     * @return Boolean
     */
    public function createOrUpdateCustomer($token, $customer)
    {
        if (!$token) {
            return false;
        }

        $this->client->setToken($token);

        try {
            $reverseDisplayName = null;

            if ($customer->is_commercial) {
                $displayName = $customer->first_name . ' ' . '(' . $customer->id . ')';
            } else {
                $displayName = $customer->first_name . ' ' . $customer->last_name . ' ' . '(' . $customer->id . ')';
                $reverseDisplayName = $customer->last_name . ' ' . $customer->first_name . ' ' . '(' . $customer->id . ')';
            }

            $quickbookId = $customer->quickbook_id;

            $data = $this->getQuickbookCustomer($token, $quickbookId, $displayName, $reverseDisplayName, $isJob = false);

            $customerEntity = $this->mapCustomerData($customer, $data);

            $response = $this->client->request("/customer", 'POST', $customerEntity);

            $data = [
                'quickbook_id' => $response['Customer']['Id'],
                'quickbook_sync_token' => $response['Customer']['SyncToken'],
                'quickbook_sync' => true,
            ];

            $customer->update($data);

            return $customer;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    public function createOrUpdateJob($token, $job, $meta)
    {
        if (!$token) {
            return false;
        }

        $this->client->setToken($token);

        try {
            //create job & customer & if job is project then create parent job
            $jobQuickbookId = $this->getJobQuickbookId($token, $job);
            $job->quickbook_id = $jobQuickbookId;

            if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
                return false;
            }

            switch ($meta['type']) {
                case 'financials':
                    $this->updateJobFinancial($token, $job);
                    break;
                case 'invoices_with_financials':
                    $this->updateJobFinancialWithInvoice($token, $job);
                    break;
            }

            return $job;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Delete Credit Note
     * @param  Object $token Token
     * @param  Instance $jobCredit Job Credit
     * @return Boolean
     */
    public function deleteCreditNote($token, $jobCredit)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        $this->client->setToken($token);
        $id = $jobCredit->quickbook_id;

        try {
            $param = [
                'query' => "SELECT *  FROM CreditMemo WHERE DocNumber = '" . $jobCredit->id . "' AND Id = '" . $jobCredit->quickbook_id . "' "
            ];

            $queryResponse = $this->getDataByQuery($param);

            if (isset($queryResponse['CreditMemo'][0]['Id'])) {
                $this->client->request(
                    "/creditmemo",
                    'POST',
                    [
                        'Id' => $queryResponse['CreditMemo'][0]['Id'],
                        'SyncToken' => $queryResponse['CreditMemo'][0]['SyncToken']
                    ],
                    [
                        'operation' => 'delete'
                    ]
                );
            }

            $jobCredit->update([
                'quickbook_id' => null,
                'quickbook_sync' => false,
            ]);
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }

        return true;
    }

    /**
     * Get Payment Reference Method id
     * @param  Object $token Token
     * @param  String $method Payment Method
     * @return Payment Method Id
     */
    public function getPaymentReference($token, $method)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        try {
            $this->client->setToken($token);

            if ($method === 'Cc') {
                $method = 'Credit Card';
            }
            // $quickbook = $this->repo->getCompany();
            // $meta = $quickbook->meta()->whereMetaKey($method)->first();
            // if($meta) {
            // 	return $meta->meta_value;
            // }

            $param = [
                'query' => "SELECT *  FROM PaymentMethod  WHERE name = '" . addslashes($method) . "'"
            ];
            $queryResponse = $this->client->request("/query", 'GET', $param);
            if (!empty($queryResponse['QueryResponse'])) {
                $id = $queryResponse['QueryResponse']['PaymentMethod'][0]['Id'];
            } else {
                $id = $this->createPaymentMethod($token, $method);
            }

            // $data = [
            // 		'quickbook_id' => $quickbook->id,
            // 		'meta_key'     => $method,
            // 		'meta_value'   => $id
            // ];

            // QuickbookMeta::create($data);
            return $id;
        } catch (\Exception $e) {
            $this->quickBookExceptionThrow($e);
        }
    }

    /**
     * Find Or Create Item(Service)
     * @param  Object $token Token
     * @param  String $itemName Item Name
     * @return Array
     */
    public function findOrCreateItem($token, $itemName = 'Services')
    {
        $this->client->setToken($token);

        // $quickbook = $this->repo->getCompany();
        // $meta = $quickbook->meta()->whereMetaKey($itemName)->first();
        // if($meta) {

        // 	return [
        // 		'id'   => $meta->meta_value,
        // 		'name' => $meta->meta_key
        // 	];
        // }

        $param = [
            'query' => "SELECT *  FROM item WHERE name = '" . $itemName . "'"
        ];

        $queryResponse = $this->getDataByQuery($param);
        if (!empty($queryResponse)) {
            $item = [
                'id' => $queryResponse['Item'][0]['Id'],
                'name' => $queryResponse['Item'][0]['Name']
            ];
        } else {
            $item = $this->createItem($itemName);
        }

        // $data = [
        // 	'quickbook_id' => $quickbook->id,
        // 	'meta_key'     => $item['name'],
        // 	'meta_value'   => $item['id']
        // ];

        // QuickbookMeta::create($data);

        return $item;
    }

    /**
     * Create Payment Method
     * @param  Object $token Token
     * @param  String $method Payment Method
     * @return Int Payment Method Id
     */
    public function createPaymentMethod($token, $method)
    {
        $paymentEntity = [
            'Name' => $method
        ];

        $payment = $this->client->request("/paymentmethod", 'POST', $paymentEntity);

        return $payment['PaymentMethod']['Id'];
    }

    /**
     * Get Quickbook Customer
     * @param  Object $token Token
     * @param  Int $id Customer Id
     * @param  String $displayName Customer Display Name
     * @return Array of ['Customer quickbook id', 'Quickbook Sync Token']
     */
    public function getQuickbookCustomer($token, $id = null, $displayName = null, $reverseDisplayName = null, $isJob = true)
    {
        if (!$token) {
            return false;
        }

        if (!$id && !$displayName) {
            return false;
        }

        $this->client->setToken($token);

        $entity = false;

        $displayName = "'" .addslashes(removeQBSpecialChars($displayName)). "'";

        if($reverseDisplayName) {
            $displayName .= ", '".addslashes(removeQBSpecialChars($reverseDisplayName)). "'";
        }

        $query = "SELECT * FROM Customer WHERE DisplayName IN ($displayName)";
        $param = [
            'query' => $query,
        ];

        $queryResponse = $this->dataExist($param);

        if(!ine($queryResponse, 'Customer') && ($id)) {
            $query = "SELECT *  FROM  Customer WHERE Id = '".$id."'";
            if($isJob) {
                $query .= ' AND job = true';
            } else {
                $query .= ' AND job = false';
            }

            $param = [
                'query' => $query,
            ];
            $queryResponse = $this->dataExist($param);
        }
        $queryResponse = $this->dataExist($param);

        if (ine($queryResponse, 'Customer')) {
            $entity['Id'] = (int)$queryResponse['Customer'][0]['Id'];
            $entity['SyncToken'] = (int)$queryResponse['Customer'][0]['SyncToken'];
        }

        return $entity;
    }

    /**
     * Sync all Job Invoice
     * @param  Object $token Token
     * @param  Job Instance $job   Job
     * @return Boolean
     */
    public function syncPendingCustomerInvoices($token, $customer)
    {
        if (!$token) {
            return false;
        }

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        try {
            $this->client->setToken($token);

            $jobids = $customer->jobs->pluck('id')->toArray();
            $count = JobInvoice::whereIn('job_id', (array)$jobids)
                ->whereNull('quickbook_invoice_id')
                ->count();

            if (!$count) {
                return false;
            }

            $meta = $this->findOrCreateItem($token);

            goto batch;

            batch:{
                $query = JobInvoice::whereIn('job_id', (array)$jobids)
                    ->with('job.division')
                    ->whereNull('quickbook_invoice_id');

                $query->chunk(30, function ($invoices) use ($meta, $token) {
                    $invoiceData = [];
                    foreach ($invoices as $key => $invoice) {

                        $response = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);
                        if(ine($response, 'Id')) {
                            $invoice->quickbook_invoice_id = $response['Id'];
                            $invoice->quickbook_sync_token = $response['SyncToken'];
                            $invoice->save();
                            continue;
                        }

                        $job = $invoice->job;
                        $customer = $job->customer;
                        $jobQuickbookId = $this->getJobQuickbookId($token, $job);

                        $division = $job->division;
                        if($job->isProject()) {
                            $parentJob = Job::find($job->parent_id);
                            $division  = $parentJob->division;
                        }
                        $divisionId = null;

                        if (($division = $job->division) && $division->qb_id) {
                            $divisionId = $division->qb_id;
                        }

                        // map payment data for batch request
                        $data = $this->invoiceMapData(
                            $token,
                            $invoice,
                            $jobQuickbookId,
                            $meta['id'],
                            $meta['name'],
                            $divisionId,
                            $searchInvoice = false
                        );

                        $invoiceData[$key]['Invoice'] = $data;
                        $invoiceData[$key]['bId'] = $invoice->id;
                        $invoiceData[$key]['operation'] = 'create';
                    }

                    if(empty($invoiceData)) return false;

                    $batchData['BatchItemRequest'] = $invoiceData;
                    $response = $this->batchRequest($token, $batchData);
                    if (($response) && !empty($response['BatchItemResponse'])) {
                        foreach ($response['BatchItemResponse'] as $key => $value) {
                            if (!isset($value['Invoice']['Id'])) {
                                $jobInvoice = JobInvoice::find($value['bId']);
                                $jobInvoice = $this->createOrUpdateInvoice($token, $jobInvoice);
                                if (!$jobInvoice) {
                                    Log::info('Sync all customer invoices Quickbook: ' . json_encode($value));
                                }

                                continue;
                            }

                            $jobInvoice = JobInvoice::find($value['bId']);
                            $jobInvoice->update([
                                'quickbook_invoice_id' => $value['Invoice']['Id'],
                                'quickbook_sync' => true
                            ]);
                        }
                    }
                });
            }

            $count = JobInvoice::whereIn('job_id', (array)$jobids)
                ->whereNull('quickbook_invoice_id')
                ->count();
            if ($count) {
                goto batch;
            }

            return true;
        } catch (\Exception $e) {
            if ($e->getCode() != 429) {
                $this->quickBookExceptionThrow($e);
            }
        }
    }

    public function isValidToken($token)
    {
        return true;
        // /**
        //  * This following code needs to be removed after checking 2019-04-05 20:45
        //  */
        // if(!$token) return false;
        // $this->client->setToken($token);
        // try {
        //  // whether token is expired or not
        //  $expired = $this->repo->isAccessTokenExpired($token);
        //  // not expired then return
        //  if($expired) {
        //      // if expired then refresh
        //      $new_token = $this->client->refreshToken($token);
        //      // and return and save the token in DB
        //      $new_token = $this->updateAccessToken($new_token);
        //  }
        //  return true;
        // }catch(Exception $e) {
        //  $accountId = false;
        // }
    }

    /**
     * @method create customer payment on quickbook and check they are exist on quickbook online
     * @param  \Customer $customer [description]
     * @return [void]              [description]
     */
    public function paymentsSync($token, $paymentIds, $referenceId)
    {
        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        if (empty(array_filter((array)$paymentIds))) {
            return true;
        }
        if (!$referenceId) {
            return false;
        }

        if (!$this->isValidToken($token)) {
            return false;
        }

        goto batch;

        batch: {

        JobPayment::whereIn('id', (array)$paymentIds)
            ->whereNull('ref_id')
            ->whereNull('credit_id')
            ->whereNull('canceled')
            ->update([
                'quickbook_sync' => false
            ]);

        $query = JobPayment::whereIn('id', (array)$paymentIds)
            ->whereNull('ref_id')
            ->whereNull('credit_id')
            ->whereNull('canceled');

        $query->chunk(30, function ($payments) use ($referenceId, $token) {
            $paymentData = [];
            foreach ($payments as $key => $payment) {
                // map payment data for batch request
                $data = $this->paymentByBatchRequset(
                    $token,
                    $payment,
                    $referenceId
                );
                $paymentData[$key]['Payment'] = $data;
                $paymentData[$key]['bId'] = $payment->id;
                $paymentData[$key]['operation'] = 'create';
            }

            $batchData['BatchItemRequest'] = $paymentData;
            try {
                $response = $this->batchRequest($token, $batchData);
                if (($response) && !empty($response['BatchItemResponse'])) {
                    foreach ($response['BatchItemResponse'] as $key => $value) {
                        $payment = JobPayment::find($value['bId']);

                        if (isset($value['Fault']['Error'][0]['code'])) {
                            $paymentResponse = [
                                'quickbook_sync' => true,
                            ];
                        } else {
                            $paymentResponse = [
                                'quickbook_id' => $value['Payment']['Id'],
                                'quickbook_sync' => true,
                            ];
                        }
                        $payment->update($paymentResponse);
                    }
                }
            } catch (\Exception $e) {
                return false;
            }
        });
        }


        $pendingCount = JobPayment::whereIn('id', (array)$paymentIds)
            ->whereQuickbookSync(false)
            ->whereNull('ref_id')
            ->whereNull('canceled')
            ->count();

        if ($pendingCount) {
            $paymentIds = JobPayment::whereIn('id', (array)$paymentIds)
                ->whereNull('ref_id')
                ->whereQuickbookSync(false)
                ->whereNull('canceled')
                ->pluck('id')->toArray();

            goto batch;
        }

        return true;
    }

    /**
     * @method create customer credits on quickbook and check they are exist on quickbook online
     * @param  \Customer $customer [description]
     * @return [void]              [description]
     */
    public function syncCredits($token, $creditsIds, $referenceId)
    {
        if(empty(array_filter((array)$creditsIds))) return true;

        if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
            return false;
        }

        if(!$referenceId) {
            return false;
        }

        if(!$this->isValidToken($token)) {
            return false;
        }
        goto batch;
        batch: {
            JobPayment::whereIn('credit_id', (array)$creditsIds)
                ->whereNull('ref_id')
                ->whereNull('canceled')->update([
                'quickbook_sync' => false
            ]);
            $query = JobPayment::whereIn('credit_id', (array)$creditsIds)
                ->whereNull('ref_id')
                ->whereNull('canceled');
            $query->chunk(30, function($payments) use ($referenceId, $token)
            {
                $paymentData = [];
                foreach ($payments as $key => $payment) {
                    $invoicePayment = InvoicePayment::wherePaymentId($payment->id)->first();
                    $invoiceData = [];
                    if($invoicePayment){
                        $invoiceData['amount'] = $invoicePayment->amount;
                        $invoiceData['invoice_id'] = $invoicePayment->invoice_id;
                    }
                    // map payment data for batch request
                    $data = $this->creditPaymentByBatchRequset(
                        $token, $payment, $referenceId, $invoiceData
                    );
                    $paymentData[$key]['Payment'] = $data;
                    $paymentData[$key]['bId'] = $payment->id;
                    $paymentData[$key]['operation'] = 'create';
                }
                $batchData['BatchItemRequest'] = $paymentData;
                try {
                    $response = $this->batchRequest($token, $batchData);
                    if(($response) && ! empty($response['BatchItemResponse'])) {
                        foreach ($response['BatchItemResponse'] as $key => $value) {
                            $payment = JobPayment::find($value['bId']);
                            if(isset($value['Fault']['Error'][0]['code'])) {
                                $paymentResponse = [
                                    'quickbook_sync' => true,
                                ];
                            } else {
                                $paymentResponse = [
                                    'quickbook_id'   => $value['Payment']['Id'],
                                    'quickbook_sync' => true,
                                ];
                            }
                            $payment->update($paymentResponse);
                        }
                    }
                } catch(\Exceptions $e) {
                    return false;
                }
            });
        }
        $pendingCount = JobPayment::whereIn('credit_id', (array)$creditsIds)
            ->whereQuickbookSync(false)
            ->whereNull('ref_id')
            ->whereNull('canceled')
            ->count();
        if($pendingCount) {
            $creditsIds = JobPayment::whereIn('credit_id', (array)$creditsIds)
                ->whereNull('ref_id')
                ->whereQuickbookSync(false)
                ->whereNull('canceled')
                ->pluck('id')->toArray();
            goto batch;
        }
        return true;
    }

    /**
     * Delete Credit Note
     * @param  Object $token Token
     * @param  Instance $jobCredit Job Credit
     * @return Boolean
     */
    public function deleteJobInvoice($token, $jobInvoice)
    {
        if (!$token) {
            return false;
        }
        if (!$jobInvoice->quickbook_invoice_id) {
            return false;
        }

        $this->client->setToken($token);

        $queryResponse = $this->getQuickbookInvoice($jobInvoice->quickbook_invoice_id, $jobInvoice->invoice_number);

        if (ine($queryResponse, 'Id')) {
            $this->client->request(
                "/invoice",
                'POST',
                [
                    'Id' => $queryResponse['Id'],
                    'SyncToken' => $queryResponse['SyncToken']
                ],
                [
                    'operation' => 'delete'
                ]
            );

            $jobInvoice->update([
                'quickbook_invoice_id' => null,
                'quickbook_sync_token' => null,
                'quickbook_sync' => false,
            ]);
        }

        return true;
    }

    /**
     * Pagination For Accounts And Products Listing
     * @param  $count         Total Records
     * @param  $queryResponse Query Response
     * @param  $limit         Limit
     * @param  $page          Current Page
     *
     * @return meta data of pagination
     */
    public function paginatedResponse($records, $total, $limit, $page)
    {
        $meta = [];
        $totalPages = ceil($total / $limit);
        $meta['pagination'] = [
            'total' => $total,
            'count' => count($records),
            'per_page' => (int)$limit,
            'current_page' => (int)$page,
            'total_pages' => $totalPages,
        ];
        $data['data'] = $records;
        $data['meta'] = $meta;

        return $data;
    }

    /*************    Private Method ******************/

    /**
     * Map Customer Data
     * @param  Instance $customer Customer
     * @param  Array $data [Quickbook Id, Quickbook Sync Token]
     * @return Array Of Customer Data
     */
    private function mapCustomerData($customer, $data = [])
    {
        $billingAddress = $customer->billing;
        $firstName = $customer->first_name;
        $lastName = $customer->last_name;
        $companyName = $customer->company_name;

        if ($customer->is_commercial) {
            $firstName = '';
            $lastName = '';
            $companyName = $customer->first_name;
            $displayName = $companyName . ' ' . '(' . $customer->id . ')';
        } else {
            $displayNameFormat = Settings::get('QB_CUSTOMER_DISPLAY_NAME_FORMAT');

            switch ($displayNameFormat) {
                case QuickBook::LAST_NAME_FIRST_NAME:
                    $displayName = $customer->last_name . ' ' . $customer->first_name . ' ' . '(' . $customer->id . ')';
                    break;
                default:
                    $displayName = $customer->first_name . ' ' . $customer->last_name . ' ' . '(' . $customer->id . ')';
                    break;
            }
        }
        $dateTime = convertTimezone($customer->created_at, Settings::get('TIME_ZONE'));
        $createdDate = $dateTime->format('Y-m-d');

        $note = '';
		// QB not accpet the note having value greater than 2000 chars. then remove extra content
		if($customer->note) {
			$note = substr($customer->note , 0, 4000);
		}

        $customerEntity = [
            "BillAddr" => [
                "Line1" => $billingAddress->address,
                "Line2" => $billingAddress->address_line_1,
                "City" => $billingAddress->city ? $billingAddress->city : '',
                "Country" => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
                "CountrySubDivisionCode" => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
                "PostalCode" => $billingAddress->zip
            ],
            "Notes" => $note,
            "GivenName" => substr($firstName, 0, 25), // maximum of 25 char
            "FamilyName" => substr($lastName, 0, 25),
            "CompanyName" => substr($companyName, 0, 50),
            "DisplayName" => removeQBSpecialChars(substr($displayName, 0, 100)),
            "PrimaryEmailAddr" => [
                "Address" => $customer->email
            ],
            "MetaData" => [
                'CreateTime' => $createdDate
            ]
        ];

        $company = $customer->company;
        $countryCode = $company->country->code;

        foreach ($customer->phones as $phone) {
            $number = phoneNumberFormat($phone->number, $countryCode);
            switch ($phone->label) {
                case 'phone':
                    $customerEntity["PrimaryPhone"]["FreeFormNumber"] = $number;
                    break;

                case 'cell':
                    $customerEntity["Mobile"]["FreeFormNumber"] = $number;
                    break;

                case 'fax':
                    $customerEntity["Fax"]["FreeFormNumber"] = $number;
                    break;

                case 'other':
                    $customerEntity["AlternatePhone"]["FreeFormNumber"] = $number;
                    break;

                default:
                    if (!isset($customerEntity["AlternatePhone"]["FreeFormNumber"])) {
                        $customerEntity["AlternatePhone"]["FreeFormNumber"] = $number;
                    }
                    break;
            }
        }

        $customerEntity = array_filter($customerEntity);

        $customerEntity['Job'] = false;
        $customerEntity['BillWithParent'] = false;

        if (!empty($data)) {
            $customerEntity = array_merge($customerEntity, $data);
        }

        return $customerEntity;
    }

    public function mapJobData($job, $data = [])
    {
        $jobEntity = [];
        $customer = $job->customer;
        $displayName = $job->getQuickbookDisplayName();
        $dateTime = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));
        $createdDate = $dateTime->format('Y-m-d');
        $jobEntity = [
            'MetaData' => [
                'CreateTime' => $createdDate,
            ]
        ];

        $jobEntity['Job'] = true;
        $jobEntity['DisplayName'] = removeQBSpecialChars($displayName);
        $jobEntity['BillWithParent'] = true;
        $jobEntity['ParentRef']['value'] = $customer->quickbook_id;

        $billingAddress = $customer->billing;
        $jobEntity['GivenName'] = removeQBSpecialChars(substr($customer->getFirstName(), 0, 25)); // maximum of 25 char
        $jobEntity['FamilyName'] = removeQBSpecialChars(substr($customer->last_name, 0, 25));
        $jobEntity['CompanyName'] = substr($customer->getCompanyName(), 0, 25);
        $jobEntity['BillAddr'] = [
            'Line1' => $billingAddress->address,
            'Line2' => $billingAddress->address_line_1,
            'City' => $billingAddress->city ? $billingAddress->city : '',
            'Country' => isset($billingAddress->country->name) ? $billingAddress->country->name : '',
            'CountrySubDivisionCode' => isset($billingAddress->state->code) ? $billingAddress->state->code : '',
            'PostalCode' => $billingAddress->zip
        ];

        $jobEntity = array_filter($jobEntity);

        if (!empty($data)) {
            $jobEntity = array_merge($data, $jobEntity);
        }

        return $jobEntity;
    }

    /**
     * Check Data Exist On Quickbook
     * @param  Arary $param [query]
     * @return Array Of Response
     */
    private function dataExist($param)
    {

        $item = $this->client->request("/query", 'GET', $param);
        if (empty($item['QueryResponse'])) {
            return false;
        }

        return $item['QueryResponse'];
    }

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
     * Create Item
     * @param  String $itemName Item Name
     * @return Array
     */
    private function createItem($itemName)
    {
        $itemEntity = [
            'Name' => $itemName,
            'IncomeAccountRef' => [
                'value' => $this->getAccountId(),
            ],
            'Type' => 'Service'
        ];
        $item = $this->client->request("/item", 'POST', $itemEntity);

        return [
            'id' => $item['Item']['Id'],
            'name' => $item['Item']['Name']
        ];
    }

    /**
     * Get Account Id
     * @return Int Account Id
     */
    public function getAccountId()
    {
        $param = [
            'query' => "select * from Account where FullyQualifiedName = 'Services'"
        ];

        $queryResponse = $this->getDataByQuery($param);
        if (!$queryResponse) {
            $queryResponse = $this->createIncomeAccount();
            $accountId = $queryResponse['Account']['Id'];
        } else {
            $accountId = $queryResponse['Account'][0]['Id'];
        }

        return $accountId;
    }

    /**
     * Create Income Account
     * @return Account Response
     */
    private function createIncomeAccount()
    {
        $accoountMeta = [
            'AccountType' => 'Income',
            'Name' => 'Services'
        ];
        $account = $this->client->request("/account", 'POST', $accoountMeta);

        return $account;
    }

    /**
     * Find Item On Quickbook
     * @param  String $itemName ItemName
     * @return Array Of item
     */
    private function findItem($itemName)
    {
        $param = [
            'query' => "SELECT *  FROM item WHERE name = '" . $itemName . "'"
        ];
        $item = $this->client->request("/query", 'GET', $param);

        return [
            'id' => $item['QueryResponse']['Item'][0]['Id'],
            'name' => $item['QueryResponse']['Item'][0]['Name']
        ];
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
            Log::error('QuickBook: ' . json_encode($response));
            switch ((int)$response['Fault']['Error'][0]['code']) {
                case 6002:
                    throw new QuickBookException('Internet connection slow.');
                    break;

                //deprecated field
                case 6002:
                    break;

                //duplicate invoice number
                case 6140:
                    throw new QuickBookException('Something went wrong, Please try again.');
                    break;

                case 6000:
                    // throw new QuickBookException('An unexpected error occurred on quickbook. Please wait a few minutes and try again.');
                    throw new QuickBookException($response['Fault']['Error'][0]['Message']);
                    break;

                case 6190:
                    throw new QuickBookException('Quickbook subscription has ended or canceled.');
                    break;
                //batch size limit
                case 1040:
                    break;
                case 620:
                    throw new QuickBookException('Quickbook: Invoice cannot be linked.');
                    break;

                case 6210:
                case 6540:
                case 6190:
                    throw new QuickBookException("Quickbook Error: " . $response['Fault']['Error'][0]['Detail']);
                    break;

                default:
                    Log::error('QuickBook: ' . json_encode($response));
                    throw new QuickBookException(trans('response.error.something_wrong'));
                    break;
            }
        }

        Log::error('QuickBook: ' . json_encode($response));
        throw new QuickBookException('QuickBook: ' . trans('response.error.something_wrong'));
    }

    /**
     * Get Quickbook Invoice Data By Id
     * @param  Int $id Invoice Id
     * @return Array Of Invoice
     */
    private function getQuickbookInvoice($quickbookInvoiceId, $invoiceNumber)
    {
        $response = [];

        if ($quickbookInvoiceId) {
            $param = [
                'query' => "SELECT Id,SyncToken  FROM Invoice WHERE Id = '" . $quickbookInvoiceId . "'"
            ];
            $response = $this->getDataByQuery($param)['Invoice'][0];
        }

        if (!ine($response, 'Id')) {
            $param = [
                'query' => "SELECT Id, SyncToken  FROM Invoice WHERE DocNumber = '" . JobInvoice::QUICKBOOK_INVOICE_PREFIX . $invoiceNumber . "'"
            ];
            $response = $this->getDataByQuery($param)['Invoice'][0];
        }

        return $response;
    }

    /**
     * Get Quickbook Invoice Data By Id
     * @param  Int $id Invoice Id
     * @return Array Of Invoice
     */
    private function getQuickbookCreditMemo($quickbookCreditId)
    {
        $response = [];
        if($quickbookCreditId) {
            $param = [
                'query' => "SELECT Id,SyncToken  FROM CreditMemo WHERE Id = '".$quickbookCreditId."'"
            ];
            $response = $this->getDataByQuery($param)['CreditMemo'][0];
        }
        return $response;
    }

    /**
     * Update job financials
     * @param  string $token token
     * @param  Job $job job
     * @return boolean
     */
    private function updateJobFinancial($token, $job)
    {

        $credits = $job->credits()->where(function ($query) {
            $query->where('quickbook_id', '=', '')->orWhereNull('quickbook_id');
        })->get();

        $jobQuickbookId = $job->quickbook_id;
        $paymentIds = $job->payments()->whereNull('quickbook_id')
            ->whereNull('canceled')
            ->pluck('job_payments.id');
        if ($credits->count()) {
            $description = $this->getDefaultJobDesc($job);
            foreach ($credits as $credit) {
                $this->createCreditNote($token, $credit, $description, $jobQuickbookId);
            }
        }

        if (!empty($paymentIds)) {
            $referenceId = $job->customer->quickbook_id;
            $this->paymentsSync($token, $paymentIds, $referenceId);
        }
    }

    /**
     * Update job financial with invoice
     * @param  string $token token
     * @param  instance $job job
     * @return boolean
     */
    private function updateJobFinancialWithInvoice($token, $job)
    {
        $this->updateJobFinancial($token, $job);
        $paymentIds = [];
        $invoices = $job->invoices()->whereNull('quickbook_invoice_id')->get();

        if ($invoices->count()) {
            foreach ($invoices as $invoice) {
                $this->createOrUpdateInvoice($token, $invoice);

                $pIds = $invoice->jobPayments()->whereNull('quickbook_id')
                    ->select('job_payments.id')
                    ->pluck('id')->toArray();

                $paymentIds = array_merge($paymentIds, $pIds);
            }
        }

        if (!empty($paymentIds)) {
            $referenceId = $job->customer->quickbook_id;
            $this->paymentsSync($token, $paymentIds, $referenceId);
        }

        return true;
    }

    private function getDefaultJobDesc($job)
    {
        $trades = $job->trades->pluck('name')->toArray();
        $description = $job->number;
        // Append Other trade type decription if 'Other' trade is associated..
        if (in_array('OTHER', $trades) && ($job->other_trade_type_description)) {
            $otherKey = array_search('OTHER', $trades);
            unset($trades[$otherKey]);
            $other = 'OTHER - ' . $job->other_trade_type_description;
            array_push($trades, $other);
        }

        if ($trade = implode(', ', $trades)) {
            $description .= ' / ' . $trade;
        }

        return $description;
    }

    /**
     * @param  Instance $quickbook Quickbook Instance
     * @return boolean
     */
    // private function saveQuickbookMetaData($quickbook){
    // 	$context = App::make(\App\Services\Contexts\Context::class);
    // 	$context->set($company = Company::find($quickbook->company_id));
    // 	$token = $this->token();

    // 	if(!$token) return false;
    // 	\QuickbookMeta::whereQuickbookId($quickbook->id)->delete();

    // 	$this->findOrCreateItem($token);
    // 	$paymentMethods = ['Other', 'Cash', 'Credit Card', 'Paypal', 'Check'];
    // 	foreach ($paymentMethods as $method) {
    // 		$this->getPaymentReference($token, $method);
    // 	}
    // }
}
