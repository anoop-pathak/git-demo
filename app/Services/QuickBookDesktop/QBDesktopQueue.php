<?php

namespace App\Services\QuickBookDesktop;

use App\Models\Job;
use App\Models\Customer;
use App\Models\JobCredit;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Models\QBDesktopUser;
use App\Models\InvoicePayment;
use QuickBooks_WebConnector_Queue;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Models\FinancialProduct;
use Log;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\SyncTask;
use App\Models\QBDUnitOfMeasurement;
use App\Models\Vendor;
use App\Models\FinancialAccount;

class QBDesktopQueue extends QBDesktopUtilities
{

    protected $isConnected = false;
    protected $userName = null;

    /**
     * Add Account
     * @param Id $id int
     * @param void
     */
    public function addAccount($id, $userName = null, $extraParams = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_ADD_ACCOUNT, $id, QBDesktopUtilities::
        QB_ADD_ACCOUNT_PRIORITY,  $extraParams, $this->userName);
    }

    /**
     * Query Account
     * @param  int $id int
     * @param  username $userName username
     * @return void
     */
    public function queryAccount($id, $userName = null, $extraParams = array())
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_ACCOUNT, $id, QBDesktopUtilities::
        QB_QUERY_ACCOUNT_PRIORITY,  $extraParams, $this->userName);
    }

    public function addCategory($category, $userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$extraParams['is_category'] = true;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_ADD_ACCOUNT, $category->id, QBDesktopUtilities::
            QB_ADD_ACCOUNT_PRIORITY, $extraParams, $this->userName);
    }

    /**
     * Add payment method
     * @param  int $id int
     * @param  username $userName username
     * @return void
     */
    public function addPaymentMethod($id, $userName = null, $extraParams = array())
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_ADD_PAYMENTMETHOD, $id, QBDesktopUtilities::QB_ADD_PAYMENT_METHOD_PRIORITY, $extraParams, $this->userName);
    }

    /**
     * Add multiple payments
     * @param array $ids int
     * @param int $customerId int
     * @param void
     */
    public function addMultiplePayment(&$ids = [], &$job, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        if (!$job->qb_desktop_id) {
            self::addJob($job->id);
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

        $invoiceIds = InvoicePayment::whereIn('payment_id', $ids)->pluck('invoice_id')->toArray();
        $unSyncInvoiceIds = JobInvoice::whereNull('qb_desktop_id')
            ->whereQbDesktopDelete(false)
            ->whereIn('id', $invoiceIds)
            ->pluck('id')->toArray();

        foreach ($unSyncInvoiceIds as $invoiceId) {
            $queue->enqueue(QUICKBOOKS_ADD_INVOICE, $invoiceId, QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY, null, $this->userName);
        }

        foreach ($ids as $id) {
            $queue->enqueue(QUICKBOOKS_ADD_RECEIVEPAYMENT, $id, QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY, null, $this->userName);
        }
    }

    /**
     * Add Invoice
     * @param  int $id int
     * @param  username $userName username
     * @return void
     */
    public function addInvoice($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $invoice = JobInvoice::find($id);

        if ($invoice->qb_desktop_delete) {
            return null;
        }

        $job = $invoice->job;
        $customer = $invoice->customer;

        if ($customer->qb_desktop_delete) {
            return null;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

        if(!$customer->qb_desktop_id) {
            self::addCustomer($customer->id, $this->userName);
        } else {
            $queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $customer->id, QBDesktopUtilities::QB_QUERY_CUSTOMER_PRIORITY, [], $this->userName);
        }

        if ($job->qb_desktop_delete) {
            return null;
        }

        if($job->isProject()) {
            $parent = $job->parentJob;

            if($parent->qb_desktop_delete) return null;

            if(!$job->qb_desktop_id) self::addJob($parent->id);
        }

        if(!$job->qb_desktop_id) self::addJob($job->id);

        $queue->enqueue(QUICKBOOKS_ADD_INVOICE, $invoice->id, QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY, null, $this->userName);
    }

    /**
     * Delete Credit Memo
     * @param  int $id int
     * @param  username $userName username
     * @return void
     */
    public function deleteCreditMemo($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $jobCredit = JobCredit::find($id);

        if ($jobCredit->qb_desktop_delete
            || !$jobCredit->qb_desktop_txn_id) {
            return null;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_DERIVE_CREDITMEMO, $jobCredit->id, QBDesktopUtilities::QB_DERIVE_CREDITMEMO_PRIORITY, null, $this->userName);
    }

    /**
     * Delete Invoice
     * @param  int $id int
     * @param  username $userName username
     * @return void
     */
    public function deleteInvoice($ID, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_DERIVE_INVOICE, $ID, QBDesktopUtilities::QB_DERIVE_INVOICE_PRIORITY, null, $this->userName);
    }

    /**
     * Add Credit Memo
     * @param int $id id
     * @param string $userName
     */
    public function addCreditMemo($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $jobCredit = JobCredit::find($id);

        if (!$jobCredit) {
            return false;
        }

        if ($jobCredit->qb_desktop_delete) {
            return null;
        }

        $job = $jobCredit->job;
        $customer = $jobCredit->customer;

        if ($customer->qb_desktop_delete) {
            return null;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

        if(!$customer->qb_desktop_id) {
            self::addCustomer($id, $this->userName);
        } else {
            $queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $customer->id, QBDesktopUtilities::QB_QUERY_CUSTOMER_PRIORITY, [], $this->userName);
        }

        if ($job->qb_desktop_delete) {
            return null;
        }

        if($job->isProject()) {
            $parent = $job->parentJob;

            if($parent->qb_desktop_delete) return null;

            if(!$job->qb_desktop_id) {
                self::addJob($parent->id, $this->userName);
            }  else {
                $queue->enqueue(QUICKBOOKS_QUERY_JOB, $parent->id, QBDesktopUtilities::QB_QUERY_JOB_PRIORITY, [], $this->userName);
            }
        }

        if(!$job->qb_desktop_id) {
            self::addJob($job->id, $this->userName);
        }  else {
            $queue->enqueue(QUICKBOOKS_QUERY_JOB, $job->id, QBDesktopUtilities::QB_QUERY_JOB_PRIORITY, [], $this->userName);
        }

        $queue->enqueue(QUICKBOOKS_ADD_CREDITMEMO, $jobCredit->id, QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY, null, $this->userName);
    }

    /**
     * Query Credit Memo
     * @param  int $id ID
     * @param  string $username username
     * @return void
     */
    public function queryCreditMemo($id, $username)
    {
        if (!self::isAccountConnected($username)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_CREDITMEMO, $id, QBDesktopUtilities::QB_QUERY_INVOICE_PRIORITY, null, $this->userName);
    }

    /**
     * Query Invoice
     * @param  int $id Invoice Id
     * @param  string $userName username
     * @param  array $extraParam extra param
     * @return void
     */
    public function queryInvoice($id, $userName = null, $extraParam = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $jobInvoice = JobInvoice::find($id);
        if($jobInvoice->qb_desktop_delete
            || !$jobInvoice->qb_desktop_txn_id) return null;

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_INVOICE, $id, QBDesktopUtilities::QB_QUERY_INVOICE_PRIORITY, $extraParam, $this->userName);
    }

    /**
     * Add Payment
     * @param  int $id Payment Id
     * @param  string $userName username
     * @return void
     */
    public function addPayment($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $jobPayment = JobPayment::where('id', $id)->first();
        $customer = $jobPayment->customer;

        if ($customer->qb_desktop_delete) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

        if(!$customer->qb_desktop_id) {
            self::addCustomer($customer->id);
        }  else {
            $queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $customer->id, QBDesktopUtilities::QB_QUERY_CUSTOMER_PRIORITY, [], $this->userName);
        }

        $queue->enqueue(QUICKBOOKS_ADD_RECEIVEPAYMENT, $id, QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY, null, $this->userName);
    }

    /**
     * Query Payment
     * @param  int $id Payment Id
     * @param  string $userName username
     * @return void
     */
    public function queryPayment($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $jobPayment = JobPayment::where('id', $id)->first();
        $customer = $jobPayment->customer;

        if(!$customer->qb_desktop_id) return false;

        if ($customer->qb_desktop_delete) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_RECEIVEPAYMENT, $id, QBDesktopUtilities::QB_QUERY_RECEIVEPAYMENT_PRIORITY, null, $this->userName);
    }

    /**
     * Delete Payment
     * @param  int $id Payment Id
     * @param  string $userName username
     * @return void
     */
    public function deletePayment($id, $userName = null, $extraParams = [])
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }
        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_DERIVE_RECEIVEPAYMENT, $id, QBDesktopUtilities::QUICKBOOKS_DELETE_RECEIVEPAYMENT_PRIORITY, $extraParams, $this->userName);
    }

    /**
     * Add Payment Method
     * @param  int $id Payment Method Id
     * @param  string $userName username
     * @return void
     */
    public function queryPaymentMethod($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_PAYMENTMETHOD, $id, QBDesktopUtilities::QB_QUERY_PAYMENT_METHOD_PRIORITY, null, $this->userName);
    }

    /**
     * Add Service Item
     * @param  int $id Item Id
     * @param  string $userName username
     * @return void
     */
    public function addServiceItem($id, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_ADD_SERVICEITEM, $id, QBDesktopUtilities::QB_ADD_SERVICE_ITEM_PRIORITY, null, $this->userName);
    }

    public function deleteServiceItem($financialProduct, $userName = null)
	{
		if(!$financialProduct->qbdProduct) return false;
		if(!self::isAccountConnected($userName)) return false;

		$extraParam = [
			'qb_desktop_id' => $financialProduct->qb_desktop_id,
			'financial_product_id' => $financialProduct->id,
			'company_id' => $this->company_id,
		];
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_DERIVE_ITEM,
			$financialProduct->id,
			QBDesktopUtilities::QB_DELETE_SERVICE_ITEM_PRIORITY,
			$extraParam,
			$this->userName
		);
    }

    public function queryProduct($id, $userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$extraParams['is_financial_product'] = true;
		$extraParams['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		self::queryServiceItem($id, $this->userName, $extraParams);
    }

    public function addProduct($id, $userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$extraParams['is_financial_product'] = true;
        $extraParams['company_id'] = $this->company_id;

        $financialProduct = FinancialProduct::query();
		$financialProduct->where('company_id', $this->company_id);
		$financialProduct->whereIn('id', (array) $id);

		$product = $financialProduct->first();

		if(!$product){
			return false;
		}

		$uomModel = QBDUnitOfMeasurement::where('company_id', $product->company_id)
					->where('name', $product->unit)
					->first();

		if(!$uomModel) {
			$uomModel = new QBDUnitOfMeasurement;
			$uomModel->name = $product->unit;
			$uomModel->company_id = $product->company_id;
			$uomModel->save();
		}
		if (!$uomModel->qb_desktop_id) {
			$this->addUnitMeasurement($uomModel, $this->userName);
		}

		// if($product) {
		// 	Log::warning("Financial Product is already synced");
		// 	return false;
		// }
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_ADD_SERVICEITEM, $id, QBDesktopUtilities::QB_ADD_SERVICE_ITEM_PRIORITY, $extraParams, $this->userName);
    }

	public function addMultipleProduct($input = array())
	{
		if(empty($input)) return [];
		if(!self::isAccountConnected()) return false;
		$extraParams['is_financial_product'] = true;
		$extraParams['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$financialProduct = FinancialProduct::query();
		$financialProduct->where('company_id', $this->company_id);
		$financialProduct->where('unit', '!=', '');
		if(ine($input, 'category_id')) {
			$financialProduct->whereIn('category_id', (array)$input['category_id']);
			$financialProduct->whereNull('supplier_id');
		}
		if(ine($input, 'ids')) {
			$financialProduct->whereIn('id', (array)$input['ids']);
		}
		if(ine($input, 'supplier_id')) {
			$financialProduct->whereIn('supplier_id', (array)$input['supplier_id']);
		}
		$financialProduct->where(function($query) {
			$query->where('labor_id', '=', 0)->orWhereNull('labor_id');
		});
		$ids = $financialProduct->pluck('qb_desktop_id', 'id')->toArray();
		DB::table('financial_products')->whereIn('id', array_keys($ids))->update(['manual_qbd_sync' => true, 'qbd_processed' => false]);
		foreach ($ids as $id => $desktopId) {
			if($desktopId) {
				self::queryServiceItem($id, $this->userName, ['is_financial_product' => true]);
			} else {
				$queue->enqueue(QUICKBOOKS_ADD_SERVICEITEM, $id, QBDesktopUtilities::QB_ADD_SERVICE_ITEM_PRIORITY, $extraParams, $this->userName);
			}
		}

		return count($ids);
	}

    /**
     * Query Service Item
     * @param  int $id Item Id
     * @param  string $userName username
     * @return void
     */
    public function queryServiceItem($id, $userName = null, $extra = array())
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }
        $extra['company_id'] = $this->company_id;
        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_SERVICEITEM, $id, QBDesktopUtilities::QB_QUERY_SERVICE_ITEM_PRIORITY, $extra, $this->userName);
    }

    /**
     * Add Service Item
     * @param  int    $id         Item Id
     * @param  string $userName   username
     * @return void
     */
    public function addDiscountItem($id, $userName = null)
    {
        if(!self::isAccountConnected($userName)) return false;
        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_ADD_DISCOUNTITEM, $id, QBDesktopUtilities::QB_ADD_DISCOUNT_ITEM_PRIORITY, null, $this->userName);
    }
    /**
     * Query Service Item
     * @param  int    $id         Item Id
     * @param  string $userName   username
     * @return void
     */
    public function queryDiscountItem($id, $userName = null) {
        if(!self::isAccountConnected($userName)) return false;

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_DISCOUNTITEM, $id, QBDesktopUtilities::QB_QUERY_DISCOUNT_ITEM_PRIORITY, null, $this->userName);
    }

    /**
     * Add Customer
     * @param  int $id Customer Id
     * @param  string $userName username
     * @return void
     */
    public function addCustomer($customerId, $userName = null)
    {

        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $customerId, QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY, null, $this->userName);

        $jobs = Job::where('customer_id', $customerId)->get();
        foreach ($jobs as $job) {
            if ($job->isProject()) {
                $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY, null, $this->userName);
                continue;
            }
            $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_JOB_PRIORITY, null, $this->userName);
        }
    }

    /**
     * Add All Customer Info
     * @param  int    $id         Customer Id
     * @param  string $userName   username
     * @return void
     */
    public function addAllCustomerInfo($customerId, $userName = null) {
        if(!self::isAccountConnected($userName)) return false;
        $customer = Customer::find($customerId);
        if(!$customer) return null;
        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $customerId, QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY, null, $this->userName);
        $jobEntity = "SELECT job_id FROM job_invoices WHERE customer_id = {$customerId} AND deleted_at IS NULL GROUP BY job_id 
            UNION SELECT job_id FROM job_payments WHERE customer_id = {$customerId} AND canceled IS NULL GROUP BY job_id 
            UNION SELECT job_id FROM job_credits WHERE customer_id = {$customerId} AND canceled IS NULL GROUP BY job_id";
        $jobs = Job::where('customer_id', $customer->id)
            ->join(DB::raw("({$jobEntity}) as job_entity"), 'job_entity.job_id', '=', 'jobs.id')
            ->whereNull('qb_desktop_id')
            ->whereQbDesktopDelete(false)
            ->select('jobs.*')
            ->get();

        foreach ($jobs as $job) {
            if($job->isProject()) {
                $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY, null, $this->userName);
                continue;
            }
            $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_JOB_PRIORITY, null, $this->userName);
        }
        //sync all payments
        foreach ($customer->payments as $payment) {
            $queue->enqueue(QUICKBOOKS_ADD_RECEIVEPAYMENT, $payment->id, QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY, null, $this->userName);
        }
        //sync all invoices
        foreach ($customer->invoices as $invoice) {
            $queue->enqueue(QUICKBOOKS_ADD_INVOICE, $invoice->id, QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY, null, $this->userName);  
        }
        //sync all credits
        foreach ($customer->jobCredits as $jobCredit) {
            $queue->enqueue(QUICKBOOKS_ADD_CREDITMEMO, $jobCredit->id, QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY, null, $this->userName);
        }
    }


    /**
     * Query Customer
     * @param  int $id Customer Id
     * @param  string $userName username
     * @return void
     */
    public function queryCustomer($customerId, $userName = null, $extraParams = null)
    {
        if(!self::isAccountConnected($userName)) return false;

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $customerId, QBDesktopUtilities::QB_QUERY_CUSTOMER_PRIORITY, $extraParams, $this->userName);
    }

    /**
     * Add all job of customer
     * @param  int $id Customer Id
     * @param  string $userName username
     * @return void
     */
    public function addCustomerJobs($customerId, $userName = null)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $customer = Customer::find($customerId);
        if ($customer->qb_desktop_delete) {
            return true;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

        if (!$customer->qb_desktop_id) {
            $queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $customer->id, QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY, null, $this->userName);
        }

        $jobEntity = "SELECT job_id FROM job_invoices WHERE customer_id = {$customerId} AND deleted_at IS NULL GROUP BY job_id
            UNION SELECT job_id FROM job_payments WHERE customer_id = {$customerId} AND canceled IS NULL GROUP BY job_id
            UNION SELECT job_id FROM job_credits WHERE customer_id = {$customerId} AND canceled IS NULL GROUP BY job_id";

        $jobs = Job::where('customer_id', $customer->id)
            ->join(DB::raw("({$jobEntity}) as job_entity"), 'job_entity.job_id', '=', 'jobs.id')
            ->whereNull('qb_desktop_id')
            ->whereQbDesktopDelete(false)
            ->select('jobs.*')
            ->get();

        foreach ($jobs as $job) {
            if ($job->isProject()) {
                $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY, null, $this->userName);
                continue;
            }
            // $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
            $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_JOB_PRIORITY, null, $this->userName);
        }

        return true;
    }

    /**
     * Add Job
     * @param  int $id Job Id
     * @param  string $userName username
     * @return void
     */
    public function addJob($jobId, $userName = null, $syncAllInfo = false)
    {
        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $job = Job::find($jobId);

        if ($job->qb_desktop_delete) {
            return false;
        }

        $customer = $job->customer;

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

        if (!$customer->qb_desktop_id) {
            $queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $customer->id, QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY, null, $this->userName);
        }

        if (!$job->isProject()) {
            if ($job->qb_desktop_id) {
                $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_JOB_PRIORITY, null, $this->userName);
            } else {
                $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_JOB_PRIORITY, null, $this->userName);
            }

            return true;
        }

        $parent = $job->parentJob;
        if (!$parent->qb_desktop_id) {
            $queue->enqueue(QUICKBOOKS_ADD_JOB, $parent->id, QBDesktopUtilities::QB_ADD_PARENT_JOB_PRIORITY, null, $this->userName);
        }

        if ($job->qb_desktop_id) {
            $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY, null, $this->userName);
        } else {
            $queue->enqueue(QUICKBOOKS_ADD_JOB, $job->id, QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY, null, $this->userName);
        }

        if(!$syncAllInfo) return true;
        //sync all invoices
        foreach ($job->invoices as $invoice) {
            $queue->enqueue(QUICKBOOKS_ADD_INVOICE, $invoice->id, QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY, null, $this->userName);  
        }
        //sync all credits
        foreach ($job->credits as $jobCredit) {
            $queue->enqueue(QUICKBOOKS_ADD_CREDITMEMO, $jobCredit->id, QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY, null, $this->userName);
        }

        return true;
    }

    /**
     * Add Payment
     * @param  int $id Payment Id
     * @param  string $userName username
     * @param  array $extraParams extra params
     * @return void
     */
    public function queryJob($jobId, $userName = null, $extraParam = null)
    {

        if (!self::isAccountConnected($userName)) {
            return false;
        }

        $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
        $queue->enqueue(QUICKBOOKS_QUERY_JOB, $jobId, QBDesktopUtilities::QB_QUERY_JOB_PRIORITY, $extraParam, $this->userName);
    }

    public function productImport($userName)
	{
		if(!self::isAccountConnected($userName)) return false;
		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_IMPORT_SERVICEITEM, null, QBDesktopUtilities::QB_SERVICE_PRODUCT_IMPORT_PRIORITY, $extra, $this->userName);
    }

	public function accountImport($userName)
	{
		if(!self::isAccountConnected($userName)) return false;

		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_IMPORT_ACCOUNT, null, QBDesktopUtilities::QB_ACCOUNT_IMPORT_PRIORITY, $extra, $this->userName);
    }

	public function unitMeasurementImport($userName)
	{
		if(!self::isAccountConnected($userName)) return false;

		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_IMPORT_UNITOFMEASURESET, null, QBDesktopUtilities::QB_IMPORT_UNITOFMEASURESET_PRIORITY, $extra, $this->userName);
    }

	public function addUnitMeasurement($uom, $userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;

		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_ADD_UNITOFMEASURESET, $uom->id, QBDesktopUtilities::QB_ADD_UNITOFMEASURESET_PRIORITY, $extra, $this->userName);
    }

	public function addWorksheet($worksheet, $userName = null)
	{
		if(!$worksheet->sync_on_qbd_by) return false;
		if(!self::isAccountConnected($userName)) return false;

		self::addJob($worksheet->job_id);
		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

		$queue->enqueue(QUICKBOOKS_ADD_ESTIMATE, $worksheet->id, QBDesktopUtilities::QB_ADD_ESTIMATE_PRIORITY, $extra, $this->userName);
    }

	public function queryWorksheet($worksheet, $userName = null)
	{
		if(!$worksheet->sync_on_qbd_by) return false;
		if(!self::isAccountConnected($userName)) return false;
		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_QUERY_ESTIMATE, $worksheet->id, QBDesktopUtilities::QB_QUERY_ESTIMATE_PRIORITY, $extra, $this->userName);
    }

	public function deleteWorksheet($worksheet, $userName = null)
	{
		if(!$worksheet->sync_on_qbd_by) return false;
		if(!$worksheet->qb_desktop_txn_id) return false;
		if(!self::isAccountConnected($userName)) return false;
		$extra = [
			'company_id' => $this->company_id,
			'type'       => 'Estimate',
			'qb_desktop_id' => $worksheet->qb_desktop_id,
			'qb_desktop_txn_id' => $worksheet->qb_desktop_txn_id,
			'job_id' => $worksheet->job_id,
		];
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_QUERY_DELETEDTXNS, $worksheet->id, QBDesktopUtilities::QB_QUERY_DELETEDTXNS_PRIORITY, $extra, $this->userName);
    }

	public function queryUOMRequest($id, $userName = null, $extra = [])
	{
		if(!self::isAccountConnected($userName)) return false;
		$extra['company_id'] = $this->company_id;
		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_QUERY_UNITOFMEASURESET,
			$id,
			QBDesktopUtilities::QB_QUERY_UNITOFMEASURESET_PRIORITY,
			$extra,
			$this->userName
		);
    }

    public function allVendorsSync($userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$metaData['action'] = QuickBookDesktopTask::SYNC_ALL;
		$metaData['user'] = $this->userName;
        $metaData['object'] = QuickBookDesktopTask::VENDOR;
        $metaData['priority'] = QuickBookDesktopTask::PRIORITY_IMPORT_VENDOR;
        $metaData['origin'] = QuickBookDesktopTask::ORIGIN_QBD;
        $metaData['company_id'] = $this->company_id;
        $taskSchedule = App::make(\App\Services\QuickBookDesktop\TaskScheduler::class);
        $taskSchedule->addTask(QUICKBOOKS_IMPORT_VENDOR, $this->userName, $metaData);

        return true;
	}

	public function createVendors($userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$vendors = Vendor::where('company_id', $this->company_id)
                ->whereNull('qb_desktop_id')
                ->pluck('id')
                ->toArray();
        $taskSchedule = App::make(\App\Services\QuickBookDesktop\TaskScheduler::class);
        foreach ($vendors as $vendorId) {
            $taskSchedule->addJpVendorTask(QuickBookDesktopTask::CREATE, $vendorId, null, $this->userName);
        }
	}

    /**
     * Is Account Connected
     * @param  string $userName username
     * @return void
     */
    public function isAccountConnected($userName = null)
    {
        if ($this->isConnected) {
            return true;
        }

        $qbUser = QBDesktopUser::query();
        if (getScopeId()) {
            $qbUser->where('company_id', getScopeId());
        } else {
            $qbUser->where('qb_username', $userName);
        }

        $qbUser->whereSetupCompleted(true);

        $user = $qbUser->first();

        if ($user) {
            $this->userName = $user->qb_username;
            $this->isConnected = true;
            $this->company_id  = $user->company_id;

            return true;
        }

        return false;
    }

    public function addImportRequest($userName)
	{
		if (!self::isAccountConnected($userName)) return false;

		$syncTask = app()->make(SyncTask::class);

		$syncTask->addSyncTasks($userName);
	}

	public function addVendor($id, $userName = null)
	{
		if (!self::isAccountConnected($userName)) return false;

		$extra['company_id'] = $this->company_id;

		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

		$queue->enqueue(QUICKBOOKS_ADD_VENDOR, $id, QBDesktopUtilities::QB_ADD_VENDOR_PRIORITY, $extra, $this->userName);
	}

	public function queryVendor($id, $userName = null, $extra = [])
	{
		if(!self::isAccountConnected($userName)) return false;
		$extra['company_id'] = $this->company_id;

		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(QUICKBOOKS_QUERY_VENDOR,
			$id,
			QBDesktopUtilities::QB_QUERY_VENDOR_PRIORITY,
			$extra,
			$this->userName
		);
	}

	public function addBill($id, $userName = null)
	{
		if (!self::isAccountConnected($userName)) return false;

		$extra['company_id'] = $this->company_id;

		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);

		$queue->enqueue(QUICKBOOKS_ADD_BILL, $id, QBDesktopUtilities::QB_ADD_VENDOR_PRIORITY, $extra, $this->userName);
	}

	public function queryBill($id, $userName = null, $extra = [])
	{
		if (!self::isAccountConnected($userName)) return false;
		$extra['company_id'] = $this->company_id;

		$queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $this->userName);
		$queue->enqueue(
			QUICKBOOKS_QUERY_VENDOR,
			$id,
			QBDesktopUtilities::QB_QUERY_VENDOR_PRIORITY,
			$extra,
			$this->userName
		);
	}

	public function getUsername($companyId)
	{
		$qbUser = QBDesktopUser::query();
		$qbUser->where('company_id', $companyId);
		$qbUser->whereSetupCompleted(true);
		$user = $qbUser->first();

		if($user) {
			return $user->qb_username;
		}

		return false;
	}

	public function allAccountsSync($userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$metaData['action'] = QuickBookDesktopTask::SYNC_ALL;
		$metaData['user'] = $this->userName;
        $metaData['object'] = QuickBookDesktopTask::ACCOUNT;
        $metaData['priority'] = QuickBookDesktopTask::PRIORITY_IMPORT_ACCOUNT;
        $metaData['origin'] = QuickBookDesktopTask::ORIGIN_QBD;
        $metaData['company_id'] = $this->company_id;
        $taskSchedule = App::make(\App\Services\QuickBookDesktop\TaskScheduler::class);
        $taskSchedule->addTask(QUICKBOOKS_IMPORT_ACCOUNT, $this->userName, $metaData);

        return true;
	}

	public function createAccounts($userName = null)
	{
		if(!self::isAccountConnected($userName)) return false;
		$vendors = FinancialAccount::where('company_id', $this->company_id)
                ->whereNull('qb_desktop_id')
                ->pluck('id')
                ->toArray();
        $taskSchedule = App::make(\App\Services\QuickBookDesktop\TaskScheduler::class);
        foreach ($vendors as $vendorId) {
            $taskSchedule->addJpAccountTask(QuickBookDesktopTask::CREATE, $vendorId, null, $this->userName);
        }
	}
}
