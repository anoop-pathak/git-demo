<?php

namespace App\Services\QuickBookDesktop;

use App\Models\QBDesktopUser;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use QuickBooks_WebConnector_Handlers;
use App\Services\QuickBookDesktop\QBDesktopUnitOfMeasurement;
use App\Services\QuickBookDesktop\QBDesktopWorksheet;
use App\Repositories\QBDesktopProductRepository;
use App\Repositories\QBDesktopAccountRepository;
use App\Services\QuickBookDesktop\QBDesktopProduct;
use App\Services\Worksheets\WorksheetsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\QuickBookDesktop\TaxHandler;
use App\Services\QuickBookDesktop\QBDesktopVendor;
use App\Services\QuickBookDesktop\QBDesktopBill;
use App\Services\QuickBookDesktop\CDC\Customer as CDCCustomer;
use App\Services\QuickBookDesktop\CDC\Transaction as Transaction;
use App\Services\QuickBookDesktop\CDC\Invoice as CDCInvoice;
use App\Services\QuickBookDesktop\CDC\CreditMemo as CDCCreditMemo;
use App\Services\QuickBookDesktop\CDC\ReceivePayment as CDCReceivePayment;
use App\Services\QuickBookDesktop\CDC\PaymentMethod as CDCPaymentMethod;
use App\Services\QuickBookDesktop\CDC\ItemSalesTax;
use App\Services\QuickBookDesktop\CDC\ItemSalesTaxGroup;
use App\Services\QuickBookDesktop\CDC\SalesTaxCode;
use App\Services\QuickBookDesktop\CDC\Vendor as CDCVendor;
use App\Services\QuickBookDesktop\CDC\Account as CDCAccount;
use App\Services\QuickBookDesktop\CDC\Bill as CDCBill;
use App\Services\QuickBookDesktop\CDC\Estimate as CDCEstimate;
use App\Services\QuickBookDesktop\CDC\Item as CDCItem;
use App\Services\QuickBookDesktop\CDC\UnitOfMeasurement as CDCUnitOfMeasurement;
use App\Services\QuickBookDesktop\Setting\Time;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Item as ItemEnity;
use App\Services\QuickBookDesktop\Setting\Settings;
use Exception;
Use App\Models\PaymentMethod;

define('QUICKBOOKS_OBJECT_XML_DROP', true);

class QBDesktop
{

    public function __construct(
        QBDesktopProductRepository $produtRepo,
		QBDesktopAccountRepository $acccountRepo,
		WorksheetsService $worksheetService,
		CDCCustomer $cdcCustomer,
		Transaction $cdcTransection,
		CDCInvoice $cdcInvoice,
		CDCCreditMemo $cdcCreditMemo,
		CDCReceivePayment $cdcReceivePayment,
		CDCPaymentMethod $cdcPaymentMethod,
		QBDAccount $qbdAccount,
		Settings $settings,
		ItemEnity $item
    ){
		$this->qbdProductRepo = $produtRepo;
		$this->qbdAccountRepo = $acccountRepo;
        $this->worksheetService = $worksheetService;
        $this->cdcCustomer = $cdcCustomer;
		$this->cdcTransection = $cdcTransection;
		$this->cdcInvoice = $cdcInvoice;
		$this->cdcCreditMemo = $cdcCreditMemo;
		$this->cdcReceivePayment = $cdcReceivePayment;
		$this->cdcPaymentMethod = $cdcPaymentMethod;
		$this->qbdAccount = $qbdAccount;
		$this->settings = $settings;
		$this->item = $item;
    }

	public function getProducts($filters, $sortable = true)
	{
		return $this->qbdProductRepo->getFilteredProducts($filters);
    }

    /**
     * Hanlde QB Webconnector Request and Response
     * @return
     */
    public function connector()
    {
        try {
            $qbCustomer = new QBDesktopCustomerJob;
            $qbServiceItem = new QBDesktopServiceItem;
            $qbPaymentMethod = new QBDesktopPaymentMethod;
            $errorHandler = new QBDesktopErrorHandler;
            $qbInvoice = new QBDesktopInvoice;
            $payment = new QBDesktopPayment;
            $creditMemo = new QBDesktopCreditMemo;
            $qbAccount = new QBDesktopAccount;
            // $qbProduct       = new QBDesktopProduct;
            $qbDicountItem   = new QBDesktopDiscountItem;
            $qbUnitMeasurement = new QBDesktopUnitOfMeasurement;
            $qbWorksheet       = new QBDesktopWorksheet;
            $taxHandler       = new TaxHandler;

			$qbVendor = app()->make(QBDesktopVendor::class);
			$qbBill = app()->make(QBDesktopBill::class);

			$cdcItemSalesTaxGroup = app()->make(ItemSalesTaxGroup::class);
			$cdcItemSalesTax = app()->make(ItemSalesTax::class);
			$cdcSalesTaxCode = app()->make(SalesTaxCode::class);
			$cdcVendor = app()->make(CDCVendor::class);
			$cdcAccount = app()->make(CDCAccount::class);
			$cdcBill = app()->make(CDCBill::class);
			$cdcEstimate = app()->make(CDCEstimate::class);
			$cdcItem = app()->make(CDCItem::class);
			$cdcUnitOfMeasurement = app()->make(CDCUnitOfMeasurement::class);

			$this->timeSettings = app()->make(Time::class);

            $map = [];
            $errmap = [];
            $hooks = [
                QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => [
                    [$this, 'userLoginSuccess']
                ]
            ];

            $map = [
                QUICKBOOKS_ADD_CUSTOMER => [
                    [$qbCustomer, 'addCustomerRequest'],
                    [$qbCustomer, 'addCustomerResponse'],
                ],
                QUICKBOOKS_MOD_CUSTOMER => [
                    [$qbCustomer, 'addCustomerRequest'],
                    [$qbCustomer, 'addCustomerResponse'],
                ],
                QUICKBOOKS_QUERY_CUSTOMER => [
                    [$qbCustomer, 'customerQueryRequest'],
                    [$qbCustomer, 'customerQueryResponse'],
                ],
                QUICKBOOKS_ADD_JOB => [
                    [$qbCustomer, 'addJobRequest'],
                    [$qbCustomer, 'addJobResponse'],
                ],
                QUICKBOOKS_ADD_DISCOUNTITEM => [
                    [$qbDicountItem, 'addDiscountItemRequest'],
                    [$qbDicountItem, 'addDiscountResponse'],
                ],
                QUICKBOOKS_QUERY_DISCOUNTITEM => [
                    [$qbDicountItem, 'queryDiscountItemRequest'],
                    [$qbDicountItem, 'queryDiscountItemResponse'],
                ],
                QUICKBOOKS_QUERY_JOB => [
                    [$qbCustomer, 'jobQueryRequest'],
                    [$qbCustomer, 'jobQueryResponse'],
                ],
                QUICKBOOKS_ADD_PAYMENTMETHOD => [
                    [$qbPaymentMethod, 'addPaymentMethodRequest'],
                    [$qbPaymentMethod, 'addPaymentMethodResponse']
                ],
                QUICKBOOKS_QUERY_PAYMENTMETHOD => [
                    [$qbPaymentMethod, 'queryPaymentMethodRequest'],
                    [$qbPaymentMethod, 'queryPaymentMethodResponse']
                ],
                QUICKBOOKS_ADD_SERVICEITEM => [
                    [$qbServiceItem, 'addServiceItemRequest'],
                    [$qbServiceItem, 'addServiceItemResponse'],
                ],
                QUICKBOOKS_QUERY_SERVICEITEM => [
                    [$qbServiceItem, 'queryServiceItemRequest'],
                    [$qbServiceItem, 'queryServiceItemResponse'],
                ],
                QUICKBOOKS_DERIVE_ITEM => [
                    [$qbServiceItem, 'deleteServiceItem'],
                    [$qbServiceItem, 'deleteServiceResponse'],
                ],
                QUICKBOOKS_ADD_INVOICE => [
                    [$qbInvoice, 'addInvoiceRequest'],
                    [$qbInvoice, 'addInvoiceResponse'],
                ],
                QUICKBOOKS_QUERY_INVOICE => [
                    [$qbInvoice, 'invoiceQueryRequest'],
                    [$qbInvoice, 'invoiceQueryResponse'],
                ],
                QUICKBOOKS_DERIVE_INVOICE => [
                    [$qbInvoice, 'jobInvoiceDeleteRequest'],
                    [$qbInvoice, 'jobInvoiceDeleteResponse'],
                ],
                QUICKBOOKS_ADD_RECEIVEPAYMENT => [
                    [$payment, 'addPaymentRequest'],
                    [$payment, 'addPaymentResponse'],
                ],
                QUICKBOOKS_DERIVE_RECEIVEPAYMENT => [
                    [$payment, 'jobPaymentDeleteRequest'],
                    [$payment, 'jobPaymentDeleteResponse'],
                ],
                QUICKBOOKS_QUERY_RECEIVEPAYMENT => [
                    [$payment, 'jobPaymentQueryRequest'],
                    [$payment, 'jobPaymentQueryResponse'],
                ],
                QUICKBOOKS_ADD_CREDITMEMO => [
                    [$creditMemo, 'addcreditMemoRequest'],
                    [$creditMemo, 'addcreditMemoResponse'],
                ],
                QUICKBOOKS_DERIVE_CREDITMEMO => [
                    [$creditMemo, 'creditMemoDeleteRequest'],
                    [$creditMemo, 'creditMemoDeleteResponse'],
                ],
                QUICKBOOKS_QUERY_CREDITMEMO => [
                    [$creditMemo, 'creditMemoQueryRequest'],
                    [$creditMemo, 'creditMemoQueryResponse'],
                ],
                QUICKBOOKS_ADD_ACCOUNT => [
                    [$qbAccount, 'addRequest'],
                    [$qbAccount, 'addResponse'],
                ],
                QUICKBOOKS_QUERY_ACCOUNT => [
                    [$qbAccount, 'queryRequest'],
                    [$qbAccount, 'queryResponse'],
                ],
                QUICKBOOKS_IMPORT_ACCOUNT => [
                    [$qbAccount, 'importRequest'],
                    [$qbAccount, 'importResponse'],
                ],
                QUICKBOOKS_DERIVE_ACCOUNT => [
                    [$qbAccount, 'deleteRequest'],
                    [$qbAccount, 'deleteResponse'],
                ],
                QUICKBOOKS_IMPORT_UNITOFMEASURESET => [
                    [$cdcUnitOfMeasurement, 'importRequest'],
                    [$cdcUnitOfMeasurement, 'importResponse'],
                ],
                QUICKBOOKS_ADD_UNITOFMEASURESET => [
                    [$qbUnitMeasurement, 'addRequest'],
                    [$qbUnitMeasurement, 'addResponse'],
                ],
                QUICKBOOKS_QUERY_UNITOFMEASURESET => [
                    [$qbUnitMeasurement, 'queryRequest'],
                    [$qbUnitMeasurement, 'queryResponse'],
                ],
                QUICKBOOKS_ADD_ESTIMATE => [
                    [$qbWorksheet, 'addRequest'],
                    [$qbWorksheet, 'addResponse']
                ],
                QUICKBOOKS_MOD_ESTIMATE => [
                    [$qbWorksheet, 'addRequest'],
                    [$qbWorksheet, 'addResponse']
                ],
                QUICKBOOKS_QUERY_ESTIMATE => [
                    [$qbWorksheet, 'queryRequest'],
                    [$qbWorksheet, 'queryResponse']
                ],
                QUICKBOOKS_QUERY_DELETEDTXNS => [
                    [$qbWorksheet, 'deleteRequest'],
                    [$qbWorksheet, 'deleteResponse']
                ],
                QUICKBOOKS_IMPORT_CUSTOMER => [
                    [$this->cdcCustomer, 'importRequest'],
                    [$this->cdcCustomer, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_JOB => [
                    [$this->cdcCustomer, 'importRequest'],
                    [$this->cdcCustomer, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_TRANSACTION => [
                    [$this->cdcTransection, 'importRequest'],
                    [$this->cdcTransection, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_INVOICE => [
                    [$this->cdcInvoice, 'importRequest'],
                    [$this->cdcInvoice, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_CREDITMEMO => [
                    [$this->cdcCreditMemo, 'importRequest'],
                    [$this->cdcCreditMemo, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_PAYMENTMETHOD => [
                    [$this->cdcPaymentMethod, 'importRequest'],
                    [$this->cdcPaymentMethod, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_RECEIVEPAYMENT => [
                    [$this->cdcReceivePayment, 'importRequest'],
                    [$this->cdcReceivePayment, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_SALESTAXITEM => [
                    [$cdcItemSalesTax, 'importRequest'],
                    [$cdcItemSalesTax, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_SALESTAXCODE => [
                    [$cdcSalesTaxCode, 'importRequest'],
                    [$cdcSalesTaxCode, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_SALESTAXGROUPITEM => [
                    [$cdcItemSalesTaxGroup, 'importRequest'],
                    [$cdcItemSalesTaxGroup, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_VENDOR => [
                    [$cdcVendor, 'importRequest'],
                    [$cdcVendor, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_ACCOUNT => [
                    [$cdcAccount, 'importRequest'],
                    [$cdcAccount, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_BILL => [
                    [$cdcBill, 'importRequest'],
                    [$cdcBill, 'importResponse']
                ],
                QUICKBOOKS_ADD_VENDOR => [
                    [$qbVendor, 'addRequest'],
                    [$qbVendor, 'addResponse'],
                ],
                QUICKBOOKS_MOD_VENDOR => [
                    [$qbVendor, 'addRequest'],
                    [$qbVendor, 'addResponse'],
                ],
                QUICKBOOKS_QUERY_VENDOR => [
                    [$qbVendor, 'queryRequest'],
                    [$qbVendor, 'queryResponse'],
                ],
                QUICKBOOKS_DERIVE_VENDOR => [
                    [$qbVendor, 'deleteRequest'],
                    [$qbVendor, 'deleteResponse'],
                ],
                QUICKBOOKS_DERIVE_BILL => [
                    [$qbBill, 'deleteRequest'],
                    [$qbBill, 'deleteResponse'],
                ],
                QUICKBOOKS_ADD_BILL => [
                    [$qbBill, 'addRequest'],
                    [$qbBill, 'addResponse'],
                ],
                QUICKBOOKS_QUERY_BILL => [
                    [$qbBill, 'queryRequest'],
                    [$qbBill, 'queryResponse'],
                ],
                QUICKBOOKS_IMPORT_ESTIMATE => [
                    [$cdcEstimate, 'importRequest'],
                    [$cdcEstimate, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_ITEM => [
                    [$cdcItem, 'importRequest'],
                    [$cdcItem, 'importResponse']
                ],
                QUICKBOOKS_IMPORT_DELETEDTXNS => [
                    [$this->cdcTransection, 'importDeletedTxnRequest'],
                    [$this->cdcTransection, 'importResponse']
                ],
                QUICKBOOKS_ADD_SALESTAXITEM => [
                    [$taxHandler, 'request'],
                    [$taxHandler, 'response']
                ]
            ];

            $log_level = QUICKBOOKS_LOG_VERBOSE;
            $soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;
            $soap_options = [];
            $handler_options = [
                'deny_concurrent_logins' => false,
                'deny_reallyfast_logins' => false,
            ];

            $driver_options = [];
            $callback_options = [];

            //Map error handling to functions
            $errmap = [
                // 3070 => array($errorHandler, '_quickbooks_error_stringtoolong'),
                // 3140 => array($errorHandler, '_quickbooks_reference_error'),
                3170 => [$errorHandler, 'qbMergeErrorHandler'],
                500 => [$errorHandler, 'qbObjectErrorHandler'],
                3120 => [$errorHandler, 'qbInvalidTransactionIdError'], //invalid and it delete from desktop
                3200 => [$errorHandler, 'qbObjectSequenceErrorHandler'],
                3100 => [$errorHandler, 'qbObjectAlreadyUseErrorHandler'],
                3000 => [$errorHandler, 'qbInvalidTransactionIdError'],
                '*' => [$errorHandler, 'qbAllErrorHandler'],
            ];

            $Server = new \QuickBooks_WebConnector_Server(QBDesktopUtilities::dsn(), $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);

            $response = \response($Server->handle(false, true), 200);
            $response->header('Content-Type', 'text/xml');

            return $response;
        } catch (Exception $e) {
            Log::info($e);
        }
    }

    /**
     * Download compoany QWC File
     * @param int $companyId Company Id
     * @return File
     */
    public function downloadCompanyQWCFile($companyId)
    {
        $desktop = new QBDesktopQWCFile;
        $desktop->downloadFile($companyId);
    }

    /**
     * Get Password
     * @param  int $companyId Company Id
     * @return Password
     */
    public function getPassword($companyId)
    {
        $qbUser = QBDesktopUser::where('company_id', $companyId)->first();

        if (!$qbUser) {
            return null;
        }

        $data = [
            'token' => Crypt::decrypt($qbUser->password_key),
            'setup_completed' => (bool)$qbUser->setup_completed
        ];

        return $data;
    }

    /**
     * Account Disconenct
     * @param  int $companyId Comany Id
     * @return Void
     */
    public function accountDisconnect($companyId)
    {
        $qbUser = QBDesktopUser::where('company_id', $companyId)->firstOrFail();
        DB::table('quickbooks_user')->where('company_id', $companyId)->delete();
        DB::table('quickbook_meta')->where('qb_desktop_username', $qbUser->qb_username)->delete();
        DB::table('financial_categories')->where('company_id', $companyId)->update(['qb_desktop_id' => null]);
        // DB::table('quickbooks_user')->where('company_id', $companyId)->delete();
        // DB::table('quickbooks_queue')->where('qb_username', $qbUser->qb_username)->delete();
        // DB::table('quickbooks_ticket')->where('qb_username', $qbUser->qb_username)->delete();
        DB::table('quickbooks_uom')->where('company_id', $companyId)->delete();
    }

    /**
     * WebHook Call
     */
    public function userLoginSuccess($requestID, $user, $hook, &$err, $hook_data, $callback_config)
    {
        $qbUser = QBDesktopUser::where('qb_username', $user)
            ->whereSetupCompleted(true)
            ->first();

        if ($qbUser) {
            $this->syncRequest($qbUser->qb_username);
        }
        if (!$qbUser) {
            $qbdUser = QBDesktopUser::where('qb_username', $user)->first();

			$serviceAccount = $this->qbdAccount->getServiceAccount($qbdUser->company_id);

			if (!$serviceAccount) {
				throw new Exception("Service Account not found.");
			}

			$serviceItem = $this->item->getServiceItem($qbdUser->company_id);

			if (!$serviceItem) {
				throw new Exception("Service Item not found.");
			}

			$discountItem = $this->item->getDiscountItem($qbdUser->company_id);

			if (!$discountItem) {
				throw new Exception("Discount Item not found.");
            }

            DB::table('quickbooks_user')
                ->where('qb_username', $user)
                ->update(['setup_completed' => true]);

            QBDesktopQueue::addAccount($serviceAccount->id, $user);

            QBDesktopQueue::addServiceItem($serviceItem->id, $user);

            QBDesktopQueue::addDiscountItem($discountItem->id, $user);

            $methods = PaymentMethod::Where('company_id', '0')
                ->whereNull('qb_desktop_id')
                ->get();

            foreach ($methods as $method) {
                QBDesktopQueue::addPaymentMethod($method->id, $user);
            }
        }
    }

    public function productImports($companyId)
	{
		$qbUser = QBDesktopUser::where('company_id', $companyId)->first();
		if(!$qbUser) return null;
		QBDesktopQueue::productImport($qbUser->qb_username);
    }

	public function getAccounts($filters = array())
	{
		return $this->qbdAccountRepo->getFilteredAccounts($filters);
    }

	public function importAccounts($companyId)
	{
		$qbUser = QBDesktopUser::where('company_id', $companyId)->first();
		if(!$qbUser) return null;

		QBDesktopQueue::accountImport($qbUser->qb_username);
    }

	public function createAccount($account)
	{
		$qbUser = QBDesktopUser::where('company_id', $account->company_id)->first();
		if(!$qbUser) return null;
		$extraParams = [
			'is_category' => true,
			'company_id'  => getScopeId()
		];
		QBDesktopQueue::addAccount($account->id, $qbUser->qb_username, $extraParams);
    }

	public function importUnitMeasurement($companyId)
	{
		$qbUser = QBDesktopUser::where('company_id', $companyId)->first();
		if(!$qbUser) return null;
		QBDesktopQueue::unitMeasurementImport($qbUser->qb_username);
    }

	public function createProducts($input)
	{
		return QBDesktopQueue::addMultipleProduct($input);
    }

	public function syncWorksheet($worksheetId)
	{
		$qbUser = QBDesktopUser::where('company_id', getScopeId())->firstOrFail();
		$worksheet = $this->worksheetService->getById($worksheetId);
		$worksheet->sync_on_qbd_by = Auth::id();
		$worksheet->is_qbd_worksheet = true;
		$worksheet->save();
		QBDesktopQueue::addWorksheet($worksheet);
    }

    private function syncRequest($user)
	{
		QBDesktopQueue::addImportRequest($user);
	}
}
