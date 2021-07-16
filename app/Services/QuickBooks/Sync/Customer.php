<?php
namespace App\Services\QuickBooks\Sync;

use Exception;
use App\Models\QuickBook;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\CustomerRepository;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use Carbon\Carbon;
use App\Models\QuickbookSyncCustomer;
use App\Models\QuickbookSyncInvoice;
use App\Models\QuickbookSyncJob;
use App\Models\QuickbookSyncCreditMemo;
use App\Models\QBOCustomer;
use App\Models\Customer as CustomerModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\QuickbookSyncBatch;
use App\Services\QuickBooks\Entity\V2\Invoice;
use App\Services\QuickBooks\Entity\V2\Payment;
use App\Services\QuickBooks\Entity\V2\Credit;

class Customer
{
    const ACTION_CREATE_IN_QUICKBOOKS = 'CreateInQuickBook';
    const ACTION_CREATE_IN_JOBPROGRESS = 'CreateInJobProgress';
    const ACTION_MERGE = 'Merge';
    const STATUS_PENDING = 'Pending';
    const STATUS_INPROGRESS = 'InProgress';
    const STATUS_SUCCESS = 'Success';
    const STATUS_ERROR = 'Error';

    public function __construct(CustomerRepository $customerRepo)
	{
		$this->customerRepo = $customerRepo;
	}

    private function setContext($companyId)
    {
        $quickbook = QuickBook::whereCompanyId($companyId)->first();

        if($quickbook) {

            QuickBooks::setCompanyScope($quickbook->quickbook_id);

            return true;
        }

        throw new QuickBookException('Quickbook not connected');
    }

    /**
     * While syncing process to get total number of active users
     */
    public function getTotalCount($companyId)
    {
        try {

            $quickbook = QuickBook::whereCompanyId($companyId)->first();

            if($quickbook) {

                QuickBooks::setCompanyScope($quickbook->quickbook_id);

                if(!QuickBooks::isConnected()) {

                    throw new Exception('Quickbook not connected');
                }
                return QuickBooks::getDataByQuery('Select count(*) from Customer where Active = TRUE and Job = FALSE');

            } else {

                throw new Exception('Quickbook not connected');
            }

        } catch (Exception $e) {

            throw new Exception($e);
        }
    }

    public function getAllCustomers($companyId)
    {
        try {

            $this->setContext($companyId);
            $start = 1;
            $limit = 1000;
            $fetch = true;
            $customers = [];
            while($fetch) {
                $response = QuickBooks::getDataByQuery("SELECT *  FROM Customer WHERE Active = TRUE STARTPOSITION {$start} MAXRESULTS {$limit}");
                $start = $start + $limit;

                if(empty($response)) {
                    $fetch = false;
                    break;
                }
                foreach($response as $key => $qbCustomer) {
                    $customers[] = QuickBooks::toArray($qbCustomer);
                }
            }

            Log::info('All customer fetched');

            $invoices = App::make(Invoice::class)->getAll();
            $invoice_count_map = [];
            foreach($invoices as $invoice){
                $customer_id = $invoice->CustomerRef;
                if(isset($invoice_count_map[$customer_id])){
                    $invoice_count_map[$customer_id] = $invoice_count_map[$customer_id] + 1;
                }else{
                    $invoice_count_map[$customer_id] = 1;
                }
            }
            Log::info('All invoices fetched');

            $payments = App::make(Payment::class)->getAll();
            $payment_count_map = [];
            foreach($payments as $payment){
                $customer_id = $payment->CustomerRef;
                if(isset($payment_count_map[$customer_id])){
                    $payment_count_map[$customer_id] = $payment_count_map[$customer_id] + 1;
                }else{
                    $payment_count_map[$customer_id] = 1;
                }
            }
            Log::info('All payments fetched');
            $credits = App::make(Credit::class)->getAll();
            $credit_count_map = [];
            foreach($credits as $credit){
                $customer_id = $credit->CustomerRef;
                if(isset($credit_count_map[$customer_id])){
                    $credit_count_map[$customer_id] = $credit_count_map[$customer_id] + 1;
                }else{
                    $credit_count_map[$customer_id] = 1;
                }
            }
            Log::info('All credits fetched');

            foreach ($customers as $key => $customer) {

                $customer_id = $customer['Id'];
                $stats['TotalInvoiceCount'] = isset($invoice_count_map[$customer_id]) ? $invoice_count_map[$customer_id] : 0;
                $stats['TotalPaymentCount'] = isset($payment_count_map[$customer_id]) ? $payment_count_map[$customer_id] : 0;
                $stats['TotalCreditCount'] = isset($credit_count_map[$customer_id]) ? $credit_count_map[$customer_id] : 0;

                $customers[$key] = array_merge($customer, $stats);
            }
            Log::info('All customer financial fetched');

            return $customers;

        } catch (Exception $e) {
            Log::info('Get Customer All Financials Exception');
            Log::info($e);

            throw new Exception($e);
        }
    }


    public function storeCustomerJobs($companyId, $batchId)
    {
        try {

            $batchCustomers = QuickbookSyncCustomer::where('batch_id',  $batchId)
                ->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
                ->count('object_id');

            if(!$batchCustomers) return;

            $this->setContext($companyId);
            $start = 1;
            $limit = 500;
            $fetch = true;

            while($fetch) {

                $response = QuickBooks::getDataByQuery("SELECT *  FROM Customer WHERE Active = TRUE and Job = True order by Id ASC STARTPOSITION {$start} MAXRESULTS {$limit}");

                $start = $start + $limit;
                if(empty($response)) {
                    $fetch = false;
                    break;
                }

                $jobMeta = [];
                foreach($response as $key => $job) {
                    $parentJobCustomerIds[$job->Id] = $job->ParentRef;

                    $jobMeta[$key] = [
                        'batch_id'      => $batchId,
                        'company_id'    => getScopeId(),
                        'object_id'     => $job->Id,
                        'meta'          => json_encode(QuickBooks::toArray($job)),
                        'status'        => self::STATUS_PENDING,
                        'action'        => self::ACTION_CREATE_IN_JOBPROGRESS,
                        'created_by'    => Auth::id(),
                        'created_at'    => Carbon::now()->toDateTimeString(),
                        'updated_at'    => Carbon::now()->toDateTimeString(),
                        'qb_customer_id' => $job->ParentRef,
                        'parent_id'      => null,
                        'is_project'     => 0,
                    ];

                    if($job->Level == 2) {
                        $jobMeta[$key]['parent_id'] = $job->ParentRef;
                        $jobMeta[$key]['is_project'] = 1;
                        $jobMeta[$key]['qb_customer_id'] = issetRetrun($parentJobCustomerIds, $job->ParentRef) ?: null;
                    }
                }

                if($jobMeta) {
                    QuickbookSyncJob::insert($jobMeta);
                }
            }

            $this->markMultiJob();

        } catch (Exception $e) {

            throw new Exception($e);
        }
    }

    public function markMultiJob()
    {
        $projects = QuickbookSyncJob::where('is_project', 1)
            ->get();

        if($projects->isEmpty()) return;

        foreach($projects as $project)  {
            $parentIds[] = $project->parent_id;
        }

        $parentIds = array_unique($parentIds);
        if(!empty($parentIds)) {
            QuickbookSyncJob::whereIn('object_id', $parentIds)
                ->update([
                    'multi_job' => true
                ]);
        }
    }

    public function mappingCustomers($batch)
    {
        // QBCustomer::import($batch->company_id);
        DB::table('quickbook_sync_customers')->where('batch_id', $batch->id)->delete();

        $this->matchingCustomers($batch);
        $this->mappingJpToQb($batch);
        $this->mappingQbToJp($batch);
        $this->actionRequiredCustomers($batch);
    }

    /**
     * store customers from JobProgress to Quickbooks
     * @param  Integer | $companyId         | Company Id
     * @param  Integer | $batchId           | Batch Id of customers sync
     * @param  Blloean | $checkCustomerIds  | If true then check customers by ids
     * @param  Array   | $customerIds       | Array of Customer Ids
     * @return
     */
    public function mappingJpToQb($batch)
    {
        try {
            $companyId = $batch->company_id;
            $batchId   = $batch->id;
            if($batch->connection_type == QuickbookSyncBatch::QBD){
                setScopeId($batch->company_id);
            }else{
                $this->setContext($companyId);

            }

            if(($batch->sync_action == 'other')
                 && in_array($batch->sync_scope, ['qb_to_jp'])) return;

            Log::info('JP To QB mapping started', [$batch->id]);
            if($batch->connection_type == QuickbookSyncBatch::QBD){
                $customers = CustomerModel::whereNull('qb_desktop_id');

            }else{
                $customers = CustomerModel::whereNull('quickbook_id');
            }

            $customers = $customers->where('disable_qbo_sync', false)
                ->with(['phones'])
                ->whereNotIn('id', function($query) use($batch){
                    $query->select('customer_id')->from('quickbook_sync_customers')
                        ->where('batch_id', $batch->id);
                })->where('company_id', $companyId);

            if($batch->sync_action == 'other' && $batch->sync_request_meta['duration'] != 'since_inception') {
                $customers->whereBetween('updated_at', [
                    $batch->sync_request_meta['start_date'],
                    $batch->sync_request_meta['end_date']
                ]);
            } elseif($batch->sync_action == 'customers') {
                $customers->whereIn('customers.id', $batch->selectedCustomers->pluck('id')->toArray());
            }

            if($batch->connection_type == QuickbookSyncBatch::QBD){
               $customerIds = $customers->pluck('qb_desktop_id', 'id')->toArray();

            }else{
                $customerIds = $customers->pluck('quickbook_id', 'id')->toArray();
            }

            $qbCustomer = [];
            $currentDateTime = Carbon::now()->toDateTimeString();

            foreach (array_keys($customerIds) as $customerId) {
                $qbCustomer[] = [
                    'customer_id' => $customerId,
                    'origin' => 'jp',
                    'batch_id' => $batch->id,
                    'company_id' => $batch->company_id,
                    'created_at' => $currentDateTime,
                ];
            }

            if(empty($qbCustomer)) return true;

            DB::table('quickbook_sync_customers')->insert($qbCustomer);

            // $qbCustomerids = [];
            // foreach (arry_fu($customerIds) as $key => $customerId) {
            //     $qbCustomerids[] = $key;
            // }

            // if(($batch->sync_action == 'customers') && !empty($qbCustomerids)) {
            //     DB::table('quickbook_sync_customers')->where('batch_id', $batch->id)
            //         ->whereIn('customer_id', $qbCustomerids)
            //         ->update(['action_required' => true]);
            // }

            Log::info('JP To QB mapping finished', [$batch->id]);

        } catch (Exception $e) {

            Log::info('JP To QB mapping exception', [$batch->id]);
            throw $e;
        }
    }

    public function mappingQBToJp($batch)
    {
        try {
            $isQBD = ($batch->connection_type == QuickbookSyncBatch::QBD);
            if($batch->sync_action == 'customers') return;

            if(($batch->sync_action == 'other')
                 && in_array($batch->sync_scope, ['jp_to_qb'])) return;

            $companyId = $batch->company_id;
            if($batch->connection_type == QuickbookSyncBatch::QBD){
                setScopeId($companyId);
            }else{
                $this->setContext($companyId);

            }
            $qbToJpIds = [];

            Log::info('QB To JP mapping started', [$batch->id]);

            $qbdCustomer = QBOCustomer::where('qbo_customers.company_id', getScopeId())
                ->excludeMappedCustomers($isQBD)
                ->whereNotIn('qb_id', function($query) use ($batch){
                    $query->select('qb_id')->from('quickbook_sync_customers')
                        ->whereNotNull('qb_id')
                        ->where('batch_id', $batch->id);
              })
                ->excludeSubCustomer();
                // ->valid() include dirty records in sync manager
            if($batch->sync_action == 'other' && $batch->sync_request_meta['duration'] != 'since_inception'){
                $qbdCustomer->whereBetween('qb_modified_date', [
                    $batch->sync_request_meta['start_date'], 
                    $batch->sync_request_meta['end_date']
                ]);
            }
            $customerIds = $qbdCustomer->pluck('qb_id')->toArray();
            $qbCustomerids = [];
            $currentDateTime = Carbon::now()->toDateTimeString();
            $customerIds = arry_fu($customerIds);
            foreach ($customerIds as $customerId) {
                $qbCustomerids[] = [
                    'qb_id'  => $customerId,
                    'origin' => 'qb',
                    'company_id' => getScopeId(),
                    'batch_id' => $batch->id,
                    'created_at' => $currentDateTime,
                ];
            }
            if(empty($customerIds)) return [];

            DB::table('quickbook_sync_customers')->insert($qbCustomerids);
            Log::info('QB To JP mapping finished', [$batch->id]);
        } catch (Exception $e) {

            Log::info('QB To JP mapping exception', [$batch->id]);
            throw new Exception($e);
        }
    }


    public function storeQuickBookPayments($companyId)
    {
        try {

            $this->setContext($companyId);

            $start = 1;

            $limit = 500;

            $fetch = true;

            $excludeIds = DB::table('quickbook_sync_payments')
                ->where('company_id', getScopeId())
                ->where('action', self::ACTION_CREATE_IN_JOBPROGRESS)
                ->select('object_id')
                ->get()
                ->toArray();;

            if(!empty($excludeIds)) {

                $excludeIds = array_fetch($excludeIds, 'object_id');

                $excludeIds = array_unique($excludeIds);
            }

            while($fetch) {

                $response = QuickBooks::getDataByQuery("SELECT *  FROM Payment ORDER BY Id ASC STARTPOSITION {$start} MAXRESULTS {$limit}");

                $start = $start + $limit;

                if(empty($response)) {

                    $fetch = false;
                    break;
                }

                foreach($response as $payment) {

                    $payment = QuickBooks::toArray($payment);

                    $mapInput = [
                        'quickbook_customer_id' => $payment['CustomerRef'],
                        'payment' => $payment['TotalAmt'],
                        'unapplied_amount' => $payment['UnappliedAmt'],
                        'company_id' => getScopeId(),
                        'object_id' => $payment['Id'],
                        'meta' => json_encode($payment),
                        'status' => self::STATUS_PENDING,
                        'action' => self::ACTION_CREATE_IN_JOBPROGRESS,
                        'created_by' => Auth::user()->id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    // Exclude 0 payment with no lines and already added payments
                    if(!isset($payment['Line'])
                        && $mapInput['payment'] == 0
                        && $mapInput['unapplied_amount'] == 0
                        || in_array($payment['Id'], $excludeIds)) {

                        continue;
                    }

                    DB::table("quickbook_sync_payments")
                        ->insert($mapInput);
                }
            }

        } catch (Exception $e) {

            throw new Exception($e);
        }
    }

    public function storeQuickBookInvoices($companyId, $batchId)
    {
        try {
            $batchJobs = QuickbookSyncJob::where('batch_id',  $batchId)
                ->where('company_id', getScopeId())
                ->pluck('id', 'object_id')
                ->toArray();

            if(empty($batchJobs)) return;

            $this->setContext($companyId);

            $excludeIds = QuickbookSyncInvoice::where('company_id', getScopeId())
                ->where('action', self::ACTION_CREATE_IN_JOBPROGRESS)
                ->select('object_id')
                ->get()
                ->toArray();;

            if(!empty($excludeIds)) {
                $excludeIds = array_fetch($excludeIds, 'object_id');
                $excludeIds = array_unique($excludeIds);
            }

            // add comma and single qoutes with all customer ids
            $customerRefs = '';
            if(array_keys($batchJobs)) {
                $customerRefs = "'".implode("', '", array_keys($batchJobs))."'";
            }

            $start = 1;
            $limit = 500;
            $fetch = true;

            while($fetch) {

                $response = QuickBooks::getDataByQuery("SELECT *  FROM Invoice WHERE CustomerRef IN ({$customerRefs}) ORDER BY Id ASC STARTPOSITION {$start} MAXRESULTS {$limit}");

                $start = $start + $limit;
                if(empty($response)) {

                    $fetch = false;
                    break;
                }

                $invoiceData = [];
                foreach($response as $key => $invoice) {

                    $invoice = QuickBooks::toArray($invoice);

                    // Exclude already added
                    if(in_array($invoice['Id'], $excludeIds)) {
                        continue;
                    }

                    $invoiceData[$key] = [
                        'batch_id'              => $batchId,
                        'payment'               => $invoice['TotalAmt'],
                        'balance'               => $invoice['Balance'],
                        'company_id'            => getScopeId(),
                        'object_id'             => $invoice['Id'],
                        'meta'                  => json_encode($invoice),
                        'status'                => self::STATUS_PENDING,
                        'action'                => self::ACTION_CREATE_IN_JOBPROGRESS,
                        'created_by'            => Auth::id(),
                        'created_at'            => Carbon::now()->toDateTimeString(),
                        'updated_at'            => Carbon::now()->toDateTimeString(),
                        'quickbook_customer_id' => $invoice['CustomerRef'],
                    ];
                }

                if($invoiceData) {
                    QuickbookSyncInvoice::insert($invoiceData);
                }
            }
        } catch (Exception $e) {

            throw new Exception($e);
        }
    }

    public function storeQuickBookCreditMemo($companyId, $batchId)
    {
        try {

            $batchJobs = QuickbookSyncJob::where('batch_id',  $batchId)
                ->where('company_id', getScopeId())
                ->count();

            if(!$batchJobs) return;

            $this->setContext($companyId);

            $excludeIds = QuickbookSyncCreditMemo::where('company_id', getScopeId())
                ->where('action', self::ACTION_CREATE_IN_JOBPROGRESS)
                ->select('object_id')
                ->get()
                ->toArray();
            if(!empty($excludeIds)) {
                $excludeIds = array_fetch($excludeIds, 'object_id');
                $excludeIds = array_unique($excludeIds);
            }

            $start = 1;
            $limit = 500;
            $fetch = true;

            while($fetch) {

                $response = QuickBooks::getDataByQuery("SELECT *  FROM CreditMemo ORDER BY Id ASC STARTPOSITION {$start} MAXRESULTS {$limit}");

                $start = $start + $limit;
                if(empty($response)) {
                    $fetch = false;
                    break;
                }

                $creditData = [];
                foreach($response as $key => $creditMemo) {

                    $creditMemo = QuickBooks::toArray($creditMemo);

                    // Exclude already added
                    if(in_array($creditMemo['Id'], $excludeIds)) {
                        continue;
                    }

                    $creditData[$key] = [
                        'batch_id'              => $batchId,
                        'amount'                => $creditMemo['TotalAmt'],
                        'balance'               => $creditMemo['Balance'],
                        'company_id'            => getScopeId(),
                        'object_id'             => $creditMemo['Id'],
                        'meta'                  => json_encode($creditMemo),
                        'status'                => self::STATUS_PENDING,
                        'action'                => self::ACTION_CREATE_IN_JOBPROGRESS,
                        'created_by'            => Auth::id(),
                        'created_at'            => Carbon::now()->toDateTimeString(),
                        'updated_at'            => Carbon::now()->toDateTimeString(),
                        'quickbook_customer_id' => $creditMemo['CustomerRef'],
                    ];
                }

                if($creditData) {
                    QuickbookSyncCreditMemo::insert($creditData);
                }
            }
        } catch (Exception $e) {

            throw new Exception($e);
        }
    }

    private function matchingCustomers($batch)
    {
         try {
            $companyId = $batch->company_id;
            $batchId   = $batch->id;
            $isQBD = ($batch->connection_type == QuickbookSyncBatch::QBD);
            $matchingColumn = 'quickbook_id';
            if($batch->connection_type == QuickbookSyncBatch::QBD){
                setScopeId($companyId);
            }else{
                $this->setContext($companyId);

            }

            if(($batch->sync_action == 'other') 
                 && in_array($batch->sync_scope, ['qb_to_jp'])) return;

            Log::info('Matching customers mapping started', [$batch->id]);

            if($isQBD){
                $matchingColumn = 'qb_desktop_id';
            }
            $customers = CustomerModel::whereNull($matchingColumn)
                ->with(['phones'])
                ->where('customers.company_id', $companyId)
                ->whereNotIn('id', function($query) use($batch){
                    $query->select('customer_id')->from('quickbook_sync_customers')
                        ->where('batch_id', $batch->id);
                });

            if($batch->sync_action == 'other' && $batch->sync_request_meta['duration'] != 'since_inception') {
                $customers->whereBetween('updated_at', [
                    $batch->sync_request_meta['start_date'],
                    $batch->sync_request_meta['end_date']
                ]);
            } elseif($batch->sync_action == 'customers') {
                $customers->whereIn('customers.id', $batch->selectedCustomers->pluck('id')->toArray());
            }

            $customers->where('disable_qbo_sync', false);
            
            $jpToQBIds = [];
            $customers->chunk(100, function($customers) use (&$jpToQBIds, $batch, $isQBD){
                foreach ($customers as $customer) {
                    $phones = $customer->phones->pluck('number')->toArray();
                    $companyName = null;
                    if($customer->is_commercial) {
                        $companyName = $customer->first_name;
                    }
                    $qboCustomer = QBOCustomer::findMatchingCustomer($phones, 
                        $customer->email, 
                        $customer->full_name, 
                        $companyName, 
                        $batch->id,
                        $isQBD
                    );
                    if(!$qboCustomer) continue;
                    $currentDateTime = Carbon::now()->toDateTimeString();
                    $jpToQBIds = [
                        'batch_id'   => $batch->id,
                        'company_id' => $batch->company_id,
                        'qb_id'      => $qboCustomer->qb_id,
                        'customer_id' => $customer->id,
                        'origin' => 'qb',
                        'created_at' => $currentDateTime,
                    ];
                    DB::table('quickbook_sync_customers')->insert($jpToQBIds);
                }
            });
            Log::info('Matching customers mapping finished', [$batch->id]);

        } catch (Exception $e) {
            Log::info('Matching customers mapping exception', [$batch->id]);
            throw $e;
        }
    }

    private function actionRequiredCustomers($batch)
    {
        try {

            if(($batch->sync_action == 'other') && in_array($batch->sync_scope, ['qb_to_jp'])) {

                return;
            }

            $companyId = $batch->company_id;
            $batchId   = $batch->id;
            if($batch->connection_type == QuickbookSyncBatch::QBD){
                setScopeId($companyId);
            }else{
                $this->setContext($companyId);

            }
            $isQBD = ($batch->connection_type == QuickbookSyncBatch::QBD);
            $matchingColumn = 'customers.quickbook_id';
            if($isQBD){
                $matchingColumn = 'customers.qb_desktop_id';
            }

            Log::info('Action Required customers mapping started', [$batch->id]);

            $customers = CustomerModel::whereNotNull($matchingColumn)
                ->with(['phones', 'qbCustomer'])
                ->where('customers.company_id', $companyId)
                ->where('disable_qbo_sync', false)
                ->whereNotIn('customers.id', function($query) use($batch){
                    $query->select('customer_id')
                        ->from('quickbook_sync_customers')
                        ->whereNotNull('qb_id')
                        ->where('action_required', true)
                        ->where('batch_id', $batch->id);
                });

            if($batch->sync_action == 'other' && $batch->sync_request_meta['duration'] != 'since_inception') {
                $customers->whereBetween('customers.updated_at', [
                    $batch->sync_request_meta['start_date'],
                    $batch->sync_request_meta['end_date']
                ]);
            } elseif($batch->sync_action == 'customers') {
                $customers->whereIn('customers.id', $batch->selectedCustomers->pluck('id')->toArray());
            }

            $customers->leftJoin('jobs', function($query){
                $query->on('jobs.customer_id', '=', 'customers.id')
                    ->whereNull('jobs.archived')
                    ->whereNull('jobs.deleted_at');
            });

            $customers->leftJoin('job_credits', function($query){
                $query->on('jobs.id', '=', 'job_credits.job_id')
                    ->whereNull('job_credits.ref_id')
                    ->whereNull('job_credits.canceled')
                    ->whereNull('job_credits.deleted_at');
            });

            $customers->leftJoin('job_invoices', function($query){
                $query->on('jobs.id', '=', 'job_invoices.job_id')
                    ->whereNull('job_invoices.deleted_at');
            });

            $customers->leftJoin('job_payments', function($query){
                $query->on('jobs.id', '=', 'job_payments.job_id')
                    ->whereNull('job_payments.ref_id')
                    ->whereNull('job_payments.canceled')
                    ->whereNull('job_payments.deleted_at');
            });

            $customers->selectRaw("customers.*,
                COUNT(DISTINCT(job_credits.id)) AS total_credit_count,
                COUNT(DISTINCT(job_invoices.id)) AS total_invoice_count,
                COUNT(DISTINCT(job_payments.id)) AS total_payment_count
            ");

            $customers->groupBy('customers.id');
            $customers = $customers->get();

            $customerData = [];
            foreach ($customers as $customer) {

                $qbCustomer = $customer->qbCustomer;
                if($isQBD){
                    $qbCustomer = $customer->qbdCustomer;
                }

                if(!$qbCustomer) continue;

                $qbFinancials = $qbCustomer->qbJobs()
                    ->leftJoin('qbo_customers as qbo_project_jobs', function($join) use($customer) {
                        $join->on('qbo_project_jobs.qb_parent_id', '=', 'qbo_customers.qb_id')
                            ->where('qbo_project_jobs.company_id', '=', $customer->company_id);
                    })
                    ->selectRaw("
                        qbo_customers.*,
                        SUM(COALESCE(qbo_customers.total_invoice_count,0) + COALESCE(qbo_project_jobs.total_invoice_count, 0)) as total_invoice_count,

                        SUM(COALESCE(qbo_customers.total_payment_count,0) + COALESCE(qbo_project_jobs.total_payment_count, 0)) as total_payment_count,

                        SUM(COALESCE(qbo_customers.total_credit_count,0) + COALESCE(qbo_project_jobs.total_credit_count, 0)) as total_credit_count
                    ")
                    ->groupBy('qbo_customers.qb_parent_id')
                    ->first();

                $creditCount  = $qbCustomer->total_credit_count;
                $invoiceCount = $qbCustomer->total_invoice_count;
                $paymentCount = $qbCustomer->total_payment_count;

                if($qbFinancials) {
                    $creditCount  += $qbFinancials->total_credit_count;
                    $invoiceCount += $qbFinancials->total_invoice_count;
                    $paymentCount += $qbFinancials->total_payment_count;
                }

                // ignore if all financial counts of a customer are equal
                if(($customer->total_credit_count == $creditCount)
                    && ($customer->total_invoice_count == $invoiceCount)
                    && ($customer->total_payment_count == $paymentCount)) {

                    continue;
                }

                $currentDateTime = Carbon::now()->toDateTimeString();
                $qbId = $customer->quickbook_id;
                if($isQBD){
                    $qbId = $customer->qb_desktop_id;
                }
                $customerData[] = [
                    'batch_id'          => $batch->id,
                    'company_id'        => $batch->company_id,
                    'qb_id'             => $qbId,
                    'customer_id'       => $customer->id,
                    'origin'            => 'jp',
                    'action_required'   => true,
                    'created_at' => $currentDateTime,
                ];
            }

            if($customerData) {
                DB::table('quickbook_sync_customers')->insert($customerData);
            }
            Log::info('Action Required customers mapping finished', [$batch->id]);
        } catch (Exception $e) {
            Log::info('Action Required customers mapping exception', [$batch->id]);
            throw $e;
        }
    }
}
