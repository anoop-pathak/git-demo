<?php

namespace App\Http\Controllers;

use App\Exceptions\QuickBookException;
use App\Models\ApiResponse;
use App\Models\JobInvoice;
use App\Models\QuickBook;
use App\Services\Contexts\Context;
use FlySystem;
use App\Services\QuickBooks\CustomerImport;
use App\Services\QuickBooks\QuickBookDivision;
use App\Services\QuickBooks\QuickBookProducts;
use App\Services\QuickBooks\QuickBookService;
use Request;
use Illuminate\Support\Facades\Lang;
use Sorskod\Larasponse\Larasponse;
use App\Services\QuickBooks\Facades\Item as QBItem;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Services\QuickBooks\Facades\Department as QBDepartment;

class QuickBooksController extends Controller
{

    protected $scope;

    public function __construct(
        QuickBookService $quickService,
        Context $scope,
        Larasponse $response,
        CustomerImport $quickbookCustomerImport,
        QuickBookProducts $qbProducts,
        QuickBookDivision $qbDivision
    ) {

        $this->response = $response;
        $this->quickService = $quickService;
        $this->quickbookCustomerImport = $quickbookCustomerImport;
        $this->qbProducts = $qbProducts;
        $this->qbDivision = $qbDivision;
        $this->scope = $scope;
        parent::__construct();
    }

    public function connectPage()
    {
        return view('quickbooks/choose-scope-during-connect');
    }
    public function connection()
    {
        $withPaymentsScope = Request::has('with_payments_scope');
        return redirect($this->quickService->getAuthorizationUrl($withPaymentsScope));
    }

    public function get_response()
    {
        $input = Request::all();
        $quickbook = QuickBook::where('access_token', $input['oauth_token'])->firstOrFail();
        try {
            $this->quickService->authentication(
                $quickbook,
                $input['oauth_verifier'],
                $input['realmId']
            );
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        return view('google_redirect');
    }

    public function disconnect()
    {
        try {
            $this->quickService->accountDisconnect();

            return ApiResponse::success([
                'message' => trans('response.success.disconnected', ['attribute' => 'Quickbook Account']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function getCustomer()
    {
        return $this->quickService->getCustomer();
    }

    public function customerImport()
    {
        try {
            set_time_limit(0);

            $customers = $this->quickbookCustomerImport->import();
            if (!$customers) {
                return ApiResponse::success(['message' => trans('response.error.no_customer_to_import')]);
            }

            return ApiResponse::success([
                'message' => trans('response.success.records_received', ['count' => $customers])
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorInternal($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * get quickbook invoice
     * @param  [type] $invoiceId [description]
     * @return [type]            [description]
     */
    public function getInvoicePdf($invoiceId)
    {
        $input = Request::onlyLegacy('download');
        $jobInvoice = JobInvoice::whereQuickbookInvoiceId($invoiceId)->firstOrFail();

        try {
            $token = $this->quickService->getToken();
            //quickbook Invoice
            $fileResource = $this->quickService->getPdf($token, $jobInvoice->invoice_number, $jobInvoice->quickbook_invoice_id);
            if (!$fileResource) {
                //system invoice
                $path = config('jp.BASE_PATH') . $jobInvoice->file_path;

                if (!$jobInvoice->file_size) {
                    $path = 'public/' . $jobInvoice->file_path;
                }

                $fileResource = FlySystem::read($path);
            }

            $response = \response($fileResource, 200);

            $response->header('Content-Type', 'application/pdf');

            $filename = $jobInvoice->id . '_invoice.pdf';

            if (!$input['download']) {
                $response->header('Content-Disposition', 'filename="' . $filename . '"');
            } else {
                $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }

            return $response;
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.error_page'),
            ]);
        }


        if (!$pdf) {
            $pdf = $this->getSystemInvoicePdf($invoiceId);
        }

        return $pdf;
    }

    /**
     * get system invoice
     * @param  [type] $invoiceId [description]
     * @return [type]            [description]
     */
    public function getSystemInvoicePdf($invoiceId)
    {
        $jobInvoice = JobInvoice::findOrFail($invoiceId);
        try {
            $path = config('jp.BASE_PATH') . $jobInvoice->file_path;

            if (!$jobInvoice->file_size) {
                $path = 'public/' . $jobInvoice->file_path;
            }

            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'filename="invoice.pdf"'
            ];
            $fileResource = FlySystem::read($path);
            return \response($fileResource, 200, $headers);
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.error_page'),
            ]);
        }
    }

    /**
     * Get QuickBook Products
     *
     * @return Products
     */
    public function getProducts()
    {
        $input = Request::all();
        $token = $this->quickService->getToken();
        if (!$token) {
            return ApiResponse::errorGeneral(
                trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
            );
        }

        try {
            $limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');
            $products = QBItem::getProducts($limit, $input);
            // $products = $this->qbProducts->getQBProducts($token, $limit, $input);

            return ApiResponse::success($products);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get QuickBook Products
     *
     * @return Products
     */
    public function getAccounts()
    {
        $input = Request::all();
        try {
            $token = $this->quickService->getToken();
            if(!$token) {
                return ApiResponse::errorGeneral(
                    trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
                );
            }
            $limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');
            $accounts = QBAccount::getAccounts($limit, $input);
            // $accounts = $this->qbProducts->getQBAccounts($token, $limit, $input);

            return ApiResponse::success($accounts);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     *
     */
    public function getDivisions()
    {
        $input = Request::all();
        $token = $this->quickService->getToken();
        if (!$token) {
            return ApiResponse::errorGeneral(
                trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
            );
        }

        try {
            $limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');
            $divisions = QBDepartment::getDivisions($limit, $input);
            // $divisions = $this->qbDivision->getQBDivisions($token, $limit, $input);

            return ApiResponse::success($divisions);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function importCustomers()
	{
		\App\Services\QuickBooks\Facades\Customer::importAllCustomers();
	}
}
